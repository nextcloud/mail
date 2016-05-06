# ownCloud Mail

[![Build Status](https://travis-ci.org/owncloud/mail.svg?branch=master)](https://travis-ci.org/owncloud/mail)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/owncloud/mail/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/owncloud/mail/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/owncloud/mail/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/owncloud/mail/?branch=master)
[![PHP Dependency Status](https://www.versioneye.com/user/projects/54e50fadd1ec5734f400078a/badge.svg?style=flat)](https://www.versioneye.com/user/projects/54e50fadd1ec5734f400078a)
[![JS Dependency Status](https://www.versioneye.com/user/projects/567bd06ba7c90e003500033f/badge.svg?style=flat)](https://www.versioneye.com/user/projects/567bd06ba7c90e003500033f)
[![Bountysource](https://img.shields.io/bountysource/team/owncloud/activity.svg)](https://www.bountysource.com/teams/owncloud/issues?tracker_ids=216487)

**An email app for [ownCloud](https://owncloud.org) (minimum version 8.0 & PHP 5.4).** 

![](https://raw.githubusercontent.com/owncloud/screenshots/master/mail/mail.png)


## Why is this so awesome?

* :rocket: **Integration with other ownCloud apps!** Currently Contacts, Calendar & Files – more to come.
* :inbox_tray: **Multiple mail accounts!** Personal and company account? No problem, and a nice unified inbox.
* :lock: **Send & receive encrypted emails!** Using the great [Mailvelope](https://mailvelope.com) browser extension.
* :see_no_evil: **We’re not reinventing the wheel!** Based on the great [Horde](http://horde.org) libraries.

And in the works for the [coming versions](https://github.com/owncloud/mail/milestones/):
* :books: [Proper grouping of message threads](https://github.com/owncloud/mail/issues/21) 
* :zap: [Caching to make everything faster](https://github.com/owncloud/mail/issues/480)
* :paperclip: [Even better attachment support](https://github.com/owncloud/mail/issues/462) 
* :package: [Folder management & moving mails](https://github.com/owncloud/mail/issues/411) 

## Installation

In your ownCloud, simply navigate to »Apps«, choose the category »Productivity«, find the Mail app and enable it.
Then open the Mail app from the app menu. Put in your email account credentials and off you go!

If you experience any issues or have enhancement suggestions you can report them in our [issue tracker](https://github.com/owncloud/mail/issues). Please follow the [issue template](https://raw.githubusercontent.com/owncloud/mail/master/issue_template.md) so we get the info we need to be able to debug and fix the problem. Thanks!


## Maintainers

Active: [Thomas Müller](https://github.com/DeepDiver1975), [Jan-Christoph Borchardt](https://github.com/jancborchardt), [Christoph Wurst](https://github.com/ChristophWurst), [Steffen Lindner](https://github.com/Gomez), [Lukas Reschke](https://github.com/LukasReschke), [Thomas Imbreckx](https://github.com/zinks-), [Robin McCorkell](https://github.com/Xenopathic), [Clement Wong](https://github.com/clementhk), [Colm O’Neill](https://github.com/colmoneill), [Alexander Weidinger](https://github.com/irgendwie), [Hendrik Leppelsack](https://github.com/Henni), [Plato Leung](https://github.com/PoPoutdoor) & [Jakob Sack](https://github.com/jakobsack)

Past contributors: [Bart Visscher](https://github.com/bartv2), [Sebastian Schmid](https://github.com/sebastian-schmid)

If you’d like to join, just go through the [issue list](https://github.com/owncloud/mail/issues) and fix some. :) We’re also in [#owncloud-mail on freenode IRC](https://webchat.freenode.net/?channels=owncloud-mail).


## Troubleshooting

### Gmail

If you can not access your Gmail account use https://accounts.google.com/DisplayUnlockCaptcha to unlock your account.

### Outlook.com

If you can not access your Outlook.com account try to enable the 'Two-Factor Verification' (https://account.live.com/proofs/Manage) and setup an app password (https://account.live.com/proofs/AppPassword), which you then use for the ownCloud Mail app.

### Dovecot IMAP

If your Dovecot IMAP server prefixes all folders with `INBOX`, ownCloud Mail does not work correcty. 

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

Just clone this repo into your apps directory ([ownCloud core installation needed](https://doc.owncloud.org/server/8.2/developer_manual/general/devenv.html)). Additionally,  [npm](https://docs.npmjs.com/getting-started/installing-node) is needed for installing JavaScript dependencies. On Debian and Ubuntu Systems it can be installed with the following command:
```bash
sudo apt-get install nodejs-legacy npm
```
Once npm is installed, PHP and JavaScript dependencies can be installed by running
```bash
make install-composer-deps
make optimize-js
```

### Nightly builds

Instead of setting everything up manually, you can just [download the nightly builds](https://en0ch.se/index.php/s/3v5J3XXeQCCp73p) instead. These builds are updated every 12 hours - 00:00 (12:00 AM) and 12:00 (12:00 PM) UTC+0200, and are pre-configured with all the needed dependencies.

1. Download
2. Extract the tar archive to 'path-to-owncloud/apps'
3. Navigate to »Apps«, choose the category »Productivity«, find the Mail app and enable it.

The nightly builds are provided by [Daniel Hansson](https://github.com/enoch85)

### Resetting the app
Connect to your database and run the following commands (`oc_` is the default table prefix):
```sql
DELETE FROM oc_appconfig WHERE appid = 'mail';
DROP TABLE oc_mail_accounts;
```

Go to ownCloud Mail in the browser and run this from the developer console to clear the cache:
```
localStorage.clear();
```


## Configuration

Certain advanced or experimental features need to be specifically enabled in your `config.php`:

### Debug mode
You can enable IMAP and SMTP backend logging. A horde_imap.log for IMAP and horde_smtp.log for SMTP will appear in the same directory as your owncloud.log.
#### IMAP logging:
```php
'app.mail.imaplog.enabled' => true
```
#### SMTP logging:
```php
'app.mail.smtplog.enabled' => true
```

### Server-side caching
Mailbox messages and accounts can be cached on the ownCloud server to reduce mail server load:
This requires a valid memcache to be configured
```php
'app.mail.server-side-cache.enabled' => true
```

### Use php-mail for mail sending
You can use the php mail function to send mails. This is needed for some webhosters (1&1 (1und1)):
```php
'app.mail.transport' => 'php-mail'
```
