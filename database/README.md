# Database files

This folder contains SQL files used to reproduce the PostgreSQL database.

1. Export the current schema as `schema.sql`.
2. Add non-sensitive demonstration records as `seed.sql`.
3. Add approved PostgreSQL views as `views.sql`.
4. Keep presentation queries in `queries.sql`.

Do not add `pgadmin4.db`: it is pgAdmin's local configuration database, not the Laboratory Management System database, and may include local connection details.
