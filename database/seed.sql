BEGIN;
 
-- 1. USERS and role-specific rows
INSERT INTO users (first_name, last_name, email, password_hash, role) VALUES
    ('Sanjay', 'Kulkarni', 'sanjay.kulkarni@vit.edu.in', '$2y$demo$admin', 'Administrator'),
    ('Neha', 'Rao', 'neha.rao@vit.edu.in', '$2y$demo$faculty1', 'Faculty'),
    ('Arjun', 'Mehta', 'arjun.mehta@vit.edu.in', '$2y$demo$faculty2', 'Faculty'),
    ('Aditi', 'Shah', 'aditi.shah@vit.edu.in', '$2y$demo$assistant1', 'Lab Assistant'),
    ('Rahul', 'Verma', 'rahul.verma@vit.edu.in', '$2y$demo$assistant2', 'Lab Assistant'),
    ('Riya', 'Patel', 'riya.patel@vit.edu.in', '$2y$demo$student1', 'Student'),
    ('Karan', 'Joshi', 'karan.joshi@vit.edu.in', '$2y$demo$student2', 'Student');
 
INSERT INTO administrators (user_id, access_level)
SELECT user_id, 'Full Access'
FROM users
WHERE email = 'sanjay.kulkarni@vit.edu.in';
 
INSERT INTO faculty (user_id, department)
SELECT user_id, 'Computer Engineering'
FROM users
WHERE email IN ('neha.rao@vit.edu.in', 'arjun.mehta@vit.edu.in');
 
INSERT INTO lab_assistants (user_id, shift_timing)
SELECT user_id,
       CASE email
           WHEN 'aditi.shah@vit.edu.in' THEN '09:00 - 17:00'
           ELSE '11:00 - 19:00'
       END
FROM users
WHERE email IN ('aditi.shah@vit.edu.in', 'rahul.verma@vit.edu.in');
 
INSERT INTO students (user_id, roll_no, year_of_study)
SELECT user_id,
       CASE email
           WHEN 'riya.patel@vit.edu.in' THEN '25102B0091'
           ELSE '25102B0092'
       END,
       2
FROM users
WHERE email IN ('riya.patel@vit.edu.in', 'karan.joshi@vit.edu.in');
 
INSERT INTO user_phone_numbers (user_id, phone_number)
SELECT user_id, phone_number
FROM users
JOIN (VALUES
    ('sanjay.kulkarni@vit.edu.in', '+91-9000000001'),
    ('neha.rao@vit.edu.in', '+91-9000000002'),
    ('arjun.mehta@vit.edu.in', '+91-9000000003'),
    ('aditi.shah@vit.edu.in', '+91-9000000004'),
    ('rahul.verma@vit.edu.in', '+91-9000000005'),
    ('riya.patel@vit.edu.in', '+91-9000000006'),
    ('riya.patel@vit.edu.in', '+91-9000000016'),
    ('karan.joshi@vit.edu.in', '+91-9000000007')
) AS phone_data(email, phone_number) USING (email);
 
-- 2. LABORATORIES and WORKSTATIONS
INSERT INTO laboratories (lab_name, building, floor, room_no, capacity) VALUES
    ('Database Laboratory', 'Main Building', '2', '201', 30),
    ('Networking Laboratory', 'Main Building', '2', '202', 24),
    ('Programming Laboratory', 'South Wing', '3', '301', 20);
 
INSERT INTO workstations (lab_id, workstation_no)
SELECT l.lab_id, workstation_data.workstation_no
FROM laboratories AS l
JOIN (VALUES
    ('Database Laboratory', 'PC-01'),
    ('Database Laboratory', 'PC-02'),
    ('Database Laboratory', 'PC-03'),
    ('Database Laboratory', 'PC-04'),
    ('Networking Laboratory', 'PC-01'),
    ('Networking Laboratory', 'PC-02'),
    ('Networking Laboratory', 'PC-03'),
    ('Programming Laboratory', 'PC-01'),
    ('Programming Laboratory', 'PC-02'),
    ('Programming Laboratory', 'PC-03')
) AS workstation_data(lab_name, workstation_no)
ON l.lab_name = workstation_data.lab_name;
 
