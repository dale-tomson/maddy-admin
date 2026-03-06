# Copilot Instructions for org/maddy

Repository purpose
- Admin UI and helper scripts for the `maddy` mail server, under the `admin/` web UI and supporting scripts.

Conventions
- PHP files: follow existing short-function style and current indentation.
- Shell helpers: prefer short, well-documented scripts placed in `docker/` or top-level helpers.
- Avoid changing runtime data under `maddy_data/` in commits unless updating bundled example configs.

Safe editing guidance
- Limit changes to a single logical concern per commit.
- Do not store secrets in the repo; reference environment variables or `maddy_data/` which is runtime-only.

Testing & verification
- For admin UI changes, test by running the service stack (docker-compose) and exercising the page.
- For CLI changes, run the example command locally inside the container or with the same runtime environment.

Commit messages
- Format: `Add|Fix|Update <scope>: <brief description>`

Files to inspect first
- `admin/` — UI pages and logic (e.g., `admin/app/accounts_logic.php`)
- `maddy_data/` — runtime config and example `maddy.conf`

If you need clarification, ask before making large or cross-cutting changes.

--
