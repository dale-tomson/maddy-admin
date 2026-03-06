# Maddy Admin UI

A lightweight PHP web interface for managing [maddy](https://maddy.email) email accounts, SMTP credentials, DKIM keys, and DNS records.

---

## Stack

| Layer | Technology |
|-------|-----------|
| Runtime | PHP 8 built-in server (`php -S`) |
| Container | `php:8-alpine` + `docker-cli` |
| Logic | `AdminService` class in `app/lib/` |
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
│   ├── maddy_connector.php  # Core helpers: maddy(), listAccounts(), flash, DOMAIN
│   ├── maddy_status.php     # Container status helper (login page indicator)
│   └── lib/
│       └── AdminService.php # All POST handlers + data-fetch helpers
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

- **All business logic lives in `app/lib/AdminService.php`** — view files are thin and only call `AdminService::handlePost('<scope>')` + `AdminService::get*()` helpers.
- **Action dispatcher** — `handlePost()` maps `$_POST['action']` values to small private handler methods via a static `$actionMap`. No long if/else chains in views.
- **`DOMAIN` constant** is resolved at runtime from `MADDY_DOMAIN` env var or parsed from `maddy_data/maddy.conf` — never hardcoded.
- **Maddy commands** run via `docker exec` inside the `maddy` container through the shared Docker socket (`/var/run/docker.sock`).
- **Volume mount:** only `./admin/app` is mounted as `/app`. Any PHP file the container needs must be inside `admin/app/`.

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
