# Laboratory Management System

A DBMS mini project for managing laboratories, workstations, bookings, complaints, and maintenance activities. The main focus is the PostgreSQL relational database; the PHP interface is intentionally simple and demonstrates database connectivity.

## Team

- Swanandi Deshmukh - 25102B0073
- Rishikesh Dhamdhere - 25102B0075
- Harshad Patankar - 25102B0080

## Technology

- PostgreSQL and pgAdmin 4
- PHP with PDO PostgreSQL
- HTML, CSS, and small JavaScript enhancements

## Run locally

1. Create `laboratory_management_db` in PostgreSQL.
2. Import the team SQL schema and sample-data files once exported into `database/`.
3. Copy `config/database.php.example` to `config/database.php`, then enter local credentials. Do not commit it.
4. Enable PHP's `pdo_pgsql` extension.
5. Run `php -S localhost:8000 -t public`, then open `http://localhost:8000`.

## Planned modules

- Laboratories and workstations
- Booking management
- Complaint management
- Maintenance management
- SQL reports using joins and PostgreSQL views

## DBMS concepts demonstrated

- Primary keys, foreign keys, relationships, and referential integrity
- CHECK constraints and validation
- SQL joins and aggregate reports
- PostgreSQL views

## Security

Never commit PostgreSQL passwords, `config/database.php`, `.env`, or `pgadmin4.db`. The pgAdmin file is local configuration, not a project database backup.
A DBMS mini project for managing laboratories, workstations, bookings, complaints, and maintenance using PostgreSQL and PHP.
