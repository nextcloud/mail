# Nextcloud Mail Admin Documentation

## Installation

In your Nextcloud, simply navigate to »Apps«, choose the category »Social & Communication«, find the Mail app and enable it.
Then open the Mail app from the app menu.

## Configuration

### Local IMAP and SMTP servers

By default, Nextcloud does not allow local hostnames and IP addresses as remote servers. This includes IMAP, SMTP and Sieve servers
like `localhost`, `mx.local` and `10.0.0.3`. This check can be disabled with via config/config.php

```php
'allow_local_remote_servers' => true,
```

### Attachment size limit

Admins can prevent users from attaching large attachments to their emails. Users will be asked to use link shares instead.

```php
'app.mail.attachment-size-limit' => 3*1024*1024,
```

The unit is bytes. The example about with limit to 3MB attachments. The default is 0 bytes which means no upload limit.

### Timeouts
Depending on your mail host, it may be necessary to increase your IMAP and/or SMTP timeout threshold. Currently IMAP defaults to 20 seconds and SMTP defaults to 2 seconds. They can be changed as follows:

#### IMAP timeout
```php
'app.mail.imap.timeout' => 20
```
#### SMTP timeout
```php
'app.mail.smtp.timeout' => 2
```
#### Sieve timeout
```php
'app.mail.sieve.timeout' => 2
```

### Use php-mail for sending mail
You can use the php-mail function to send mails. This is needed for some webhosters (1&1 (1und1)):
```php
'app.mail.transport' => 'php-mail'
```
### Disable TLS verification for IMAP/SMTP
Turn off TLS verfication for IMAP/SMTP. This happens globally for all accounts and is only needed in edge cases like with email servers that have a self-signed certificate.
```php
'app.mail.verify-tls-peer' => false
```

### Anti-abuse alerts

The app can write alerts to the logs when users send messages to a high number of recipients or sends a high number of messages for a short period of time. These events might indicate that the account is abused for sending spam messages.

To enable anti-abuse alerts, you'll have to set a few configuration options [via occ](https://docs.nextcloud.com/server/stable/admin_manual/configuration_server/occ_command.html).

```bash
# Turn alerts on
occ config:app:set mail abuse_detection --value=on
# Turn alerts off
occ config:app:set mail abuse_detection --value=off

# Alert when 50 or more recipients are used for one single message
occ config:app:set mail abuse_number_of_recipients_per_message_threshold --value=50

# Alerts can be configured for three intervals: 15m, 1h and 1d
# Alert when more than 10 messages are sent in 15 minutes
occ config:app:set mail abuse_number_of_messages_per_15m --value=10
# Alert when more than 30 messages are sent in one hour
occ config:app:set mail abuse_number_of_messages_per_1h --value=30
# Alert when more than 100 messages are sent in one day
occ config:app:set mail abuse_number_of_messages_per_1d --value=100
```

## Google OAuth

This app can allow users to connect their Google accounts with OAuth. This makes it possible to use accounts without 2FA or app password.

1) [Create authorization credentials](https://developers.google.com/identity/protocols/oauth2/web-server#prerequisites). You will receive a client ID and a client secret.
2) Open the Nextcloud settings page. Navigate to *Groupware* and scroll down to *Gmail integration*. Enter and save the client ID and client secret.

## Troubleshooting

### Logging

The Nextcloud mail app offers an extensive logging system to make it easier identifying and tracking down bugs.

