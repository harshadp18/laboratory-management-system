# Laboratory Management System: ER Diagram and Relational Schema

This document describes the PostgreSQL database implemented in `schema.sql`.
The Mermaid diagram can be rendered by VS Code extensions such as Mermaid
Markdown Syntax Highlighting/Preview, or on GitHub.

## Entity relationship diagram

```mermaid
erDiagram
    USERS ||--o| ADMINISTRATORS : "has details"
    USERS ||--o| FACULTY : "has details"
    USERS ||--o| LAB_ASSISTANTS : "has details"
    USERS ||--o| STUDENTS : "has details"
    USERS ||--o{ USER_PHONE_NUMBERS : has

    LABORATORIES ||--o{ WORKSTATIONS : contains
    WORKSTATIONS ||--o{ INSTALLED_COMPONENTS : has
    COMPONENTS ||--o{ INSTALLED_COMPONENTS : installed

    FACULTY ||--o{ BOOKINGS : requests
    LAB_ASSISTANTS ||--o{ BOOKINGS : approves
    LABORATORIES ||--o{ BOOKINGS : receives

    STUDENTS ||--o{ COMPLAINTS : submits
    LABORATORIES ||--o{ COMPLAINTS : concerns
    WORKSTATIONS ||--o{ COMPLAINTS : concerns
    LAB_ASSISTANTS ||--o{ COMPLAINTS : assigned

    COMPLAINTS o|--o{ MAINTENANCE : "may create"
    LABORATORIES ||--o{ MAINTENANCE : has
    WORKSTATIONS ||--o{ MAINTENANCE : serviced
    LAB_ASSISTANTS ||--o{ MAINTENANCE : performs

    WORKSTATIONS ||--o{ WORKSTATION_SOFTWARE : runs
    SOFTWARE_PACKAGES ||--o{ WORKSTATION_SOFTWARE : installed

    USERS {
        int user_id PK
        varchar first_name
        varchar last_name
        varchar email UK
        varchar password_hash
        varchar role
    }
    ADMINISTRATORS {
        int user_id PK,FK
        varchar access_level
    }
    FACULTY {
        int user_id PK,FK
        varchar department
    }
    LAB_ASSISTANTS {
        int user_id PK,FK
        varchar shift_timing
    }
    STUDENTS {
        int user_id PK,FK
        varchar roll_no UK
        int year_of_study
    }
    LABORATORIES {
        int lab_id PK
        varchar lab_name UK
        varchar building
        varchar floor
        varchar room_no
        int capacity
    }
    WORKSTATIONS {
        int lab_id PK,FK
        varchar workstation_no PK
    }
    COMPONENTS {
        int component_id PK
        varchar component_name
        varchar category
        varchar serial_number UK
    }
    INSTALLED_COMPONENTS {
        int installed_component_id PK
        int lab_id FK
        varchar workstation_no FK
        int component_id FK
        timestamptz installed_at
        timestamptz removed_at
    }
    BOOKINGS {
        int booking_id PK
        int user_id FK
        int lab_id FK
        int approved_by FK
        text purpose
        timestamptz start_time
        timestamptz end_time
        varchar status
    }
    COMPLAINTS {
        int complaint_id PK
        int user_id FK
        int lab_id FK
        varchar workstation_no FK
        int assigned_to FK
        text problem_description
        varchar status
        timestamptz raised_at
        timestamptz resolved_at
    }
    MAINTENANCE {
        int maintenance_id PK
        int complaint_id FK
        int lab_id FK
        varchar workstation_no FK
        int performed_by FK
        varchar maintenance_type
        text description
        timestamptz start_time
        timestamptz end_time
        varchar status
    }
    USER_PHONE_NUMBERS {
        int user_id PK,FK
        varchar phone_number PK
    }
    SOFTWARE_PACKAGES {
        int software_id PK
        varchar software_name
        varchar version
        varchar license_type
        date license_expiry
    }
    WORKSTATION_SOFTWARE {
        int lab_id PK,FK
        varchar workstation_no PK,FK
        int software_id PK,FK
        timestamptz installed_at
    }
```

## Relational schema

Primary keys are marked `PK`; foreign keys are marked `FK`.

```text
USERS(
  user_id PK, first_name, last_name, email UNIQUE, password_hash, role,
  created_at, updated_at
)

ADMINISTRATORS(user_id PK/FK -> USERS, access_level)
FACULTY(user_id PK/FK -> USERS, department)
LAB_ASSISTANTS(user_id PK/FK -> USERS, shift_timing)
STUDENTS(user_id PK/FK -> USERS, roll_no UNIQUE, year_of_study)

LABORATORIES(
  lab_id PK, lab_name UNIQUE, building, floor, room_no, capacity,
  UNIQUE(building, floor, room_no)
)

WORKSTATIONS(
  lab_id PK/FK -> LABORATORIES,
  workstation_no PK
)

COMPONENTS(
  component_id PK, component_name, category, brand, model,
  serial_number UNIQUE
)

INSTALLED_COMPONENTS(
  installed_component_id PK,
  (lab_id, workstation_no) FK -> WORKSTATIONS,
  component_id FK -> COMPONENTS,
  installed_at, removed_at
)

BOOKINGS(
  booking_id PK,
  user_id FK -> FACULTY,
  lab_id FK -> LABORATORIES,
  approved_by FK -> LAB_ASSISTANTS,
  purpose, start_time, end_time, status, created_at, updated_at
)

COMPLAINTS(
  complaint_id PK,
  user_id FK -> USERS,
  lab_id FK -> LABORATORIES,
  (lab_id, workstation_no) FK -> WORKSTATIONS,
  assigned_to FK -> LAB_ASSISTANTS,
  problem_description, escalation_reason, status, raised_at, resolved_at
)

MAINTENANCE(
  maintenance_id PK,
  complaint_id FK -> COMPLAINTS,
  lab_id FK -> LABORATORIES,
  (lab_id, workstation_no) FK -> WORKSTATIONS,
  performed_by FK -> LAB_ASSISTANTS,
  maintenance_type, description, start_time, end_time, status
)

USER_PHONE_NUMBERS(
  user_id PK/FK -> USERS,
  phone_number PK
)

SOFTWARE_PACKAGES(
  software_id PK, software_name, version, license_type, license_expiry,
  UNIQUE(software_name, version)
)

WORKSTATION_SOFTWARE(
  (lab_id, workstation_no) PK/FK -> WORKSTATIONS,
  software_id PK/FK -> SOFTWARE_PACKAGES,
  installed_at
)
```

## Important database rules

- A workstation is identified by the composite key `(lab_id, workstation_no)`.
- A physical component can have only one active installation.
- Booking times must have `end_time > start_time`.
- Approved bookings in the same laboratory cannot overlap.
- Complaint and maintenance statuses are restricted by `CHECK` constraints.
- Booking status changes are recorded in `booking_status_history` by a PostgreSQL trigger.
- `v_*` objects in `views.sql` are read-only reporting views, not base tables.
