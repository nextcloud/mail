<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

# OpenID Connect (XOAUTH2) local test environment

Admin and user documentation for the generic OIDC/XOAUTH2 integration (registering a
provider, how accounts connect, troubleshooting) lives in the
[official documentation](https://docs.nextcloud.com/server/latest/admin_manual/groupware/mail.html),
not here. This file covers only how to stand up a local identity provider and mail
server pair for development, since that setup doesn't belong in the admin manual.

## Setting up a local test environment

A minimal end-to-end setup on the
[nextcloud-docker-dev](https://github.com/nextcloud/nextcloud-docker-dev) environment
(Authentik at `http://authentik.local`, Nextcloud at `http://nextcloud.local`) with a
local Dovecot 2.4 + Postfix pair that accept `XOAUTH2`. The config translates to any
Authentik + Dovecot 2.4 + Postfix deployment. Unlike a provisioning setup this flow
needs no `user_oidc` — Mail is its own OAuth client and the user just consents once.

```
Browser ── add account (alice@example.local) ──> Mail matches a provider by email domain
   │
   └─ popup ─> Authentik /authorize ── sign in + consent ──> redirect back with ?code
                                                                  │
                                        Mail exchanges the code server-side (confidential
                                        client, secret never in the browser)
                                                                  ▼
                                              oc_mail_accounts (access + refresh, encrypted)
                                                                  │ XOAUTH2 (user=<email>,
                                                                  │          auth=Bearer <token>)
                                              ┌───────────────────┴───────────────────┐
                                              ▼                                       ▼
                                        Dovecot :143                            Postfix :587 ──SASL──> Dovecot
                                              │                                    (introspects the token)
                                              ▼
                                        Authentik /introspect/   (Mail's token is trusted
                                                                  via provider federation)
```

The access token must carry an `email` claim, Dovecot keys mailboxes on that claim, and
the XOAUTH2 `user=` must equal it — Mail sends the account email, so the address the user
signs in with at the IdP must match.

### 1. Authentik

Log in as `akadmin` and create **two OAuth2/OpenID providers**.

**Provider "Mail"** (the client Mail authenticates with):

- Client type: *Confidential*, Client ID: `mail`, note the client secret.
- Redirect URI (strict): `http://nextcloud.local/index.php/apps/mail/integration/oidc-auth`
  — this is exactly the "Redirect URL to register with the provider" shown in the
  provider form.
- **Grant types: `authorization_code` and `refresh_token`.** A freshly created Authentik
  provider has an **empty** grant-types list, and then `/authorize` rejects the request
  with *"Invalid grant_type for provider"* — easy to miss.
- Signing key: the self-signed certificate (**RS256**).
- Scopes: `openid`, `email`, `offline_access` (offline_access ⇒ refresh tokens ⇒
  background sync keeps working after the access token expires).

**Provider "Dovecot"** (introspection credential only — no login, no redirect URIs):

- Client type: *Confidential*, Client ID: `dovecot`, note the client secret.

**Federation** (the critical, easy-to-miss step): edit the **Mail** provider →
*Federated OIDC Providers* → add **Dovecot**. The *issuing* provider lists the
*introspecting* one. Without this, Dovecot rejects the Mail-issued token and every
IMAP/SMTP auth fails even though the token itself is valid.

> **Authentik version**: cross-provider introspection requires Authentik ≥ 2026.x
> (verified on 2026.5.5). On 2025.10.x the identical config answers every federated
> introspection with `{"active": false}`. docker-dev pins an older tag by default — set
> `AUTHENTIK_TAG=2026.5.5` in `.env`.

Create an **application** bound to the Mail provider, and a test user whose **email
domain matches the domain you configure in Mail** (e.g. `alice@example.local`).

### 2. Dovecot (2.4)

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
lookup, because the oauth2 passdb can't answer lookups without a token:

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

A complete, ready-to-run local pair is in the [appendix](#appendix-ready-to-run-local-pair).

### 3. Postfix (submission :587)

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

### 4. Nextcloud

No `user_oidc` needed. Bump the IMAP timeout — the first XOAUTH2 login triggers a *cold*
token introspection at the IdP which can exceed Mail's 5s default (symptom: spurious
"denied authentication" followed by a rate-limit lockout):

```sh
occ config:system:set app.mail.imap.timeout --value=20 --type=integer
```

Then in **Admin settings → Groupware → Mail → OpenID Connect integration**, click
**Add OIDC provider** and fill in:

| Setting | Value |
|---|---|
| Display name | anything, e.g. `Authentik local` |
| Email domain | your test user's domain, e.g. `example.local` |
| IMAP host/port | your Dovecot, e.g. `dovecot.local` / `143` / SSL *none* (local rig) |
| SMTP host/port | your Postfix, e.g. `postfix.local` / `587` / SSL *none* (local rig) |
| Discovery endpoint | `http://authentik.local/application/o/<app-slug>/.well-known/openid-configuration` (or tick *Manually define endpoints* and paste the authorize/token URLs) |
| Client ID / secret | `mail` + the secret from step 1 |
| Scopes | `openid email offline_access` |

### 5. Verify

1. In Mail, **Add mail account**, enter the test user's address (e.g.
   `alice@example.local`). The mail servers pre-fill and a single-sign-on notice appears —
   the password field is gone. Hit **Connect to \<provider\>**.
2. A popup opens at Authentik → sign in as the test user and consent → the account is
   created and syncs, no password stored.
3. Send a mail to yourself → lands in INBOX, copy in Sent.
4. Background path — force-expire the token and sync without a session:
   ```sh
   # UPDATE oc_mail_accounts SET oauth_token_ttl = 1 WHERE id = <id>;
   occ mail:account:sync <id>
   ```
   The sync must succeed and `oauth_token_ttl` move into the future (refreshed via the
   stored refresh token against the IdP token endpoint).

### Common test-setup issues

- **`/authorize` redirects back with `error=invalid_request`, Authentik logs "Invalid
  grant_type for provider"** — the Mail provider's grant-types list is empty; add
  `authorization_code` and `refresh_token`.
- **Popup finishes but the account isn't authorized, Dovecot logs introspection
  `active: false`** — the provider federation is missing or reversed (step 1).
- **"Mail server denied authentication" on the first attempt, works later** — cold
  introspection vs. the IMAP timeout, see `app.mail.imap.timeout` above. After 3 failures
  Mail's rate limiter blocks the bucket for up to 3 h (`mail_imap_ratelimit` keys in the
  distributed cache).