Please enable [debug mode](https://docs.nextcloud.com/server/stable/developer_manual/getting_started/devenv.html#enabling-debug-mode) and set the log [level to debug](https://docs.nextcloud.com/server/stable/admin_manual/configuration_server/logging_configuration.html) in your admin settings. Then try to reproduce your issue and take another look at `data/nextcloud.log`, `data/horde_imap.log` and `data/horde_smtp.log`.

Make sure to remove any sensitive data before posting it publicly. Reset log levels and debug mode to the previous values when you are done debugging.

### Database insert problems on MySQL

If Mail fails to insert new rows for messages (`oc_mail_messages`), recipients (`oc_mail_recipients`) or similar tables, you are possibly not using the 4 byte support. See [the Nextcloud Admin Manual](https://docs.nextcloud.com/server/stable/admin_manual/configuration_database/mysql_4byte_support.html) on how to update your database configuration.

### Timeout and other connectivity issues

You can use OpenSSL to test and benchmark the connection from your Nextcloud host to the IMAP/SMTP host.

```bash
openssl s_time -connect imap.domain.tld:993
```

The output should look similar to this:

```
Collecting connection statistics for 30 seconds
***************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************

483 connections in 0.94s; 513.83 connections/user sec, bytes read 0
483 connections in 31 real seconds, 0 bytes read per connection


Now timing with session id reuse.
starting
*****************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************

497 connections in 0.97s; 512.37 connections/user sec, bytes read 0
497 connections in 31 real seconds, 0 bytes read per connection
```

### Get account IDs

For many troubleshooting instructions you need to know the `id` of a user's account. You can acquire this through the database, but it's also possible to utilize the account export command of [occ](https://docs.nextcloud.com/server/stable/admin_manual/configuration_server/occ_command.html) if you know the UID of the user:

```bash
php -f occ mail:account:export user123
```

The output will look similar to this:

```
Account 1393:
- E-Mail: christoph@domain.com
- Name: Christoph Wurst
- IMAP user: christoph
- IMAP host: mx.domain.com:993, security: ssl
- SMTP user: christoph
- SMTP host: mx.domain.com:587, security: tls
```

In this example, `1393` is the *account ID*.

### Manual account synchronization and threading

To troubleshoot synchronization or threading problems it's helpful to run the sync from the command line while the user does not use the web interface (reduces chances of a conflict):

```bash
php -f occ mail:account:sync -vvv 1393
```

1393 represents the [account ID](#get-account-ids).

The command offers a ``--force`` option. Use it wisely as it doesn't perform the same path a typical web triggered sync request would do.

The output will look similar to this:

```
[debug] Skipping mailbox sync for Archive
[debug] Skipping mailbox sync for Archive.2020
[debug] partial sync 1393:Drafts - get all known UIDs took 0s
[debug] partial sync 1393:Drafts - get new messages via Horde took 0s
[debug] partial sync 1393:Drafts - persist new messages took 0s
[debug] partial sync 1393:Drafts - get changed messages via Horde took 0s
[debug] partial sync 1393:Drafts - persist changed messages took 0s
[debug] partial sync 1393:Drafts - get vanished messages via Horde took 0s
[debug] partial sync 1393:Drafts - persist new messages took 0s
[debug] partial sync 1393:Drafts took 0s
[debug] partial sync 1393:INBOX - get all known UIDs took 0s
[debug] partial sync 1393:INBOX - get new messages via Horde took 0s
[debug] partial sync 1393:INBOX - classified a chunk of new messages took 1s
[debug] partial sync 1393:INBOX - persist new messages took 0s
[debug] partial sync 1393:INBOX - get changed messages via Horde took 1s
[debug] partial sync 1393:INBOX - persist changed messages took 0s
[debug] partial sync 1393:INBOX - get vanished messages via Horde took 0s
[debug] partial sync 1393:INBOX - persist new messages took 0s
[debug] partial sync 1393:INBOX took 2s
[debug] Skipping mailbox sync for Sent
[debug] Skipping mailbox sync for Sentry
[debug] Skipping mailbox sync for Trash
[debug] Account 1393 has 19417 messages for threading
[debug] Threading 19417 messages - build ID table took 1s
[debug] Threading 19417 messages - build root container took 0s
[debug] Threading 19417 messages - free ID table took 0s
[debug] Threading 19417 messages - prune containers took 0s
[debug] Threading 19417 messages - group by subject took 0s
[debug] Threading 19417 messages took 1s
[debug] Account 1393 has 9839 threads
[debug] Account 1393 has 0 messages with a new thread IDs
62MB of memory used
```

### Export threading data

If you encounter an issue with threading, e.g. messages that are supposed to group are not grouping, you can export the data the algorithm will use to build threads. We are dealing with sensitive data here, but the command will optionally redact the data with the ``--redact`` switch. The exported data will then only keep the original database IDs, the rest of the data is randomized. While this format doesn't give anyone infos about your email, it still contains metadata about how many messages you have and in what relation those are. Please consider this before posting the data online.

```bash
php -f occ mail:account:export-threads 1393
```

1393 represents the [account ID](#get-account-ids).

The output will look similar to this:

```json
[
    {
        "subject": "83379f9bc36915d5024de878386060b5@redacted",
        "id": "2def0f3597806ecb886da1d9cc323a7c@redacted",
        "references": [],
        "databaseId": 261535
    },
        {
        "subject": "Re: 1d4725ae1ac4e4798b541ca3f3cdce6e@redacted",
        "id": "ce9e248333c44a5a64ccad26f2550f95@redacted",
        "references": [
            "bc95cbaff3abbed716e1d40bbdaa58a0@redacted",
            "8651a9ac37674907606c936ced1333d7@redacted",
            "4a87e94522a3cf26dba8977ae901094d@redacted",
            "a3b30430b1ccb41089170eecbe315d3a@redacted",
            "8e9f60369dce3d8b2b27430bd50ec46d@redacted",
            "46cfa6e729ff329e6ede076853154113@redacted",
            "079e7bc89d69792839a5e1831b1cbc80@redacted",
            "079e7bc89d69792839a5e1831b1cbc80@redacted"
        ],
        "databaseId": 262086
    },
    {
        "subject": "Re: 1d4725ae1ac4e4798b541ca3f3cdce6e@redacted",
        "id": "8dd0e0ef2f7ab100b75922489ff26306@redacted",
        "references": [
            "bc95cbaff3abbed716e1d40bbdaa58a0@redacted",
            "8651a9ac37674907606c936ced1333d7@redacted",
            "4a87e94522a3cf26dba8977ae901094d@redacted",
            "a3b30430b1ccb41089170eecbe315d3a@redacted",
            "8e9f60369dce3d8b2b27430bd50ec46d@redacted",
            "46cfa6e729ff329e6ede076853154113@redacted",
            "079e7bc89d69792839a5e1831b1cbc80@redacted",
            "ce9e248333c44a5a64ccad26f2550f95@redacted",
            "ce9e248333c44a5a64ccad26f2550f95@redacted"
        ],
        "databaseId": 262087
    },
]
```

It's recommended practice to pipe the export into a file, which you can later share with the Mail app community and developers:

```bash
php -f occ mail:account:export-threads 1393 | gzip -c > /tmp/nextcloud-mail-threads-1393.json.gz
```

### Gmail

If you can not access your Gmail account use https://accounts.google.com/DisplayUnlockCaptcha to unlock your account.

### Outlook.com

If you can not access your Outlook.com account try to enable the 'Two-Factor Verification' (https://account.live.com/proofs/Manage) and set up an app password (https://account.live.com/proofs/AppPassword), which you then use for the Nextcloud Mail app.

### Autoconfig for your e-mail domain fails

If autoconfiguration for your domain fails, you can create an autoconfig file and place it as https://autoconfig.yourdomain.tld/mail/config-v1.1.xml
For more information please refer to Mozilla's documentation:
https://developer.mozilla.org/en-US/docs/Mozilla/Thunderbird/Autoconfiguration/FileFormat/HowTo
