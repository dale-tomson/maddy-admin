# Copilot Instructions for org/maddy

## Repository purpose
Web admin UI and helper scripts for the [maddy](https://maddy.email) self-hosted mail server.

---

## Project layout

```
admin/
  Dockerfile              # PHP 8 + docker-cli; WORKDIR /app; serves router.php on :8080
  app/                    # Volume-mounted as /app inside the container
    router.php            # Built-in-server router — maps URIs to page files
    index.php             # Login page (validates ADMIN_PASSWORD env var)
    accounts.php          # Email account management view
    smtp.php              # SMTP credential / connection info view
    passwd.php            # Change-password view
    dns.php               # DNS record reference + DKIM key generation view
    logout.php            # Session destroy
    _auth.php             # Bootstrap: session start, constants, auth guard
    _head.php / _foot.php # Shared HTML header / footer partials
    lib/
      Maddy.php           # Docker exec wrapper: exec(), listAccounts(), domain()
      Flash.php           # Session flash: Flash::set() / Flash::pop()
      MaddyStatus.php     # Container status check (up/starting/down)
      AdminService.php    # All POST handlers + data-fetch helpers
maddy_data/
  maddy.conf              # Runtime maddy config (not tracked; example in maddy.conf.example)
docker-compose.yml        # Defines `maddy` and `maddy-admin` services
```

---

## Key architecture decisions

- **`admin/app/` is the only volume mount** (`./admin/app:/app`). Any PHP file that must be accessible from the container must live inside `admin/app/`.
- **`lib/` contains all reusable classes** — global functions have been eliminated:
  - `Maddy` — wraps `docker exec` calls to the maddy container (`Maddy::exec()`, `Maddy::listAccounts()`, `Maddy::domain()`).
  - `Flash` — session flash messages (`Flash::set(msg, type)` before redirect → `Flash::pop()` on next page).
  - `MaddyStatus` — checks container state (`MaddyStatus::get()` returns `['state'=>'up'|'starting'|'down', ...]`).
  - `AdminService` — central POST dispatcher + data helpers for all admin pages.
- **`AdminService::handlePost(string $scope)`** looks up `$_POST['action']` in a static `$actionMap` keyed by scope and calls the matching private handler. No long if/else chains.
- **View files are thin** — each page file only calls `AdminService::handlePost('<scope>')`, `Flash::pop()`, the relevant `AdminService::get*()` helper, then includes `_head.php` and the HTML template.
- **`DOMAIN` constant** is resolved at runtime in `_auth.php` via `Maddy::domain()` — from `MADDY_DOMAIN` env var or parsed from `maddy_data/maddy.conf`. Never hardcoded.
- **Secrets** (`ADMIN_PASSWORD`, `MADDY_DOMAIN`, `MADDY_CONTAINER`) come only from environment variables — never commit them.

---

## Environment variables (docker-compose / .env)

| Variable           | Used by            | Purpose                                    |
|--------------------|--------------------|--------------------------------------------|
| `ADMIN_PASSWORD`   | `_auth.php`        | Login password for the web UI              |
| `MADDY_DOMAIN`     | `Maddy::domain()`  | Primary mail domain                        |
| `MADDY_CONTAINER`  | `Maddy::exec()`    | Docker container name for exec             |
| `ADMIN_PORT`       | `docker-compose`   | Host port for the admin UI (default 11000) |

---

## Conventions

- **PHP style:** short focused methods, 4-space indent, `require_once` with `__DIR__`-relative paths.
- **Views are thin:** no business logic in `.php` view files — delegate everything to `AdminService`.
- **Require paths:** always use `__DIR__` — never `dirname()` chains or dynamic path searches.
- **Shell helpers:** keep short and place at repo root or `docker/`; document required env vars inline.
- **maddy_data/:** treat as runtime-only; only commit `maddy.conf.example`, not `maddy.conf`.
- **Commit messages:** `Add|Fix|Update|Refactor <scope>: <short description>`

---

## Testing

- Bring up the stack: `docker compose up -d`
- Tail admin logs: `docker logs -f maddy-admin`
- Exercise the relevant page in a browser; check flash messages and maddy output.
- For CLI-only changes: `docker exec -it maddy <command>`

---

## Files to read before editing

| Task | Read first |
|------|------------|
| Adding a new POST action | `admin/app/lib/AdminService.php` (add to `$actionMap` + new private method) |
| Changing a view | the corresponding `admin/app/<page>.php` |
| Auth / session / constants | `admin/app/_auth.php`, `admin/app/lib/Maddy.php` |
| Flash messages | `admin/app/lib/Flash.php` |
| DNS / DKIM | `admin/app/dns.php`, `AdminService::getDnsData()` |
| Container status | `admin/app/lib/MaddyStatus.php` |
| Container wiring | `docker-compose.yml`, `admin/Dockerfile` |

---

Ask before making changes that touch more than one logical concern per commit.