-- 3. COMPONENTS and INSTALLED_COMPONENTS
INSERT INTO components (component_name, category, brand, model, serial_number) VALUES
    ('Processor', 'Processor', 'Intel', 'Core i5-12400', 'CPU-DB-01'),
    ('RAM Module', 'RAM', 'Kingston', '16 GB DDR4', 'RAM-DB-01'),
    ('SSD', 'Storage', 'Samsung', '970 EVO Plus 512 GB', 'SSD-DB-01'),
    ('Monitor', 'Monitor', 'Dell', 'P2422H', 'MON-DB-01'),
    ('Keyboard', 'Keyboard', 'Logitech', 'K120', 'KEY-DB-01'),
    ('Mouse', 'Mouse', 'Logitech', 'B100', 'MOUSE-DB-01'),
    ('Processor', 'Processor', 'Intel', 'Core i5-12400', 'CPU-DB-02'),
    ('RAM Module', 'RAM', 'Kingston', '16 GB DDR4', 'RAM-DB-02'),
    ('SSD', 'Storage', 'Samsung', '970 EVO Plus 512 GB', 'SSD-DB-02'),
    ('Monitor', 'Monitor', 'Dell', 'P2422H', 'MON-DB-02'),
    ('Network Card', 'Network Device', 'Intel', 'I219-V Ethernet', 'NET-NW-01'),
    ('Webcam', 'Peripheral', 'Logitech', 'C270', 'CAM-PR-01');
 
INSERT INTO installed_components (lab_id, workstation_no, component_id, installed_at)
SELECT l.lab_id, installation.workstation_no, c.component_id, installation.installed_at
FROM (VALUES
    ('Database Laboratory', 'PC-01', 'CPU-DB-01',  '2026-08-01 09:00:00+05:30'::TIMESTAMPTZ),
    ('Database Laboratory', 'PC-01', 'RAM-DB-01',  '2026-08-01 09:00:00+05:30'::TIMESTAMPTZ),
    ('Database Laboratory', 'PC-01', 'SSD-DB-01',  '2026-08-01 09:00:00+05:30'::TIMESTAMPTZ),
    ('Database Laboratory', 'PC-01', 'MON-DB-01',  '2026-08-01 09:00:00+05:30'::TIMESTAMPTZ),
    ('Database Laboratory', 'PC-01', 'KEY-DB-01',  '2026-08-01 09:00:00+05:30'::TIMESTAMPTZ),
    ('Database Laboratory', 'PC-01', 'MOUSE-DB-01','2026-08-01 09:00:00+05:30'::TIMESTAMPTZ),
    ('Database Laboratory', 'PC-02', 'CPU-DB-02',  '2026-08-01 09:30:00+05:30'::TIMESTAMPTZ),
    ('Database Laboratory', 'PC-02', 'RAM-DB-02',  '2026-08-01 09:30:00+05:30'::TIMESTAMPTZ),
    ('Database Laboratory', 'PC-02', 'SSD-DB-02',  '2026-08-01 09:30:00+05:30'::TIMESTAMPTZ),
    ('Database Laboratory', 'PC-02', 'MON-DB-02',  '2026-08-01 09:30:00+05:30'::TIMESTAMPTZ),
    ('Networking Laboratory', 'PC-01', 'NET-NW-01','2026-08-02 10:00:00+05:30'::TIMESTAMPTZ),
    ('Programming Laboratory', 'PC-01', 'CAM-PR-01','2026-08-03 10:00:00+05:30'::TIMESTAMPTZ)
) AS installation(lab_name, workstation_no, serial_number, installed_at)
JOIN laboratories AS l ON l.lab_name = installation.lab_name
JOIN components AS c ON c.serial_number = installation.serial_number;
 
-- 4. BOOKINGS
INSERT INTO bookings
    (user_id, lab_id, approved_by, purpose, start_time, end_time, status)
