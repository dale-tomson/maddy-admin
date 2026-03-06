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
    maddy_connector.php   # Core helpers: maddy(), listAccounts(), flash helpers, DOMAIN resolver
    maddy_status.php      # Docker status check helper (used by login page)
    lib/
      AdminService.php    # Central logic class — all POST handlers + data helpers
maddy_data/
  maddy.conf              # Runtime maddy config (not tracked; example in maddy.conf.example)
docker-compose.yml        # Defines `maddy` and `maddy-admin` services
```

---

## Key architecture decisions

- **`admin/app/` is the only volume mount** (`./admin/app:/app`). Any PHP file that must be accessible from the container must live inside `admin/app/`.
- **`AdminService`** (`admin/app/lib/AdminService.php`) is the single place for all POST handling and data-fetching logic. Views only call `AdminService::handlePost('<scope>')` and `AdminService::get*()` helpers.
- **Action dispatcher** — `AdminService::handlePost(string $scope)` looks up `$_POST['action']` in a static `$actionMap` keyed by scope and calls the matching private handler method. No long if/else chains.
- **`DOMAIN` constant** is resolved at runtime from the `MADDY_DOMAIN` env var or from `maddy.conf`. It is never hardcoded.
- **Secrets** (`ADMIN_PASSWORD`, `MADDY_DOMAIN`, `MADDY_CONTAINER`) come only from environment variables — never commit them.

---

## Environment variables (docker-compose / .env)

| Variable           | Used by          | Purpose                              |
|--------------------|------------------|--------------------------------------|
| `ADMIN_PASSWORD`   | `_auth.php`      | Login password for the web UI        |
| `MADDY_DOMAIN`     | `maddy_connector.php` | Primary mail domain             |
| `MADDY_CONTAINER`  | `maddy_connector.php` | Docker container name for exec  |
| `ADMIN_PORT`       | `docker-compose` | Host port for the admin UI (default 11000) |

---

## Conventions

- **PHP style:** short focused functions/methods, 4-space indent, `require_once` with `__DIR__`-relative paths.
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
|------|-----------|
| Adding a new POST action | `admin/app/lib/AdminService.php` |
| Changing a view | the corresponding `admin/app/<page>.php` |
| Auth / session / constants | `admin/app/_auth.php`, `admin/app/maddy_connector.php` |
| DNS / DKIM | `admin/app/dns.php`, `AdminService::getDnsData()` |
| Container wiring | `docker-compose.yml`, `admin/Dockerfile` |

---

Ask before making changes that touch more than one logical concern per commit.
