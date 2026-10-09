CREATE EXTENSION IF NOT EXISTS btree_gist;

-- 1. users
CREATE TABLE users (
    user_id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT valid_user_role
        CHECK (role IN ('Administrator', 'Faculty', 'Lab Assistant', 'Student'))
);

-- 2. administrators
CREATE TABLE administrators (
    user_id INTEGER PRIMARY KEY
        REFERENCES users(user_id) ON DELETE CASCADE,
    access_level VARCHAR(30) NOT NULL
);

-- 3. faculty
CREATE TABLE faculty (
    user_id INTEGER PRIMARY KEY
        REFERENCES users(user_id) ON DELETE CASCADE,
    department VARCHAR(100) NOT NULL
);

-- 4. lab_assistants
CREATE TABLE lab_assistants (
    user_id INTEGER PRIMARY KEY
        REFERENCES users(user_id) ON DELETE CASCADE,
    shift_timing VARCHAR(50)
);

-- 5. students
CREATE TABLE students (
    user_id INTEGER PRIMARY KEY
        REFERENCES users(user_id) ON DELETE CASCADE,
    roll_no VARCHAR(20) NOT NULL UNIQUE,
    year_of_study INTEGER NOT NULL
        CHECK (year_of_study BETWEEN 1 AND 4)
);

-- 6. laboratories
CREATE TABLE laboratories (
    lab_id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    lab_name VARCHAR(100) NOT NULL UNIQUE,
    building VARCHAR(100) NOT NULL,
    floor VARCHAR(20) NOT NULL,
    room_no VARCHAR(20) NOT NULL,
    capacity INTEGER NOT NULL CHECK (capacity > 0),
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT unique_lab_location UNIQUE (building, floor, room_no)
);

-- 7. workstations (number is unique only within its laboratory)
CREATE TABLE workstations (
    lab_id INTEGER NOT NULL,
    workstation_no VARCHAR(20) NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT pk_workstations PRIMARY KEY (lab_id, workstation_no),

    CONSTRAINT fk_workstation_lab
        FOREIGN KEY (lab_id)
        REFERENCES laboratories(lab_id)
        ON DELETE RESTRICT
);

-- 8. components
CREATE TABLE components (
    component_id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    component_name VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL,
    brand VARCHAR(100),
    model VARCHAR(100),
    serial_number VARCHAR(100) UNIQUE,

    CONSTRAINT valid_component_category
        CHECK (category IN
            ('Processor', 'RAM', 'Storage', 'Monitor',
             'Keyboard', 'Mouse', 'Network Device',
             'Peripheral', 'Other'))
);

-- 9. installed_components (installation history)
CREATE TABLE installed_components (
    installed_component_id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    lab_id INTEGER NOT NULL,
    workstation_no VARCHAR(20) NOT NULL,
    component_id INTEGER NOT NULL,
    installed_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    removed_at TIMESTAMPTZ,

    CONSTRAINT valid_component_dates
        CHECK (removed_at IS NULL OR removed_at > installed_at),

    CONSTRAINT fk_installed_component_workstation
        FOREIGN KEY (lab_id, workstation_no)
        REFERENCES workstations(lab_id, workstation_no)
        ON DELETE CASCADE,

    CONSTRAINT fk_installed_component_component
        FOREIGN KEY (component_id)
        REFERENCES components(component_id)
        ON DELETE RESTRICT
);

-- A physical component can be actively installed in only one workstation.
CREATE UNIQUE INDEX uq_component_active_install
ON installed_components(component_id)
WHERE removed_at IS NULL;