VALUES
    (
        (SELECT user_id FROM users WHERE email = 'neha.rao@vit.edu.in'),
        (SELECT lab_id FROM laboratories WHERE lab_name = 'Database Laboratory'),
        (SELECT user_id FROM users WHERE email = 'aditi.shah@vit.edu.in'),
        'DBMS practical session',
        '2026-10-06 10:00:00+05:30',
        '2026-10-06 12:00:00+05:30',
        'Approved'
    ),
    (
        (SELECT user_id FROM users WHERE email = 'arjun.mehta@vit.edu.in'),
        (SELECT lab_id FROM laboratories WHERE lab_name = 'Networking Laboratory'),
        NULL,
        'Computer Networks workshop',
        '2026-10-07 13:00:00+05:30',
        '2026-10-07 15:00:00+05:30',
        'Pending'
    ),
    (
        (SELECT user_id FROM users WHERE email = 'neha.rao@vit.edu.in'),
        (SELECT lab_id FROM laboratories WHERE lab_name = 'Programming Laboratory'),
        (SELECT user_id FROM users WHERE email = 'rahul.verma@vit.edu.in'),
        'Python programming practical',
        '2026-10-02 09:00:00+05:30',
        '2026-10-02 11:00:00+05:30',
        'Completed'
    );
 
-- 5. COMPLAINTS and MAINTENANCE
INSERT INTO complaints
    (user_id, lab_id, workstation_no, problem_description, assigned_to,
     escalation_reason, status, raised_at, resolved_at)
VALUES
    (
        (SELECT user_id FROM users WHERE email = 'riya.patel@vit.edu.in'),
        (SELECT lab_id FROM laboratories WHERE lab_name = 'Database Laboratory'),
        'PC-02',
        'Monitor flickers intermittently while the workstation is in use.',
        (SELECT user_id FROM users WHERE email = 'aditi.shah@vit.edu.in'),
        NULL,
        'Open',
        '2026-10-05 10:20:00+05:30',
        NULL
    ),
    (
        (SELECT user_id FROM users WHERE email = 'karan.joshi@vit.edu.in'),
        (SELECT lab_id FROM laboratories WHERE lab_name = 'Networking Laboratory'),
        'PC-01',
        'Network connection is unavailable on this workstation.',
        (SELECT user_id FROM users WHERE email = 'rahul.verma@vit.edu.in'),
        'Network fault requires administrator review.',
        'In Progress',
        '2026-10-05 09:15:00+05:30',
        NULL
    ),
    (
        (SELECT user_id FROM users WHERE email = 'riya.patel@vit.edu.in'),
        (SELECT lab_id FROM laboratories WHERE lab_name = 'Database Laboratory'),
        'PC-01',
        'Mouse pointer freezes during use.',
        (SELECT user_id FROM users WHERE email = 'aditi.shah@vit.edu.in'),
        NULL,
        'Resolved',
        '2026-10-02 14:10:00+05:30',
        '2026-10-02 15:25:00+05:30'
    ),
    (
        (SELECT user_id FROM users WHERE email = 'arjun.mehta@vit.edu.in'),
        (SELECT lab_id FROM laboratories WHERE lab_name = 'Programming Laboratory'),
        NULL,
        'The laboratory projector is not receiving an input signal.',
        (SELECT user_id FROM users WHERE email = 'aditi.shah@vit.edu.in'),
        NULL,
        'Open',
        '2026-10-04 11:30:00+05:30',
        NULL
    );
 
INSERT INTO maintenance
    (complaint_id, lab_id, workstation_no, performed_by, maintenance_type,
     description, start_time, end_time, status)
