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
2. For a new database, run `database/schema.sql`, `database/seed.sql`, `database/views.sql`, `database/booking_conflicts.sql`, and `database/booking_history.sql` in that order in pgAdmin's Query Tool.
3. Run `database/demo_login_passwords.sql` to set local assessment passwords for the seeded users.
4. Copy `config/database.php.example` to `config/database.php` and enter local PostgreSQL credentials. Do not commit this file.
5. The supplied `start-server.bat` enables PostgreSQL extensions bundled with this project's WinGet PHP installation. For another PHP installation, enable `pdo_pgsql` in its `php.ini`.
6. Run `start-server.bat`, then open `http://localhost:8000`.

Without a working PostgreSQL connection, pages show an explicit disconnected state and do not substitute sample records. With PostgreSQL connected, pages read and update the same database records.

For an existing database that already has its schema and seed rows, do not rerun `schema.sql` or `seed.sql`. Run only `database/booking_conflicts.sql` and `database/booking_history.sql` if those additions have not already been applied. The migration scripts are safe to rerun.

The database design is documented in [`database/ER_DIAGRAM.md`](database/ER_DIAGRAM.md). It includes a Mermaid ER diagram and the relational schema used in the SQL files.

## Connected workflows

- Inventory and installed components
- Booking requests, overlap checks, and assistant approval/rejection
- First-submitted overlapping request review, with PostgreSQL preventing two overlapping approved bookings
- Booking status history, with actor, timestamp, and status transition
- Student complaints assigned to the assistant
- Scheduled maintenance, task progression, and linked complaint resolution
- Dashboard statistics, activity, and reports from database records and views

Sign in with a seeded account. Role permissions are loaded from the authenticated database user; there is no public persona switcher. Local assessment passwords are listed in `database/demo_login_passwords.sql` and must not be used for real accounts or production deployments.

The appearance toggle supports light and dark themes; the choice is saved in the browser. Dark mode uses graphite surfaces, muted blue accents, and adjusted semantic status colors.

## Render Deployment

Docker is optional for local development: `start-server.bat` runs the PHP development server directly. The included `Dockerfile` is used when deploying the PHP application as a Docker Web Service, such as on Render. It installs Apache, PHP, `pdo_pgsql`, and `pgsql`; it does not run PostgreSQL itself. Create a separate Render PostgreSQL database and a Docker Web Service from this repository, then set the Web Service's `DATABASE_URL` to the database's **internal connection URL**. Apply the SQL files above to that hosted database using pgAdmin and its external connection URL. Do not point a public deployment at a developer's local database.

The app prefers `DATABASE_URL` when set and falls back to `config/database.php` for local development. Set `APP_ENV=production` in Render so session cookies are marked Secure behind HTTPS. Keep all database URLs and credentials in Render's environment settings; never commit them. The assessment passwords are public demo credentials, so replace them before any real deployment.

## DBMS concepts demonstrated

- Primary keys, foreign keys, relationships, and referential integrity
- CHECK constraints and validation
- SQL joins and aggregate reports
- PostgreSQL views

## Security

Never commit PostgreSQL credentials, `config/database.php`, `.env`, or `pgadmin4.db`. The pgAdmin file is local configuration, not a project database backup. The demo login passwords are public test credentials only; replace the demo credential script before any real deployment.
A DBMS mini project for managing laboratories, workstations, bookings, complaints, and maintenance using PostgreSQL and PHP.
