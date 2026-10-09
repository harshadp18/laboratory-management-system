-- 1. User directory with role-specific details
CREATE OR REPLACE VIEW v_user_directory AS
SELECT u.user_id,
       u.first_name || ' ' || u.last_name AS full_name,
       u.email,
       u.role,
       f.department,
       la.shift_timing,
       s.roll_no,
       s.year_of_study,
       a.access_level
FROM users AS u
LEFT JOIN faculty AS f ON f.user_id = u.user_id
LEFT JOIN lab_assistants AS la ON la.user_id = u.user_id
LEFT JOIN students AS s ON s.user_id = u.user_id
LEFT JOIN administrators AS a ON a.user_id = u.user_id;

-- 2. Currently installed components per workstation
CREATE OR REPLACE VIEW v_workstation_inventory AS
SELECT l.lab_name,
       w.workstation_no,
       c.component_name,
       c.category,
       c.brand,
       c.model,
       c.serial_number,
       ic.installed_at
FROM workstations AS w
JOIN laboratories AS l ON l.lab_id = w.lab_id
LEFT JOIN installed_components AS ic
       ON ic.lab_id = w.lab_id AND ic.workstation_no = w.workstation_no
      AND ic.removed_at IS NULL
LEFT JOIN components AS c ON c.component_id = ic.component_id;

-- 3. Booking details with requester and approver names
CREATE OR REPLACE VIEW v_booking_details AS
SELECT b.booking_id,
       l.lab_name,
       r.first_name || ' ' || r.last_name AS requested_by,
       ap.first_name || ' ' || ap.last_name AS approved_by_name,
       b.purpose,
       b.start_time,
       b.end_time,
       b.end_time - b.start_time AS duration,
       b.status
FROM bookings AS b
JOIN laboratories AS l ON l.lab_id = b.lab_id
JOIN users AS r ON r.user_id = b.user_id
LEFT JOIN users AS ap ON ap.user_id = b.approved_by;

-- 4. Complaints that are not yet resolved
CREATE OR REPLACE VIEW v_open_complaints AS
SELECT c.complaint_id,
       l.lab_name,
       c.workstation_no,
       u.first_name || ' ' || u.last_name AS raised_by,
       t.first_name || ' ' || t.last_name AS assigned_to_name,
       c.problem_description,
       c.escalation_reason,
       c.status,
       c.raised_at
FROM complaints AS c
JOIN laboratories AS l ON l.lab_id = c.lab_id
JOIN users AS u ON u.user_id = c.user_id
LEFT JOIN users AS t ON t.user_id = c.assigned_to
WHERE c.status IN ('Open', 'In Progress');

-- 5. Maintenance history
CREATE OR REPLACE VIEW v_maintenance_history AS
SELECT m.maintenance_id,
       l.lab_name,
       m.workstation_no,
       m.maintenance_type,
       m.description,
       p.first_name || ' ' || p.last_name AS performed_by_name,
       m.complaint_id,
       m.start_time,
       m.end_time,
       m.status
FROM maintenance AS m
JOIN laboratories AS l ON l.lab_id = m.lab_id
JOIN users AS p ON p.user_id = m.performed_by;

-- 6. Per-lab summary report
CREATE OR REPLACE VIEW v_lab_summary AS
SELECT l.lab_id,
       l.lab_name,
       l.capacity,
       (SELECT COUNT(*) FROM workstations w WHERE w.lab_id = l.lab_id) AS workstation_count,
       (SELECT COUNT(*) FROM bookings b WHERE b.lab_id = l.lab_id) AS booking_count,
       (SELECT COUNT(*) FROM complaints c WHERE c.lab_id = l.lab_id) AS complaint_count,
       (SELECT COUNT(*) FROM complaints c
         WHERE c.lab_id = l.lab_id AND c.status IN ('Open', 'In Progress')) AS unresolved_complaints,
       (SELECT COUNT(*) FROM maintenance m WHERE m.lab_id = l.lab_id) AS maintenance_count
FROM laboratories AS l;

-- 7. Software installed on each workstation
CREATE OR REPLACE VIEW v_workstation_software AS
SELECT l.lab_name,
       ws.workstation_no,
       sp.software_name,
       sp.version,
       sp.license_type,
       sp.license_expiry,
       ws.installed_at
FROM workstation_software AS ws
JOIN laboratories AS l ON l.lab_id = ws.lab_id
JOIN software_packages AS sp ON sp.software_id = ws.software_id;