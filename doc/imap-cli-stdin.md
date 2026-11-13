<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# IMAP account CLI credentials via stdin

The `mail:account:create-imap` and `mail:account:update-imap` commands accept
`--passwords-from-stdin` to avoid exposing credentials in process arguments.
Provide two newline-terminated lines, in order: the IMAP app password,
then the SMTP app password. Neither may be blank, contain a NUL or embedded
carriage return, or exceed 4096 bytes. The final newline is required.

For **create**, keep the existing positional arguments in their original order,
but supply a literal `-` for *both* password positions, and add
`--passwords-from-stdin`. For **update**, omit `--imap-password` and
`--smtp-password` entirely and add `--passwords-from-stdin`. Both credentials
are updated together in this mode. Mixed modes are rejected without saving.

For example, if a secured provider writes the two newline-terminated passwords
to stdout, the commands can consume its output directly:

```bash
# `read-secrets` is a placeholder for a trusted external secret provider.
read-secrets | php occ mail:account:create-imap \
  test-user Work user@example.test \
  imap.example.test 993 ssl user@example.test - \
  smtp.example.test 465 ssl user@example.test - \
  --passwords-from-stdin

read-secrets | php occ mail:account:update-imap 42 --passwords-from-stdin
```

Run `occ` with the application server's appropriate user and permissions.
`read-secrets` is not a Mail command; use a provider that does not disclose
passwords through arguments, shell history or logs.

Supply stdin from a secured secret source or private pipe, **not** from
`echo 'secret'`, command-line variables, here-documents containing secrets,
`--password` options, command substitutions or temporary plaintext files.
A terminal is deliberately rejected to prevent echoed passwords. Avoid `set -x`
and process/output recording. Stdin is not automatically safe
against privileged process inspection, a compromised orchestrator or the
provider of the secret. This feature does not protect the legacy CLI password
arguments, which remain supported for compatibility.

Credentials still pass through Mail's existing `ICrypto::encrypt()` and account
persistence paths. On invalid, incomplete or truncated input the operation
fails without printing credentials. On failed update, no save is performed.
On create, all required arguments (including both `-` placeholders) must be
provided due to the existing positional CLI interface.

Compatibility: the legacy password arguments and options still work without
`--passwords-from-stdin`. To roll back, install a prior Mail release through
your regular deployment process. This feature has no schema migration and
existing encrypted account credentials remain unchanged.
