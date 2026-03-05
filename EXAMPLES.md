# Examples and safe placeholders

This file contains safe, copy‑paste examples and placeholders you can adapt when deploying `maddy` from this repo. It intentionally uses placeholders — do NOT paste private keys, secrets, or real credentials into version control.

## `.env.example` (placeholder)

Copy this to `.env` and fill values locally (do not commit `.env`).

```
MADDY_HOSTNAME=mx.yourdomain.com
MADDY_DOMAIN=yourdomain.com
ADMIN_PASSWORD=change_this_to_a_strong_password
ADMIN_PORT=11000
ADMIN_DOMAIN=mail.yourdomain.com
```

## Minimal `maddy.conf` snippets (placeholders)

Replace `yourdomain.com` and file paths as appropriate.

```
listen tcp://0.0.0.0:25
tls cert="/data/tls/fullchain.pem" key="/data/tls/privkey.pem"

domain "yourdomain.com" {
  # delivery, auth and other sections here
}
```

## DKIM DNS example (public key placeholder)

If your selector is `maddy`, publish a TXT at `maddy._domainkey.yourdomain.com` with a value like:

```
v=DKIM1; k=rsa; p=MIIBIjANBgkqhkiG9w0BAQ...AB
```

The `p=` value must be the public key (single-line, no headers). Keep the private key on the server only.

## SPF example (TXT)

Basic SPF that permits mail from MX hosts:

```
v=spf1 mx -all
```

Use `~all` while testing if you're not ready to hard-fail.

## MX example

If your mail host is `mx.yourdomain.com`:

Type: MX
Name: @
Value: mx.yourdomain.com.
Priority: 10

Also ensure the `A` record for `mx` points to your server IP (not proxied through Cloudflare).

## PTR (reverse DNS)

PTR is set by your hosting provider. Ask them to point your IP back to `mx.yourdomain.com`.

## Nginx example (envsubst-ready)

Use `envsubst` to avoid hardcoding domains into the repo. Example command:

```bash
export ADMIN_DOMAIN=mail.yourdomain.com ADMIN_PORT=11000
envsubst '${ADMIN_DOMAIN} ${ADMIN_PORT}' < admin/nginx.conf.example > /etc/nginx/sites-available/${ADMIN_DOMAIN}
```

## TLS (Let's Encrypt example)

Request certificates on the host and copy into `maddy_data/tls/`:

```bash
certbot certonly --standalone -d mx.yourdomain.com
cp /etc/letsencrypt/live/mx.yourdomain.com/fullchain.pem maddy_data/tls/
cp /etc/letsencrypt/live/mx.yourdomain.com/privkey.pem maddy_data/tls/
```

## DKIM key generation (on server)

```bash
openssl genrsa -out dkim.private.pem 2048
openssl rsa -in dkim.private.pem -pubout -out dkim.public.pem
```

Publish the contents of `dkim.public.pem` (converted to single-line) in the TXT record. Do NOT store `dkim.private.pem` in the repo.

## Testing tips

- Check DNS propagation: `dig MX yourdomain.com`, `dig TXT maddy._domainkey.yourdomain.com`.
- Check TLS: `openssl s_client -connect mx.yourdomain.com:993 -servername mx.yourdomain.com`.
- Verify SPF: send a test mail and check headers or use online SPF checkers.

## Safety checklist

- `.env` must remain gitignored.
- `maddy_data/tls/privkey.pem` and any `dkim.private.pem` must never be committed.
- Use staging/`~all` SPF/relaxed DMARC while testing.