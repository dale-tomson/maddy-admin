# Maddy Admin UI

A lightweight PHP web interface for managing [maddy](https://maddy.email) email accounts, SMTP credentials, DKIM keys, and DNS records.

---

## Stack

| Layer | Technology |
|-------|------------|
| Runtime | PHP 8 built-in server (`php -S`) |
| Container | `php:8-alpine` + `docker-cli` |
| Logic | Classes in `app/lib/` |
| Auth | Single shared password via env var |

---

## Directory layout

```
admin/
├── Dockerfile          # Builds the admin container (php:8-alpine)
├── app/                # Mounted as /app inside the container
│   ├── router.php      # URI → page dispatcher for PHP built-in server
│   ├── index.php       # Login page
│   ├── accounts.php    # Email account management
│   ├── smtp.php        # SMTP connection info & credentials
│   ├── passwd.php      # Change account passwords
│   ├── dns.php         # DNS records reference + DKIM key generation
│   ├── logout.php      # Session destroy
│   ├── _auth.php       # Bootstrap: session, constants, auth guard
│   ├── _head.php       # Shared HTML header partial
│   ├── _foot.php       # Shared HTML footer partial
│   └── lib/
│       ├── Maddy.php         # Docker exec wrapper: exec(), listAccounts(), domain()
│       ├── Flash.php         # Session flash: Flash::set() / Flash::pop()
│       ├── MaddyStatus.php   # Container up/starting/down detection
│       └── AdminService.php  # All POST handlers + data-fetch helpers
└── examplenginx.conf   # Example nginx reverse-proxy config
```

---

## Configuration

Set these in your `.env` file (used by `docker-compose.yml`):

| Variable          | Required | Description                                      |
|-------------------|----------|--------------------------------------------------|
| `ADMIN_PASSWORD`  | Yes      | Password for the admin web UI login              |
| `MADDY_DOMAIN`    | Yes      | Primary mail domain (e.g. `example.com`)         |
| `MADDY_CONTAINER` | No       | Maddy container name — defaults to `maddy`       |
| `ADMIN_PORT`      | No       | Host port for the UI — defaults to `11000`       |

> **Never commit `.env` or real credentials.**

---

## Running

```bash
# From the repo root (org/maddy/)
docker compose up -d

# Tail admin logs
docker logs -f maddy-admin
```

The UI is available at `http://localhost:11000` (or the configured `ADMIN_PORT`).
Behind nginx, proxy to `http://127.0.0.1:11000` — see `examplenginx.conf` for a ready-to-use config.

---

## Pages

| URL | Purpose |
|-----|---------|
| `/` | Login |
| `/accounts.php` | Create / delete email accounts, manage IMAP and SMTP |
| `/smtp.php` | Connection info (ports, hostname) and credential list |
| `/passwd.php` | Change account password |
| `/dns.php` | Required DNS records and DKIM key generation |

---

## Architecture notes

- **`lib/` holds all reusable code** — view files are thin and only call into `lib/`.
- **`Maddy`** wraps all `docker exec` interaction with the maddy container: `Maddy::exec()`, `Maddy::listAccounts()`, `Maddy::domain()`.
- **`Flash`** manages session flash messages: `Flash::set()` before a redirect, `Flash::pop()` at the top of the next page.
- **`MaddyStatus`** checks whether the maddy container is up, starting, or down — used by the login page.
- **`AdminService`** is the single place for POST handling and data fetching. Views call `AdminService::handlePost('<scope>')` and `AdminService::get*()` helpers. Action dispatch is driven by a static `$actionMap` — no if/else chains.
- **`DOMAIN` constant** is resolved at runtime from the `MADDY_DOMAIN` env var or parsed from `maddy_data/maddy.conf` — never hardcoded.
- **Volume mount:** only `./admin/app` is mounted as `/app`. Any PHP the container needs must live inside `admin/app/`.

---

## Development tips

```bash
# Rebuild and restart just the admin container
docker compose up -d --build maddy-admin

# Run a maddy command manually
docker exec maddy maddy creds list

# Check DKIM key exists inside the maddy container
docker exec maddy ls /data/dkim_keys/
```
