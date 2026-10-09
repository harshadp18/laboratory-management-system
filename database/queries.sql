-- A1. Confirm all 15 tables exist
SELECT table_name
FROM information_schema.tables
WHERE table_schema = 'public' AND table_type = 'BASE TABLE'
ORDER BY table_name;
 
-- A2. Row count of every table
SELECT 'users' AS table_name, COUNT(*) AS row_count FROM users
UNION ALL SELECT 'administrators', COUNT(*) FROM administrators
UNION ALL SELECT 'faculty', COUNT(*) FROM faculty
UNION ALL SELECT 'lab_assistants', COUNT(*) FROM lab_assistants
UNION ALL SELECT 'students', COUNT(*) FROM students
UNION ALL SELECT 'user_phone_numbers', COUNT(*) FROM user_phone_numbers
UNION ALL SELECT 'laboratories', COUNT(*) FROM laboratories
UNION ALL SELECT 'workstations', COUNT(*) FROM workstations
UNION ALL SELECT 'components', COUNT(*) FROM components
UNION ALL SELECT 'installed_components', COUNT(*) FROM installed_components
UNION ALL SELECT 'bookings', COUNT(*) FROM bookings
UNION ALL SELECT 'complaints', COUNT(*) FROM complaints
UNION ALL SELECT 'maintenance', COUNT(*) FROM maintenance
UNION ALL SELECT 'software_packages', COUNT(*) FROM software_packages
UNION ALL SELECT 'workstation_software', COUNT(*) FROM workstation_software
ORDER BY table_name;
 
-- A3. Constraint test: this must FAIL (invalid role)
-- INSERT INTO users (first_name, last_name, email, password_hash, role)
-- VALUES ('Test', 'User', 'test@vit.edu.in', 'x', 'Visitor');
 
-- A4. Constraint test: this must FAIL (end_time before start_time)
-- INSERT INTO bookings (user_id, lab_id, purpose, start_time, end_time)
-- VALUES ((SELECT user_id FROM users WHERE email = 'neha.rao@vit.edu.in'), 1,
--         'Bad booking', '2026-10-10 12:00+05:30', '2026-10-10 10:00+05:30');
 
-- A4b. Constraint test: this must FAIL (booking user must be faculty)
-- INSERT INTO bookings (user_id, lab_id, purpose, start_time, end_time)
-- VALUES ((SELECT user_id FROM users WHERE email = 'riya.patel@vit.edu.in'), 1,
--         'Student booking', '2026-10-10 10:00+05:30', '2026-10-10 12:00+05:30');
 
-- A6. Unique-index test: this must FAIL (CPU-DB-01 is already actively installed)
-- INSERT INTO installed_components (lab_id, workstation_no, component_id)
-- SELECT lab_id, 'PC-03', component_id
-- FROM components, laboratories
-- WHERE serial_number = 'CPU-DB-01' AND lab_name = 'Database Laboratory';
 
-- A5. Referential test: this must FAIL (workstation does not exist)
-- INSERT INTO installed_components (lab_id, workstation_no, component_id)
-- VALUES (1, 'PC-99', 1);
 
-- ---------------- B. JOINS ----------------
 
-- B1. Inner join: bookings with lab and requester
SELECT b.booking_id, l.lab_name, u.first_name, u.last_name,
       b.purpose, b.start_time, b.status
FROM bookings AS b
JOIN laboratories AS l ON l.lab_id = b.lab_id
JOIN users AS u ON u.user_id = b.user_id
ORDER BY b.start_time;
 
-- B2. Left join: every workstation with its current components (NULL if none)
SELECT l.lab_name, w.workstation_no, c.component_name, c.serial_number
FROM workstations AS w
JOIN laboratories AS l ON l.lab_id = w.lab_id
LEFT JOIN installed_components AS ic
       ON ic.lab_id = w.lab_id AND ic.workstation_no = w.workstation_no
      AND ic.removed_at IS NULL
