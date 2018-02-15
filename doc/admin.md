# Nextcloud Mail Admin Documentation

## Installation

In your Nextcloud, simply navigate to »Apps«, choose the category »Social & Communication«, find the Mail app and enable it.
Then open the Mail app from the app menu. Put in your mail account credentials and off you go!

## Configuration

Certain advanced or experimental features need to be specifically enabled in your `config.php`:

### Automatic account creation

In cases where an external user back-end is used for both your Nextcloud and your mail server you may want to have imap accounts set up automatically for your users.

### Available patterns

Two patterns are available to automatically construct credentials:
* `%USERID%`, e.g. `jan`
* `%EMAIL%`, e.g. `jan@domain.tld`

### Minimal configuration

The following minimal configuration will add such an account as soon as the user logs in. The login password is used for the IMAP and SMTP authentication.

Note: Valid values for SSL are `'none'`, `'ssl'` and `'tls'`.

```
  'app.mail.accounts.default' => [
    'email' => '%USERID%@domain.tld',
    'imapHost' => 'imap.domain.tld',
    'imapPort' => 993,
    'imapSslMode' => 'ssl',
    'smtpHost' => 'smtp.domain.tld',
    'smtpPort' => 486,
    'smtpSslMode' => 'tls',
  ],
```

### Advanced configuration

In case you have to tweak IMAP and SMTP username, you can do that too.

```
  'app.mail.accounts.default' => [
    'email' => '%USERID%@domain.tld',
    'imapHost' => 'imap.domain.tld',
    'imapPort' => 993,
    'imapUser' => '%USERID%@domain.tld',
    'imapSslMode' => 'ssl',
    'smtpHost' => 'smtp.domain.tld',
    'smtpPort' => 486,
    'smtpUser' => '%USERID%@domain.tld',
    'smtpSslMode' => 'tls',
  ],
```

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
### Use php-mail for sending mail
You can use the php-mail function to send mails. This is needed for some webhosters (1&1 (1und1)):
```php
'app.mail.transport' => 'php-mail'
```

## Troubleshooting

### Gmail

If you can not access your Gmail account use https://accounts.google.com/DisplayUnlockCaptcha to unlock your account.

### Outlook.com

If you can not access your Outlook.com account try to enable the 'Two-Factor Verification' (https://account.live.com/proofs/Manage) and set up an app password (https://account.live.com/proofs/AppPassword), which you then use for the Nextcloud Mail app.