-- 10. bookings
CREATE TABLE bookings (
    booking_id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    user_id INTEGER NOT NULL,
    lab_id INTEGER NOT NULL,
    approved_by INTEGER,
    purpose TEXT NOT NULL,
    start_time TIMESTAMPTZ NOT NULL,
    end_time TIMESTAMPTZ NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT valid_booking_time
        CHECK (end_time > start_time),

    CONSTRAINT valid_booking_status
        CHECK (status IN ('Pending', 'Approved', 'Rejected', 'Completed')),

    CONSTRAINT fk_booking_faculty
        FOREIGN KEY (user_id)
        REFERENCES faculty(user_id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_booking_lab
        FOREIGN KEY (lab_id)
        REFERENCES laboratories(lab_id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_booking_approver
        FOREIGN KEY (approved_by)
        REFERENCES lab_assistants(user_id)
        ON DELETE SET NULL
);

ALTER TABLE bookings
ADD CONSTRAINT no_overlapping_approved_lab_bookings
EXCLUDE USING gist (
    lab_id WITH =,
    tstzrange(start_time, end_time, '[)') WITH &&
)
WHERE (status = 'Approved');

-- 11. complaints
CREATE TABLE complaints (
    complaint_id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    user_id INTEGER NOT NULL,
    lab_id INTEGER NOT NULL,
    workstation_no VARCHAR(20),
    problem_description TEXT NOT NULL,
    assigned_to INTEGER,
    escalation_reason TEXT,
    status VARCHAR(20) NOT NULL DEFAULT 'Open',
    raised_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMPTZ,

    CONSTRAINT valid_complaint_status
        CHECK (status IN ('Open', 'In Progress', 'Resolved')),

    CONSTRAINT valid_complaint_dates
        CHECK (resolved_at IS NULL OR resolved_at >= raised_at),

    CONSTRAINT fk_complaint_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_complaint_lab
        FOREIGN KEY (lab_id)
        REFERENCES laboratories(lab_id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_complaint_workstation
        FOREIGN KEY (lab_id, workstation_no)
        REFERENCES workstations(lab_id, workstation_no)
        ON DELETE SET NULL (workstation_no),

    CONSTRAINT fk_complaint_assignee
        FOREIGN KEY (assigned_to)
        REFERENCES lab_assistants(user_id)
        ON DELETE SET NULL
);

-- 12. maintenance
CREATE TABLE maintenance (
    maintenance_id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    complaint_id INTEGER,
    lab_id INTEGER NOT NULL,
    workstation_no VARCHAR(20) NOT NULL,
    performed_by INTEGER NOT NULL,
    maintenance_type VARCHAR(20) NOT NULL,
    description TEXT NOT NULL,
    start_time TIMESTAMPTZ NOT NULL,
    end_time TIMESTAMPTZ,
    status VARCHAR(20) NOT NULL DEFAULT 'Scheduled',

    CONSTRAINT valid_maintenance_type
        CHECK (maintenance_type IN ('Preventive', 'Corrective')),

    CONSTRAINT valid_maintenance_status
        CHECK (status IN ('Scheduled', 'In Progress', 'Completed')),

    CONSTRAINT valid_maintenance_dates
        CHECK (end_time IS NULL OR end_time > start_time),

    CONSTRAINT fk_maintenance_complaint
        FOREIGN KEY (complaint_id)
        REFERENCES complaints(complaint_id)
        ON DELETE SET NULL,

    CONSTRAINT fk_maintenance_workstation
        FOREIGN KEY (lab_id, workstation_no)
        REFERENCES workstations(lab_id, workstation_no)
        ON DELETE CASCADE,

    CONSTRAINT fk_maintenance_assistant
        FOREIGN KEY (performed_by)
        REFERENCES lab_assistants(user_id)
        ON DELETE RESTRICT
);

-- 13. user_phone_numbers (multivalued attribute)
CREATE TABLE user_phone_numbers (
    user_id INTEGER NOT NULL,
    phone_number VARCHAR(20) NOT NULL,

    CONSTRAINT pk_user_phone_numbers
        PRIMARY KEY (user_id, phone_number),

    CONSTRAINT fk_phone_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE
);

-- 14. software_packages (approved extension)
CREATE TABLE software_packages (
    software_id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    software_name VARCHAR(100) NOT NULL,
    version VARCHAR(50) NOT NULL,
    license_type VARCHAR(30) NOT NULL,
    license_expiry DATE,

    CONSTRAINT valid_software_license_type CHECK
        (license_type IN ('Open Source', 'Institutional', 'Commercial')),
    CONSTRAINT unique_software_version UNIQUE (software_name, version)
);

-- 15. workstation_software (approved extension)
CREATE TABLE workstation_software (
    lab_id INTEGER NOT NULL,
    workstation_no VARCHAR(20) NOT NULL,
    software_id INTEGER NOT NULL,
    installed_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT pk_workstation_software
        PRIMARY KEY (lab_id, workstation_no, software_id),
    CONSTRAINT fk_workstation_software_workstation
        FOREIGN KEY (lab_id, workstation_no)
        REFERENCES workstations (lab_id, workstation_no)
        ON DELETE CASCADE,
    CONSTRAINT fk_workstation_software_package
        FOREIGN KEY (software_id)
        REFERENCES software_packages (software_id)
        ON DELETE RESTRICT
);