LEFT JOIN components AS c ON c.component_id = ic.component_id
ORDER BY l.lab_name, w.workstation_no, c.component_name;
 
-- B3. Self-style join on users: complaint, reporter and assignee
SELECT c.complaint_id,
       rep.first_name AS reported_by,
       asg.first_name AS assigned_to,
       c.status
FROM complaints AS c
JOIN users AS rep ON rep.user_id = c.user_id
LEFT JOIN users AS asg ON asg.user_id = c.assigned_to;
 
-- B4. Multi-table join: complaint with its maintenance record
SELECT c.complaint_id, l.lab_name, c.workstation_no,
       c.problem_description, m.maintenance_type, m.status AS maintenance_status
FROM complaints AS c
JOIN laboratories AS l ON l.lab_id = c.lab_id
LEFT JOIN maintenance AS m ON m.complaint_id = c.complaint_id;
 
-- B5. Software installed per workstation
SELECT l.lab_name, ws.workstation_no, sp.software_name, sp.version
FROM workstation_software AS ws
JOIN laboratories AS l ON l.lab_id = ws.lab_id
JOIN software_packages AS sp ON sp.software_id = ws.software_id
ORDER BY l.lab_name, ws.workstation_no;
 
-- ---------------- C. AGGREGATES, GROUP BY, HAVING ----------------
 
-- C1. Users per role
SELECT role, COUNT(*) AS total_users
FROM users
GROUP BY role
ORDER BY total_users DESC;
 
-- C2. Workstations per lab
SELECT l.lab_name, COUNT(w.workstation_no) AS workstation_count
FROM laboratories AS l
LEFT JOIN workstations AS w ON w.lab_id = l.lab_id
GROUP BY l.lab_name
ORDER BY workstation_count DESC;
 
-- C3. Labs with more than one complaint (HAVING)
SELECT l.lab_name, COUNT(*) AS complaint_count
FROM complaints AS c
JOIN laboratories AS l ON l.lab_id = c.lab_id
GROUP BY l.lab_name
HAVING COUNT(*) > 1;
 
-- C4. Workstations currently holding 4 or more components (HAVING)
SELECT lab_id, workstation_no, COUNT(*) AS component_count
FROM installed_components
WHERE removed_at IS NULL
GROUP BY lab_id, workstation_no
HAVING COUNT(*) >= 4;
 
-- C5. Capacity statistics
SELECT COUNT(*) AS labs,
       SUM(capacity) AS total_capacity,
       AVG(capacity)::NUMERIC(6,2) AS average_capacity,
       MIN(capacity) AS smallest_lab,
       MAX(capacity) AS largest_lab
FROM laboratories;
 
-- C6. Bookings per status
SELECT status, COUNT(*) AS booking_count
FROM bookings
GROUP BY status;
 
-- C7. Complaints per assigned lab assistant
SELECT u.first_name, u.last_name, COUNT(*) AS assigned_complaints
FROM complaints AS c
JOIN users AS u ON u.user_id = c.assigned_to
GROUP BY u.user_id, u.first_name, u.last_name
ORDER BY assigned_complaints DESC;
 
-- ---------------- D. DATE AND TIME FUNCTIONS ----------------
 
-- D1. Booking duration in hours
SELECT booking_id, purpose,
       EXTRACT(EPOCH FROM (end_time - start_time)) / 3600 AS duration_hours
FROM bookings;
 
-- D2. Complaint resolution time (resolved complaints only)
SELECT complaint_id, raised_at, resolved_at,
       resolved_at - raised_at AS time_to_resolve
FROM complaints
WHERE resolved_at IS NOT NULL;
 
-- D3. Complaints raised per month
SELECT DATE_TRUNC('month', raised_at) AS month, COUNT(*) AS complaints
FROM complaints
GROUP BY DATE_TRUNC('month', raised_at)
ORDER BY month;
 