VALUES
    (
        (SELECT complaint_id FROM complaints
         WHERE problem_description = 'Monitor flickers intermittently while the workstation is in use.'),
        (SELECT lab_id FROM laboratories WHERE lab_name = 'Database Laboratory'),
        'PC-02',
        (SELECT user_id FROM users WHERE email = 'aditi.shah@vit.edu.in'),
        'Corrective',
        'Inspect monitor cable and display output.',
        '2026-10-05 10:45:00+05:30',
        NULL,
        'Scheduled'
    ),
    (
        (SELECT complaint_id FROM complaints
         WHERE problem_description = 'Network connection is unavailable on this workstation.'),
        (SELECT lab_id FROM laboratories WHERE lab_name = 'Networking Laboratory'),
        'PC-01',
        (SELECT user_id FROM users WHERE email = 'rahul.verma@vit.edu.in'),
        'Corrective',
        'Run network diagnostics and check switch port.',
        '2026-10-05 10:00:00+05:30',
        NULL,
        'In Progress'
    ),
    (
        (SELECT complaint_id FROM complaints
         WHERE problem_description = 'Mouse pointer freezes during use.'),
        (SELECT lab_id FROM laboratories WHERE lab_name = 'Database Laboratory'),
        'PC-01',
        (SELECT user_id FROM users WHERE email = 'aditi.shah@vit.edu.in'),
        'Corrective',
        'Replace faulty mouse and verify pointer movement.',
        '2026-10-02 14:30:00+05:30',
        '2026-10-02 15:25:00+05:30',
        'Completed'
    ),
    (
        NULL,
        (SELECT lab_id FROM laboratories WHERE lab_name = 'Programming Laboratory'),
        'PC-02',
        (SELECT user_id FROM users WHERE email = 'rahul.verma@vit.edu.in'),
        'Preventive',
        'Clean ports, inspect fans, and verify workstation health.',
        '2026-10-06 09:00:00+05:30',
        NULL,
        'Scheduled'
    );
 
-- 6. SOFTWARE_PACKAGES and WORKSTATION_SOFTWARE
INSERT INTO software_packages
    (software_name, version, license_type, license_expiry)
VALUES
    ('PostgreSQL', '17', 'Open Source', NULL),
    ('Visual Studio Code', '1.95', 'Open Source', NULL),
    ('LibreOffice', '24.8', 'Open Source', NULL),
    ('Microsoft Office', '2024', 'Institutional', '2027-06-30'),
    ('Cisco Packet Tracer', '8.2', 'Institutional', '2027-06-30');
 
INSERT INTO workstation_software
    (lab_id, workstation_no, software_id, installed_at)
SELECT l.lab_id, install_data.workstation_no, s.software_id, install_data.installed_at
FROM (VALUES
    ('Database Laboratory',   'PC-01', 'PostgreSQL',           '17',   '2026-08-01 09:00:00+05:30'::TIMESTAMPTZ),
    ('Database Laboratory',   'PC-01', 'Visual Studio Code',   '1.95', '2026-08-01 09:00:00+05:30'::TIMESTAMPTZ),
    ('Database Laboratory',   'PC-02', 'PostgreSQL',           '17',   '2026-08-01 09:30:00+05:30'::TIMESTAMPTZ),
    ('Database Laboratory',   'PC-02', 'LibreOffice',          '24.8', '2026-08-01 09:30:00+05:30'::TIMESTAMPTZ),
    ('Networking Laboratory', 'PC-01', 'Cisco Packet Tracer',  '8.2',  '2026-08-02 10:00:00+05:30'::TIMESTAMPTZ),
    ('Networking Laboratory', 'PC-02', 'Visual Studio Code',   '1.95', '2026-08-02 10:00:00+05:30'::TIMESTAMPTZ),
    ('Programming Laboratory','PC-01', 'Visual Studio Code',   '1.95', '2026-08-03 10:00:00+05:30'::TIMESTAMPTZ),
    ('Programming Laboratory','PC-01', 'Microsoft Office',     '2024', '2026-08-03 10:00:00+05:30'::TIMESTAMPTZ)
) AS install_data(lab_name, workstation_no, software_name, version, installed_at)
JOIN laboratories AS l ON l.lab_name = install_data.lab_name
JOIN software_packages AS s
  ON s.software_name = install_data.software_name
 AND s.version = install_data.version;
 
COMMIT;