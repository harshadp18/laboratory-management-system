-- ============================================================================
-- PostgreSQL Views for Laboratory Management System
-- Demonstrating multi-table JOINs, conditional aggregations, and query optimization.
-- ============================================================================

-- View 1: Laboratory and workstation availability summary
CREATE OR REPLACE VIEW v_lab_workstation_status AS
SELECT 
    l.lab_id,
    l.lab_name,
    l.capacity,
    COUNT(w.workstation_id) AS total_workstations,
    COUNT(CASE WHEN w.status = 'Working' THEN 1 END) AS working_count,
    COUNT(CASE WHEN w.status = 'Under Repair' THEN 1 END) AS under_repair_count
FROM laboratories l
LEFT JOIN workstations w ON l.lab_id = w.lab_id
GROUP BY l.lab_id, l.lab_name, l.capacity;

-- View 2: Open complaints assigned to lab assistants for rapid triage
CREATE OR REPLACE VIEW v_open_complaints AS
SELECT 
    c.complaint_id,
    c.complaint_code,
    c.title,
    w.workstation_code,
    l.lab_name,
    u_student.full_name AS student_name,
    u_assistant.full_name AS assigned_assistant,
    c.status,
    c.raised_at
FROM complaints c
JOIN workstations w ON c.workstation_id = w.workstation_id
JOIN laboratories l ON w.lab_id = l.lab_id
JOIN users u_student ON c.student_id = u_student.user_id
LEFT JOIN users u_assistant ON c.assigned_to = u_assistant.user_id
WHERE c.status <> 'Resolved';

-- View 3: Faculty booking schedule and approval summary
CREATE OR REPLACE VIEW v_booking_summary AS
SELECT 
    b.booking_id,
    b.booking_code,
    u.full_name AS faculty_name,
    l.lab_name,
    b.session_date,
    b.start_time,
    b.end_time,
    b.purpose,
    b.status
FROM bookings b
JOIN users u ON b.faculty_id = u.user_id
JOIN laboratories l ON b.lab_id = l.lab_id
ORDER BY b.session_date DESC, b.start_time ASC;
