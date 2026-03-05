# Maddy Mail Server (Docker)

Self-hosted mail server using Maddy with a lightweight PHP web admin panel. This repository contains configuration and helper files to run Maddy in Docker alongside a minimal admin UI.

This document is intended for operators. It omits any sensitive values (private keys, passwords, API tokens) — never commit those to the repository.

## Quick links

- Upstream: https://maddy.email/
- This repo: configuration and a small admin UI

## Highlights

- SMTP (inbound), submission (465/587), IMAP (993/143)
- SQLite for simple credential storage
- Simple web admin to manage mailboxes (proxied by nginx)
- TLS for mail ports; web admin typically behind Cloudflare or similar

## Directory overview

See the repository for the exact layout. Important items:

- `.env.example` — copy to `.env` and populate values (do not check secrets in)
- `docker-compose.yml` — service definitions for Maddy + admin
- `maddy_data/maddy.conf` — active Maddy configuration (gitignored)
- `maddy_data/tls/` — TLS certs for mail ports (gitignored)
- `admin/` — PHP admin app and an example nginx config

## Prerequisites

- Docker & Docker Compose (v2 recommended)
- A public domain you control
- Ability to create DNS records for the domain
- A valid TLS certificate for `mx.yourdomain.com` (used by Maddy for mail ports)

Note: Do NOT store private keys or other secrets in the repo. Use environment files that are gitignored or secret managers.

## Required DNS records (minimum)

The exact names and values will depend on your domain and naming choices. Replace `yourdomain.com` and `mx`/`mail` with your chosen names.

- MX record
  - Type: `MX`
  - Name: `@`
  - Value: `mx.yourdomain.com.`
  - Priority: `10`

- A record for mail transport (MTA)
  - Type: `A`
  - Name: `mx` (or `mail` if you prefer)
  - Value: `your-server-ip`
  - NOTE: This record should NOT be proxied (Cloudflare "orange cloud" off) because mail protocols do not work through HTTP proxies.

- A record for web admin (optional)
  - Type: `A`
  - Name: `mail` (or another name)
  - Value: `your-server-ip`
  - This can be proxied if you want Cloudflare in front of the admin site.

- SPF (TXT)
  - Type: `TXT`
  - Name: `@`
  - Value: e.g. `v=spf1 mx -all` (or `v=spf1 mx ~all` during testing)

- DKIM (TXT)
  - Type: `TXT`
  - Name: `<selector>._domainkey` (e.g. `maddy._domainkey`)
  - Value: `v=DKIM1; k=rsa; p=<public-key-without-newlines>`
  - Generate DKIM keys on the server and publish only the public key here. NEVER publish the private key.

- PTR (reverse DNS)
  - PTR must be set by the IP owner (your hosting provider) and should point from `your-server-ip` → `mx.yourdomain.com`.

- DMARC (optional but recommended)
  - Type: `TXT`
  - Name: `_dmarc`
  - Value: `v=DMARC1; p=quarantine; rua=mailto:postmaster@yourdomain.com` (tune to your policy)

## TLS (mail ports)

- Maddy requires a TLS certificate for TLS-enabled ports (submission, IMAPS, etc.). Use a certificate for the mail hostname (e.g. `mx.yourdomain.com`).
- Recommended: obtain certs from Let's Encrypt (Certbot) or your CA and place them under `maddy_data/tls/` as `fullchain.pem` and `privkey.pem`. Keep the private key secret and gitignored.
- Automatic certificate provisioning (ACME) can be used on the host and copied into the container; do not embed ACME credentials into the repo.

Example (Certbot standalone):

```bash
certbot certonly --standalone -d mx.yourdomain.com
cp /etc/letsencrypt/live/mx.yourdomain.com/fullchain.pem maddy_data/tls/
cp /etc/letsencrypt/live/mx.yourdomain.com/privkey.pem  maddy_data/tls/
```

## DKIM — generating and using keys

1. Generate a keypair on the server (do not commit private key):

```bash
openssl genrsa -out dkim.private.pem 2048
openssl rsa -in dkim.private.pem -pubout -out dkim.public.pem
```

2. Add the public key to DNS at `<selector>._domainkey.yourdomain.com` as a single-line `p=` value.
3. Configure Maddy (or your signing tool) to use the private key for outgoing mail signing.

Important: never include `dkim.private.pem` or the private key material in the repo or documentation. Use placeholders in config examples.

## Security notes

- Never commit `.env` or `maddy_data/tls/privkey.pem` into version control.
- Rotate keys and certificates regularly.
- Monitor logs for delivery/connection failures; many mail delivery issues relate to DNS or PTR mismatches.

## Example environment (do not commit)

Copy and edit `.env.example` to `.env` and populate values locally. Example keys to set locally:

- `MADDY_HOSTNAME=mx.yourdomain.com`
- `MADDY_DOMAIN=yourdomain.com`
- `ADMIN_PASSWORD=CHANGE_ME_TO_A_STRONG_PASSWORD`
- `ADMIN_DOMAIN=mail.yourdomain.com`

## Running

Start the stack:

```bash
docker compose up -d --build
```

View logs:

```bash
docker compose logs -f
```

## Reference to upstream

This deployment uses Maddy as the MTA. See the upstream project for configuration options and documentation:

https://maddy.email/

## License

This repository is released under the MIT License. See the included `LICENSE` file.