-- D4. Bookings on a given date
SELECT booking_id, purpose, start_time
FROM bookings
WHERE start_time::DATE = DATE '2026-10-06';
 
-- D5. Licenses expiring within the next 365 days from a fixed date
SELECT software_name, version, license_expiry,
       license_expiry - DATE '2026-10-06' AS days_remaining
FROM software_packages
WHERE license_expiry IS NOT NULL
  AND license_expiry <= DATE '2026-10-06' + INTERVAL '365 days';
 
-- D6. Age of each unresolved complaint as of a fixed date
SELECT complaint_id, status,
       AGE(TIMESTAMPTZ '2026-10-06 12:00:00+05:30', raised_at) AS complaint_age
FROM complaints
WHERE status IN ('Open', 'In Progress');
 
-- ---------------- E. IN, NOT IN, EXISTS, NOT EXISTS ----------------
 
-- E1. IN: staff accounts
SELECT first_name, last_name, role
FROM users
WHERE role IN ('Faculty', 'Lab Assistant');
 
-- E2. IN with subquery: users who have raised a complaint
SELECT first_name, last_name
FROM users
WHERE user_id IN (SELECT user_id FROM complaints);
 
-- E3. NOT IN: components not currently installed anywhere
SELECT component_name, serial_number
FROM components
WHERE component_id NOT IN (
    SELECT component_id FROM installed_components WHERE removed_at IS NULL
);
 
-- E4. EXISTS: labs having at least one unresolved complaint
SELECT l.lab_name
FROM laboratories AS l
WHERE EXISTS (
    SELECT 1 FROM complaints AS c
    WHERE c.lab_id = l.lab_id AND c.status IN ('Open', 'In Progress')
);
 
-- E5. NOT EXISTS: workstations with no current component
SELECT w.lab_id, w.workstation_no
FROM workstations AS w
WHERE NOT EXISTS (
    SELECT 1 FROM installed_components AS ic
    WHERE ic.lab_id = w.lab_id AND ic.workstation_no = w.workstation_no
      AND ic.removed_at IS NULL
);
 
-- E6. NOT EXISTS: complaints that have no maintenance record
SELECT c.complaint_id, c.problem_description
FROM complaints AS c
WHERE NOT EXISTS (
    SELECT 1 FROM maintenance AS m WHERE m.complaint_id = c.complaint_id
);
 
-- ---------------- F. SET OPERATORS ----------------
 
-- F1. UNION: every user who raised a complaint OR made a booking
SELECT user_id FROM complaints
UNION
SELECT user_id FROM bookings;
 
-- F2. INTERSECT: users who both raised a complaint AND made a booking
SELECT user_id FROM complaints
INTERSECT
SELECT user_id FROM bookings;
 
-- F3. EXCEPT: labs that have workstations but no bookings
SELECT lab_id FROM workstations
EXCEPT
SELECT lab_id FROM bookings;
 
-- F4. UNION ALL: all lab-related activity with its source
SELECT lab_id, 'Booking' AS activity FROM bookings
UNION ALL
SELECT lab_id, 'Complaint' FROM complaints
UNION ALL
SELECT lab_id, 'Maintenance' FROM maintenance
ORDER BY lab_id, activity;
 
-- ---------------- G. SUBQUERIES AND VIEW USAGE ----------------
 
-- G1. Lab with the largest capacity
SELECT lab_name, capacity
FROM laboratories
WHERE capacity = (SELECT MAX(capacity) FROM laboratories);
 
-- G2. Labs with above-average capacity
SELECT lab_name, capacity
FROM laboratories
WHERE capacity > (SELECT AVG(capacity) FROM laboratories);
 
-- G3. Views
SELECT * FROM v_lab_summary;
SELECT * FROM v_open_complaints;
SELECT * FROM v_booking_details;
SELECT * FROM v_workstation_inventory WHERE lab_name = 'Database Laboratory';