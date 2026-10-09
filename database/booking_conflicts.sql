-- Approved bookings in the same laboratory may not overlap.
-- Apply after schema.sql on a database that is already initialized.
CREATE EXTENSION IF NOT EXISTS btree_gist;

DO $booking_conflicts$
BEGIN
    IF EXISTS (
        SELECT 1
        FROM bookings a
        JOIN bookings b
          ON a.lab_id = b.lab_id
         AND a.booking_id < b.booking_id
         AND tstzrange(a.start_time, a.end_time, '[)')
             && tstzrange(b.start_time, b.end_time, '[)')
        WHERE a.status = 'Approved'
          AND b.status = 'Approved'
    ) THEN
        RAISE EXCEPTION 'Cannot add the exclusion constraint while approved bookings overlap.'
            USING HINT = 'Resolve the existing approved conflicts, then rerun this script.';
    END IF;

    IF NOT EXISTS (
        SELECT 1
        FROM pg_constraint
        WHERE conname = 'no_overlapping_approved_lab_bookings'
          AND conrelid = 'bookings'::regclass
    ) THEN
        EXECUTE $constraint$
            ALTER TABLE bookings
            ADD CONSTRAINT no_overlapping_approved_lab_bookings
            EXCLUDE USING gist (
                lab_id WITH =,
                tstzrange(start_time, end_time, '[)') WITH &&
            )
            WHERE (status = 'Approved')
        $constraint$;
    END IF;
END
$booking_conflicts$;
