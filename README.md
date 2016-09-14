# Nextcloud Mail

[![Build Status](https://travis-ci.org/nextcloud/mail.svg?branch=master)](https://travis-ci.org/nextcloud/mail)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/nextcloud/mail/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/nextcloud/mail/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/nextcloud/mail/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/nextcloud/mail/?branch=master)
[![PHP Dependency Status](https://www.versioneye.com/user/projects/54e50fadd1ec5734f400078a/badge.svg?style=flat)](https://www.versioneye.com/user/projects/54e50fadd1ec5734f400078a)
[![JS Dependency Status](https://www.versioneye.com/user/projects/567bd06ba7c90e003500033f/badge.svg?style=flat)](https://www.versioneye.com/user/projects/567bd06ba7c90e003500033f)

**An email app for [Nextcloud](https://nextcloud.com)**

![](screenshots/mail.png)


## Why is this so awesome?

* :rocket: **Integration with other Nextcloud apps!** Currently Contacts, Calendar & Files – more to come.
* :inbox_tray: **Multiple mail accounts!** Personal and company account? No problem, and a nice unified inbox.
* :lock: **Send & receive encrypted emails!** Using the great [Mailvelope](https://mailvelope.com) browser extension.
* :see_no_evil: **We’re not reinventing the wheel!** Based on the great [Horde](http://horde.org) libraries.

And in the works for the [coming versions](https://github.com/nextcloud/mail/milestones/):
* :books: Proper grouping of message threads
* :zap: Caching to make everything faster
* :paperclip: Even better attachment support
* :package: Folder management & moving mails

## Installation

In your Nextcloud, simply navigate to »Apps«, choose the category »Productivity«, find the Mail app and enable it.
Then open the Mail app from the app menu. Put in your email account credentials and off you go!

If you experience any issues or have enhancement suggestions you can report them in our [issue tracker](https://github.com/nextcloud/mail/issues). Please follow the [issue template](https://raw.githubusercontent.com/nextcloud/mail/master/issue_template.md) so we get the info we need to be able to debug and fix the problem. Thanks!


## Maintainers

[Christoph Wurst](https://github.com/ChristophWurst), [Thomas Müller](https://github.com/DeepDiver1975), [Jan-Christoph Borchardt](https://github.com/jancborchardt), [Steffen Lindner](https://github.com/Gomez) [and many more](https://github.com/nextcloud/mail/graphs/contributors)

If you’d like to join, just go through the [issue list](https://github.com/nextcloud/mail/issues) and fix some. :) We’re also in [#nextcloud-mail on freenode IRC](https://webchat.freenode.net/?channels=nextcloud-mail).

## Troubleshooting

### Gmail

If you can not access your Gmail account use https://accounts.google.com/DisplayUnlockCaptcha to unlock your account.

### Outlook.com

If you can not access your Outlook.com account try to enable the 'Two-Factor Verification' (https://account.live.com/proofs/Manage) and setup an app password (https://account.live.com/proofs/AppPassword), which you then use for the Nextcloud Mail app.

### Dovecot IMAP

If your Dovecot IMAP server prefixes all folders with `INBOX`, Nextcloud Mail does not work correctly.

Check `/etc/dovecot/dovecot.conf`:

```
namespace inbox {
        separator = .
        # All folders prefixed
        # prefix = INBOX.
        prefix =
        inbox = yes
        type = private
}
```


## Developer setup info

Just clone this repo into your apps directory (Nextcloud server installation needed). Additionally,  [nodejs and npm](https://nodejs.org/en/download/package-manager/) are needed for installing JavaScript dependencies.

Once node and npm are installed, PHP and JavaScript dependencies can be installed by running
```bash
make install-composer-deps
make optimize-js
```

### Resetting the app
Connect to your database and run the following commands (`oc_` is the default table prefix):
```sql
DELETE FROM oc_appconfig WHERE appid = 'mail';
DROP TABLE oc_mail_accounts;
DROP TABLE oc_mail_collected_addresses;
```


## Configuration

Certain advanced or experimental features need to be specifically enabled in your `config.php`:

### Debug mode
You can enable IMAP and SMTP backend logging. A horde_imap.log for IMAP and horde_smtp.log for SMTP will appear in the same directory as your nextcloud.log.
#### IMAP logging:
```php
'app.mail.imaplog.enabled' => true
```
#### SMTP logging:
```php
'app.mail.smtplog.enabled' => true
```

### Timeouts:
Depending on your mail host, it may be necessary to increase your IMAP and/or SMTP timeout settings. Currently IMAP defaults to 20 seconds and SMTP defaults to 2 seconds. They can be changed with.

#### IMAP timeout:
```php
'app.mail.imap.timeout' => 20
```
#### SMTP timeout:
```php
'app.mail.smtp.timeout' => 2
```
### Use php-mail for mail sending
You can use the php mail function to send mails. This is needed for some webhosters (1&1 (1und1)):
```php
'app.mail.transport' => 'php-mail'
```
