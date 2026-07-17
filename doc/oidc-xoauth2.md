<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

# Provisioning with OIDC access tokens (XOAUTH2)

Minimal end-to-end setup for the `oidcEnabled` provisioning option: Nextcloud Mail
authenticates to IMAP/SMTP with the logged-in user's OIDC access token instead of a
stored password. Written for the
[nextcloud-docker-dev](https://github.com/nextcloud/nextcloud-docker-dev) environment
(Authentik at `http://authentik.local`, Nextcloud at `http://nextcloud.local`), but the
config translates 1:1 to any Authentik + Dovecot 2.4 + Postfix deployment.

This flow has been verified end to end on a fully local docker-dev stack (Authentik
2026.5.5 + Dovecot 2.4.4 + Postfix): SSO login → account auto-provisioned → IMAP sync
and message send over XOAUTH2 → background sync after the access token expired
(refresh-token path, no session).

## How it works

```
Browser ──SSO──> Authentik ──token──> user_oidc (session)
                                          │ mirrored on Mail requests,
                                          │ refreshed via refresh token in cron
                                          ▼
                              oc_mail_accounts (encrypted)
                                          │ XOAUTH2 (user=<email>, auth=Bearer <token>)
                              ┌───────────┴───────────┐
                              ▼                       ▼
                        Dovecot :993            Postfix :587 ──SASL──> Dovecot
                              │                       (introspects the token
                              ▼                        with its own client creds)
                        Authentik /introspect/
```

Requirements: the access token must carry an `email` claim, Dovecot keys mailboxes on
that claim, and the XOAUTH2 `user=` must equal it — Mail sends the account email, so the
provisioning email template must produce the same address the IdP puts in the token.

## 1. Authentik

Log in as `akadmin` and create **two OAuth2/OpenID providers**:

**Provider "Nextcloud"** (drives the SSO login):

- Client type: *Confidential*, Client ID: `nextcloud`
- Redirect URIs (strict):
  - `http://nextcloud.local/index.php/apps/user_oidc/code`
  - `http://nextcloud.local/apps/user_oidc/code`
  - type *logout*: `http://nextcloud.local/` (for RP-initiated logout)
- Signing key: select the self-signed certificate (**RS256** — user_oidc rejects the
  HS256 default)
- Scopes: `openid`, `email`, `profile`, `offline_access` (offline_access ⇒ refresh
  tokens ⇒ background sync keeps working after the access token expires)
- Invalidation flow: `default-invalidation-flow` if logging out of Nextcloud should end
  the whole SSO session

**Provider "Dovecot"** (introspection credential only — no login, no redirect URIs):

- Client type: *Confidential*, Client ID: `dovecot`, note the client secret

**Federation** (the critical, easy-to-miss step): edit the **Nextcloud** provider →
*Federated OIDC Providers* → add **Dovecot**. Direction matters: the *issuing* provider
lists the *introspecting* one. Reversed, every introspection returns `{"active": false}`.

> **Authentik version**: cross-provider introspection requires Authentik ≥ 2026.x —
> verified working on 2026.5.5. On 2025.10.x the identical configuration answers every
> federated introspection with `{"active": false}` (docker-dev pins 2025.10 by default;
> set `AUTHENTIK_TAG=2026.5.5` in `.env`).

Create an **application** for the Nextcloud provider (slug `nextcloud`) and a test user
whose **email domain matches your mail domain**.

## 2. Dovecot (2.4)

```
# dovecot.conf (fragments)
auth_mechanisms {
  plain = yes         # optional, for non-OIDC clients
  login = yes
  oauthbearer = yes
  xoauth2 = yes
}

passdb oauth2 {
}
oauth2 {
  # client id/secret of the *Dovecot* provider, embedded in the URL
  introspection_url = https://dovecot:<CLIENT_SECRET>@authentik.local/application/o/introspect/
  introspection_mode = post
  username_attribute = email     # mailbox = the token's email claim
  active_attribute = active
  active_value = true
}

mail_home = /var/vmail/%{user | domain}/%{user | username}
mail_driver = maildir
mail_path = ~/mail

# Auto-create the folders Mail needs — it refuses to send without a Sent mailbox
namespace inbox { inbox = yes  separator = / }
mailbox Drafts  { special_use = \Drafts   auto = subscribe }
mailbox Sent    { special_use = \Sent     auto = subscribe }
mailbox Trash   { special_use = \Trash    auto = subscribe }
mailbox Spam    { special_use = \Junk     auto = subscribe }

# SASL service for Postfix submission
service auth {
  inet_listener auth { port = 12 }
}
```

You also need a userdb (static or LDAP) resolving the email to uid/gid/home. With a
static userdb, set `allow_all_users = yes` — otherwise LMTP delivery fails its userdb
lookup, because the oauth2 passdb cannot answer lookups without a token:

```
userdb static {
  allow_all_users = yes
  fields {
    uid = 1000
    gid = 1000
    home = /var/vmail/%{user | domain}/%{user | username}
  }
}
```

On the minimal `dovecot/dovecot` image, also set `default_login_user`,
`default_internal_user` and `default_internal_group` to `vmail` (the image has no
`dovenull` user), and make sure the mail volume is writable by that uid.

A complete, ready-to-run local pair (Dovecot 2.4.4 + Postfix on docker compose, joining
the docker-dev network) is given in the [appendix](#appendix-ready-to-run-local-pair).

## 3. Postfix (submission :587)

```
# master.cf
submission inet n - n - - smtpd
  -o smtpd_tls_security_level=encrypt
  -o smtpd_sasl_auth_enable=yes
  -o smtpd_client_restrictions=permit_sasl_authenticated,reject
  -o smtpd_sender_restrictions=reject_authenticated_sender_login_mismatch
  # Authentik RS256 tokens make the XOAUTH2 SASL blob ~2.4 KB — over the 2048
  # default line limit. Without this every AUTH is truncated ("invalid base64").
  -o line_length_limit=8192
```

```
# main.cf
smtpd_sasl_type = dovecot
smtpd_sasl_path = inet:<dovecot-host>:12
smtpd_sasl_security_options = noanonymous
# Dovecot returns the email as SASL username; senders may only use their own address
smtpd_sender_login_maps = regexp:/etc/postfix/sender_login_maps.regexp
```

```
# /etc/postfix/sender_login_maps.regexp — identity map: login == allowed sender
/^(.+)$/ ${1}
```

## 4. Nextcloud

```sh
occ app:enable user_oidc
occ user_oidc:provider Authentik \
  --clientid=nextcloud \
  --clientsecret=<NEXTCLOUD_CLIENT_SECRET> \
  --discoveryuri=https://authentik.local/application/o/nextcloud/.well-known/openid-configuration \
  --scope="openid email profile offline_access" \
  --unique-uid=0 --mapping-uid=preferred_username \
  --send-id-token-hint=1
# Keep the login token so Mail can mirror it (and single logout works)
occ config:app:set user_oidc store_login_token --value=1

# The first XOAUTH2 login triggers a *cold* token introspection at the IdP, which can
# exceed Mail's 5s default IMAP timeout (symptom: spurious "denied authentication"
# followed by a rate-limit lockout).
occ config:system:set app.mail.imap.timeout --value=20 --type=integer
```

Then in **Admin settings → Groupware → Mail app**, add a provisioning configuration:

| Setting | Value |
|---|---|
| Provisioning domain | your mail domain (or `*`) |
| Email template | `%EMAIL%` |
| IMAP user / SMTP user | `%EMAIL%` |
| IMAP host/port | your Dovecot, `993` / SSL — **the host must match the TLS cert SAN** |
| SMTP host/port | your Postfix, `587` / STARTTLS |
| OpenID Connect | ✅ *Use the user's OIDC access token (XOAUTH2)* |

## 5. Verify

1. Log in to Nextcloud via SSO as the test user, open Mail → the account appears and
   syncs without ever asking for a password.
2. Send a mail to yourself → lands in INBOX, copy in Sent.
3. Background path: force-expire the token and sync without a session —
   ```sh
   # UPDATE oc_mail_accounts SET oauth_token_ttl = 1 WHERE id = <id>;
   occ mail:account:sync <id>
   ```
   The sync must succeed and `oauth_token_ttl` move into the future (refreshed via the
   stored refresh token against the IdP token endpoint).

## Troubleshooting

- **"Mail server denied authentication" on the first attempt, works later** — cold
  introspection vs. the IMAP timeout, see `app.mail.imap.timeout` above. After 3
  failures Mail's rate limiter blocks the bucket for up to 3 h (`mail_imap_ratelimit`
  keys in the distributed cache).
- **Every login fails, Dovecot logs show introspection `active: false`** — the
  federation is missing or reversed (see step 1).
- **SMTP `535 invalid base64`** — `line_length_limit` not raised on the submission port.
- **SMTP `553 not owned by user`** — sender ≠ SASL login; aliases need entries in
  `smtpd_sender_login_maps`.
- **`occ mail:account:diagnose`** and `occ config:system:set app.mail.debug --value=true
  --type=boolean` (protocol log in `data/mail-<user>-<id>-imap.log`) are your friends.

## Appendix: ready-to-run local pair

A self-contained Dovecot + Postfix pair for the docker-dev environment. It joins the
docker-dev docker network so the containers resolve `authentik.local` / `nextcloud.local`,
and exposes IMAP on `127.0.0.1:1143` and submission on `127.0.0.1:1587` for host-side
testing. No TLS — this is a local test rig, not a deployment.

### 0. Bump docker-dev's Authentik

docker-dev pins an Authentik version that predates federated introspection. In the
docker-dev repo root, set the tag in `.env` and recreate:

```sh
echo 'AUTHENTIK_TAG=2026.5.5' >> .env
docker compose up -d authentik authentik-worker
```

Then create the two providers, federation, application and test user from step 1 (the
Authentik admin UI, or its API).

### 1. Create the directory

All paths below are relative to `data/oidc-mail/` in the docker-dev checkout (the `data/`
dir is bind-mounted, so anything here is reachable from the host):

```sh
mkdir -p data/oidc-mail/dovecot data/oidc-mail/postfix
cd data/oidc-mail
```

### 2. `docker-compose.yml`

```yaml
services:
  dovecot:
    image: dovecot/dovecot:2.4.4
    ports:
      - "127.0.0.1:1143:143"
    volumes:
      # rendered from dovecot.conf.template (secret substituted on the host)
      - ./dovecot/dovecot.conf:/etc/dovecot/dovecot.conf:ro
      - vmail:/var/vmail
    networks:
      default:
        aliases: [dovecot.local]

  postfix:
    build: ./postfix
    ports:
      - "127.0.0.1:1587:587"
    depends_on: [dovecot]
    networks:
      default:
        aliases: [postfix.local]

volumes:
  vmail:

networks:
  default:
    name: master_default   # the docker-dev network
    external: true
```

### 3. `dovecot/dovecot.conf.template`

The Dovecot image is minimal (no `sed`), so the introspection client secret is
substituted **on the host** and the rendered file is mounted (see step 6).

```
dovecot_config_version = 2.4.0
dovecot_storage_version = 2.4.0

log_path = /dev/stderr
auth_verbose = yes
protocols = imap lmtp

# The image has no dovenull user
default_login_user = vmail
default_internal_user = vmail
default_internal_group = vmail

# Local test rig: no TLS
ssl = no
auth_allow_cleartext = yes

auth_mechanisms {
  plain = yes
  login = yes
  oauthbearer = yes
  xoauth2 = yes
}

passdb oauth2 {
}
oauth2 {
  introspection_url = http://dovecot:__DOVECOT_CLIENT_SECRET__@authentik.local/application/o/introspect/
  introspection_mode = post
  username_attribute = email
  active_attribute = active
  active_value = true
}

userdb static {
  allow_all_users = yes
  fields {
    uid = 1000
    gid = 1000
    home = /var/vmail/%{user | domain}/%{user | username}
  }
}

mail_driver = maildir
mail_home = /var/vmail/%{user | domain}/%{user | username}
mail_path = ~/mail
mailbox_list_index = yes

namespace inbox {
  inbox = yes
  separator = /
}

mailbox Drafts { special_use = \Drafts  auto = subscribe }
mailbox Sent   { special_use = \Sent    auto = subscribe }
mailbox Trash  { special_use = \Trash   auto = subscribe }
mailbox Spam   { special_use = \Junk    auto = subscribe }

service imap-login { inet_listener imap { port = 143 } }
service lmtp       { inet_listener lmtp { port = 24 } }   # delivery from Postfix
service auth       { inet_listener auth { port = 12 } }   # SASL for Postfix submission
```

### 4. `postfix/` files

`postfix/Dockerfile`:

```dockerfile
FROM alpine:3.22
RUN apk add --no-cache postfix
COPY main.cf /etc/postfix/main.cf
COPY sender_login_maps.regexp /etc/postfix/sender_login_maps.regexp
COPY submission.cf /tmp/submission.cf
RUN cat /tmp/submission.cf >> /etc/postfix/master.cf && rm /tmp/submission.cf
CMD ["postfix", "start-fg"]
```

`postfix/main.cf`:

```
compatibility_level = 3.6
maillog_file = /dev/stdout
myhostname = postfix.local
mydomain = example.local
myorigin = $mydomain
mydestination =

virtual_mailbox_domains = example.local
virtual_transport = lmtp:inet:dovecot:24

smtpd_sasl_type = dovecot
smtpd_sasl_path = inet:dovecot:12
smtpd_sasl_security_options = noanonymous
smtpd_sender_login_maps = regexp:/etc/postfix/sender_login_maps.regexp

smtpd_relay_restrictions = permit_sasl_authenticated, reject_unauth_destination
smtpd_recipient_restrictions = permit_sasl_authenticated, reject_unauth_destination
```

`postfix/submission.cf` (appended to `master.cf` at build time):

```
submission inet n       -       n       -       -       smtpd
  -o syslog_name=postfix/submission
  -o smtpd_tls_security_level=none
  -o smtpd_sasl_auth_enable=yes
  -o smtpd_client_restrictions=permit_sasl_authenticated,reject
  -o smtpd_sender_restrictions=reject_authenticated_sender_login_mismatch
  -o line_length_limit=8192
```

`postfix/sender_login_maps.regexp`:

```
/^(.+)$/ ${1}
```

### 5. Render the Dovecot config with the introspection secret

```sh
# <DOVECOT_CLIENT_SECRET> is the client secret of the "Dovecot" Authentik provider
sed "s|__DOVECOT_CLIENT_SECRET__|<DOVECOT_CLIENT_SECRET>|" \
  dovecot/dovecot.conf.template > dovecot/dovecot.conf
```

### 6. Prepare the mail volume and start

The image's `vmail` user is uid 1000; the named volume must be writable by it:

```sh
docker compose up -d          # creates the vmail volume
docker run --rm -v oidc-mail_vmail:/var/vmail alpine:3.22 chown 1000:1000 /var/vmail
docker compose up -d --build  # (re)build postfix and start both
```

Check the logs — Dovecot should report `starting up for imap, lmtp` with no fatal
errors:

```sh
docker compose logs -f dovecot postfix
```

### 7. Point Mail at it

Use these values in step 4's `occ` command and the provisioning config: IMAP host
`dovecot.local` port `143` SSL *none*, SMTP host `postfix.local` port `587` SSL *none*,
provisioning domain `example.local` (matching the test user's email). Reaching the pair
from the **host** instead (e.g. an IMAP client) uses `127.0.0.1:1143` / `127.0.0.1:1587`.
