-- Booking decision audit trail. Safe to rerun after the base schema exists.
BEGIN;

CREATE TABLE IF NOT EXISTS booking_status_history (
    history_id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    booking_id INTEGER NOT NULL
        REFERENCES bookings(booking_id) ON DELETE RESTRICT,
    actor_user_id INTEGER
        REFERENCES users(user_id) ON DELETE SET NULL,
    previous_status VARCHAR(20),
    new_status VARCHAR(20) NOT NULL,
    note TEXT NOT NULL,
    changed_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT valid_history_previous_status
        CHECK (previous_status IS NULL OR previous_status IN
            ('Pending', 'Approved', 'Rejected', 'Completed')),
    CONSTRAINT valid_history_new_status
        CHECK (new_status IN ('Pending', 'Approved', 'Rejected', 'Completed'))
);

CREATE INDEX IF NOT EXISTS idx_booking_status_history_booking_time
    ON booking_status_history (booking_id, changed_at, history_id);

-- Older bookings receive one current-state snapshot; earlier decisions cannot
-- be reconstructed because they were not recorded before this audit trail.
INSERT INTO booking_status_history
    (booking_id, actor_user_id, previous_status, new_status, note, changed_at)
SELECT b.booking_id, NULL, NULL, b.status,
       'Current status snapshot; earlier history unavailable', CURRENT_TIMESTAMP
FROM bookings AS b
WHERE NOT EXISTS (
    SELECT 1 FROM booking_status_history h WHERE h.booking_id = b.booking_id
);

CREATE OR REPLACE FUNCTION capture_booking_status_history()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $booking_history$
DECLARE
    event_actor_id INTEGER;
    event_previous_status VARCHAR(20);
    event_note TEXT;
BEGIN
    IF TG_OP = 'INSERT' THEN
        event_actor_id := NEW.user_id;
        event_previous_status := NULL;
        event_note := 'Booking request submitted';
    ELSE
        IF OLD.status IS NOT DISTINCT FROM NEW.status THEN
            RETURN NEW;
        END IF;

        event_actor_id := NEW.approved_by;
        event_previous_status := OLD.status;
        event_note := CASE NEW.status
            WHEN 'Approved' THEN 'Booking approved'
            WHEN 'Rejected' THEN 'Booking rejected'
            WHEN 'Completed' THEN 'Booking completed'
            ELSE 'Booking status changed'
        END;
    END IF;

    INSERT INTO booking_status_history
        (booking_id, actor_user_id, previous_status, new_status, note)
    VALUES
        (NEW.booking_id, event_actor_id, event_previous_status, NEW.status, event_note);

    RETURN NEW;
END;
$booking_history$;

DROP TRIGGER IF EXISTS trg_booking_status_history ON bookings;

CREATE TRIGGER trg_booking_status_history
AFTER INSERT OR UPDATE ON bookings
FOR EACH ROW
EXECUTE FUNCTION capture_booking_status_history();

CREATE OR REPLACE VIEW v_booking_status_history AS
SELECT h.history_id,
       h.booking_id,
       b.purpose,
       requester.first_name || ' ' || requester.last_name AS requested_by,
       actor.first_name || ' ' || actor.last_name AS changed_by,
       h.previous_status,
       h.new_status,
       h.note,
       h.changed_at
FROM booking_status_history AS h
JOIN bookings AS b ON b.booking_id = h.booking_id
JOIN users AS requester ON requester.user_id = b.user_id
LEFT JOIN users AS actor ON actor.user_id = h.actor_user_id;

COMMIT;
