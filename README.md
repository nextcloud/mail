# Nextcloud Mail

![Downloads](https://img.shields.io/github/downloads/nextcloud/mail/total.svg)
[![Build Status](https://travis-ci.org/nextcloud/mail.svg?branch=master)](https://travis-ci.org/nextcloud/mail)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/nextcloud/mail/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/nextcloud/mail/?branch=master)
[![PHP Coverage](https://scrutinizer-ci.com/g/nextcloud/mail/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/nextcloud/mail/?branch=master)
[![JavaScript Coverage](https://coveralls.io/repos/github/nextcloud/mail/badge.svg)](https://coveralls.io/github/nextcloud/mail)
[![Dependabot Status](https://api.dependabot.com/badges/status?host=github&repo=nextcloud/mail)](https://dependabot.com)
[![Bountysource](https://img.shields.io/bountysource/team/nextcloud/activity.svg?maxAge=2592000)](https://www.bountysource.com/teams/nextcloud/issues?tracker_ids=44154351)

**💌 A mail app for [Nextcloud](https://nextcloud.com)**

![](screenshots/mail.png)


## Why is this so awesome?

* **🚀 Integration with other Nextcloud apps!** Currently Contacts, Calendar & Files – more to come.
* **📥 Multiple mail accounts!** Personal and company account? No problem, and a nice unified inbox. Connect any IMAP account.
* **🔒 Send & receive encrypted mails!** Using the great [Mailvelope](https://mailvelope.com) browser extension.
* **🙈 We’re not reinventing the wheel!** Based on the great [Horde](http://horde.org) libraries.
* **📬 Want to host your own mail server?** We don’t have to reimplement this as you could set up [Mail-in-a-Box](https://mailinabox.email)!

And in the works for the [coming versions](https://github.com/nextcloud/mail/milestones/):
* 📑 Proper grouping of message threads
* 🗄️ Folder management

If you experience any issues or have any suggestions for improvement, use the [issue tracker](https://github.com/nextcloud/mail/issues). Please follow the [issue template chooser](https://github.com/nextcloud/mail/issues/new/choose) so we get the info needed to debug and fix the problem. Thanks!

## Get on board
For new contributors, please check out [ContributingToNextcloudIntroductoryWorkshop](https://github.com/sleepypioneer/ContributingToNextcloudIntroductoryWorkshop)

## Development setup

Just clone this repo into your apps directory ([Nextcloud server](https://github.com/nextcloud/server#running-master-checkouts) installation needed). Additionally, [npm](https://www.npmjs.com/) to fetch [Node.js](https://nodejs.org/en/download/package-manager/) is needed for installing JavaScript dependencies.

Once npm and Node.js are installed, PHP and JavaScript dependencies can be installed by running:
```bash
make dev-setup
```

We are also available on [our public Mail development chat](https://cloud.nextcloud.com/call/89474m7g), if you want to join the development discussion. Please report bugs [here on Github](https://github.com/nextcloud/mail/issues/new/choose) and open any questions and support tickets at [the community forum](https://help.nextcloud.com/c/apps/mail).

## Documentation

Need help? Check out our documentation. It's split into three parts.
* [Admin documentation](doc/admin.md) (installation, configuration, troubleshooting)
* [Developer documentation](doc/developer.md) (developer setup, nightly builds)
* [User documentation](doc/user.md) (usage, keyboard shortcuts)

## Maintainers

[Christoph Wurst](https://github.com/ChristophWurst), [Jan-Christoph Borchardt](https://github.com/jancborchardt), [Steffen Lindner](https://github.com/Gomez) [and many more](https://github.com/nextcloud/mail/graphs/contributors)

If you’d like to join, just run through the [issue list](https://github.com/nextcloud/mail/issues) and fix some. :)
