-- Local assessment accounts only. Do not use these passwords outside a demo.
CREATE EXTENSION IF NOT EXISTS pgcrypto;

WITH demo_credentials(email, raw_password) AS (
    VALUES
        ('sanjay.kulkarni@vit.edu.in', 'AdminDemo2026!'),
        ('neha.rao@vit.edu.in', 'FacultyDemo2026!'),
        ('arjun.mehta@vit.edu.in', 'FacultyDemo2026!'),
        ('aditi.shah@vit.edu.in', 'AssistantDemo2026!'),
        ('rahul.verma@vit.edu.in', 'AssistantDemo2026!'),
        ('riya.patel@vit.edu.in', 'StudentDemo2026!'),
        ('karan.joshi@vit.edu.in', 'StudentDemo2026!')
)
UPDATE users AS u
SET password_hash = crypt(d.raw_password, gen_salt('bf', 12))
FROM demo_credentials AS d
WHERE u.email = d.email;
