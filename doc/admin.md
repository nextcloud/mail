# Nextcloud Mail Admin Documentation

## Installation

In your Nextcloud, simply navigate to »Apps«, choose the category »Social & Communication«, find the Mail app and enable it.
Then open the Mail app from the app menu. Put in your mail account credentials and off you go!

## Configuration

Certain advanced or experimental features need to be specifically enabled in your `config.php`:

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

### Autoconfig for your e-mail domain fails

If autoconfiguration for your domain fails, you can create an autoconfig file and place it as https://autoconfig.yourdomain.tld/mail/config-v1.1.xml
For more information please refer to Mozilla's documentation:
https://developer.mozilla.org/en-US/docs/Mozilla/Thunderbird/Autoconfiguration/FileFormat/HowTo
