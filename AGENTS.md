# AGENTS.md — Repository guidance for AI coding agents

Purpose
- Short, actionable instructions to help an AI agent get productive in this PHP project.

Quick environment
- Runtime: PHP 7.4+ with PDO and MySQL. Recommend XAMPP on Windows.
- Start locally: place repo in XAMPP `htdocs`, enable Apache+MySQL, open the site root in a browser.
- DB bootstrap: import [setup_database.sql](setup_database.sql) into MySQL.

Key files (jump to them)
- Config and DB: [config/database.php](config/database.php)
- Entry point: [index.php](index.php)
- Auth: [auth/login.php](auth/login.php), [auth/register.php](auth/register.php)
- Admin area: [admin/dashboard.php](admin/dashboard.php)
- User area: [usuario/crear_ticket.php](usuario/crear_ticket.php)
- Helpers: [includes/functions.php](includes/functions.php)
- Assets: [assets/css/style.css](assets/css/style.css), [assets/js/main.js](assets/js/main.js)
- DB schema & migrations: [setup_database.sql](setup_database.sql), [migrations/](migrations/)

Developer notes & gotchas (concise)
- This is a plain PHP app (no framework). Preserve existing conventions and global helpers.
- Sessions: `session_start()` is used across pages — avoid output before sessions start.
- DB creds live in [config/database.php](config/database.php) — update when running locally.
- XAMPP on Windows is the recommended run environment; consider adding a sample vhost for consistent BASE_URL.
- No automated tests or build scripts present.

Guidance for AI agents
- Link to docs rather than copying them (see QUICK_START.md and README.md).
- Small, targeted changes only: prefer config edits, small fixes, or new migrations.
- When modifying database seeds or setup SQL, keep a backup and update migrations in `migrations/`.
- Avoid large UI rewrites without user approval.

Recommended first tasks an agent can offer
- Add a `tickets.local` vhost example and instructions.
- Create a simple `check_env.php` script to validate PHP extensions and DB connectivity.
- Generate hashed passwords for sample users and optionally patch `setup_database.sql`.

Next suggested customizations
- Create a lightweight skill to import/export DB seeds.
- Add an agent hook to run a local environment checklist (Apache, MySQL, PDO extensions).

If you want, I can implement any of the recommended first tasks above; tell me which one to start.