- **SMTP `535 invalid base64`** — `line_length_limit` not raised on the submission port.
- **SMTP `553 not owned by user`** — sender ≠ SASL login; aliases need entries in
  `smtpd_sender_login_maps`.
- **`occ mail:account:diagnose`** and `occ config:system:set app.mail.debug --value=true
  --type=boolean` (protocol log in `data/mail-<user>-<id>-imap.log`) are your friends.

### Appendix: ready-to-run local pair

A self-contained Dovecot + Postfix pair for docker-dev. It joins the docker-dev network
so the containers resolve `authentik.local`, and exposes IMAP on `127.0.0.1:1143` and
submission on `127.0.0.1:1587` for host-side testing. No TLS — local test rig, not a
deployment.

**0. Bump docker-dev's Authentik.** In the docker-dev repo root:

```sh
echo 'AUTHENTIK_TAG=2026.5.5' >> .env
docker compose up -d authentik authentik-worker
```

Then create the two providers, federation, application and test user from step 1.

**1. Create the directory** (relative to `data/oidc-mail/` in the docker-dev checkout;
`data/` is bind-mounted so it is reachable from the host):

```sh
mkdir -p data/oidc-mail/dovecot data/oidc-mail/postfix
cd data/oidc-mail
```

**2. `docker-compose.yml`:**

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

**3. `dovecot/dovecot.conf.template`** (the image has no `sed`, so the introspection
secret is substituted on the host and the rendered file is mounted — see step 5):

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

**4. `postfix/` files.**

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

**5. Render the Dovecot config with the introspection secret:**

```sh
# <DOVECOT_CLIENT_SECRET> is the client secret of the "Dovecot" Authentik provider
sed "s|__DOVECOT_CLIENT_SECRET__|<DOVECOT_CLIENT_SECRET>|" \
  dovecot/dovecot.conf.template > dovecot/dovecot.conf
```

**6. Prepare the mail volume and start** (the image's `vmail` user is uid 1000; the named
volume must be writable by it):

```sh
docker compose up -d          # creates the vmail volume
docker run --rm -v oidc-mail_vmail:/var/vmail alpine:3.22 chown 1000:1000 /var/vmail
docker compose up -d --build  # (re)build postfix and start both
docker compose logs -f dovecot postfix   # dovecot should report "starting up for imap, lmtp"
```

**7. Point Mail at it.** In the provider form (step 4): IMAP `dovecot.local` port `143`
SSL *none*, SMTP `postfix.local` port `587` SSL *none*, email domain `example.local`
(matching the test user). Reaching the pair from the **host** instead uses
`127.0.0.1:1143` / `127.0.0.1:1587`.
