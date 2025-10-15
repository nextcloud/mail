<!--
  - SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-FileCopyrightText: 2013-2016 ownCloud, Inc.
  - SPDX-License-Identifier: AGPL-3.0-only
-->
# Nextcloud Mail

![Downloads](https://img.shields.io/github/downloads/nextcloud/mail/total.svg)
[![REUSE status](https://api.reuse.software/badge/github.com/nextcloud/mail)](https://api.reuse.software/info/github.com/nextcloud/mail)
![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/nextcloud/mail/test.yml)
[![Codecov](https://img.shields.io/codecov/c/github/nextcloud/mail)](https://codecov.io/gh/nextcloud/mail)
[![Renovate](https://img.shields.io/badge/renovate-enabled-brightgreen.svg)](https://github.com/nextcloud/mail/issues/7948)

**💌 A mail app for [Nextcloud](https://nextcloud.com)**

![](screenshots/mail.png)


## Why is this so awesome?

* **🚀 Integration with other Nextcloud apps!** Currently Contacts, Calendar, Files & Tasks – more to come.
* **📥 Multiple mail accounts!** Personal and company account? No problem, and a nice unified inbox. Connect any IMAP account.
* **🔒 Send & receive encrypted mails!** Using the great [Mailvelope](https://mailvelope.com) browser extension or the built-in support for S/MIME encryption and signatures.
* **📑 Message threads!** Now we have proper grouping of message threads.
* **🗄️ Mailbox management!** You can edit, delete, add submailboxes and more.
* **🙈 We’re not reinventing the wheel!** Based on the great [Horde](https://www.horde.org) libraries.
* **📬 Want to host your own mail server?** We don’t have to reimplement this as you could set up [Mail-in-a-Box](https://mailinabox.email), [Stalwart](https://stalw.art) or [Dovecot](https://www.dovecot.org)!

If you experience any issues or have any suggestions for improvement, use the [issue tracker](https://github.com/nextcloud/mail/issues). Please follow the [issue template chooser](https://github.com/nextcloud/mail/issues/new/choose) so we get the info needed to debug and fix the problem. Thanks!

## Ethical AI Rating

### Priority Inbox

**Rating:** 🟢

Positive:
* The software for training and inferencing of this model is open source.
* The model is created and trained on-premises based on the user's own data.
* The training data is accessible to the user, making it possible to check or correct for bias or optimise the performance and CO2 usage.

### Thread Summaries (opt-in)

**Rating:** 🟢/🟡/🟡/🔴

The rating depends on the installed text processing backend. See [the rating overview](https://docs.nextcloud.com/server/latest/admin_manual/ai/index.html) for details.

Learn more about the Nextcloud Ethical AI Rating [in our blog](https://nextcloud.com/blog/nextcloud-ethical-ai-rating/).

## Maintainers

* [@ChristophWurst](https://github.com/ChristophWurst)
* [@GretaD](https://github.com/GretaD)
* [@kesselb](https://github.com/kesselb)

## Installation

The app is distributed through the [app store](https://apps.nextcloud.com/apps/mail) and you can install it [right from your Nextcloud installation](https://docs.nextcloud.com/server/stable/admin_manual/apps_management.html).

Release tarballs are hosted at https://github.com/nextcloud-releases/mail/releases.

## Get on board
For new contributors, please check out [ContributingToNextcloudIntroductoryWorkshop](https://github.com/sleepypioneer/ContributingToNextcloudIntroductoryWorkshop)

## Development setup

Just clone this repo into your apps directory ([Nextcloud server](https://github.com/nextcloud/server#running-master-checkouts) installation needed). Additionally, [npm](https://www.npmjs.com/) to fetch [Node.js](https://nodejs.org/en/download/package-manager/) is needed for installing JavaScript dependencies
and [composer](https://getcomposer.org/download/) is needed for dependency management in PHP.

Once npm and Node.js are installed, PHP and JavaScript dependencies can be installed by running:
```bash
make dev-setup
```

We are also available on [our public Mail development chat](https://cloud.nextcloud.com/call/5qb8fujz), if you want to join the development discussion. Please report bugs [here on Github](https://github.com/nextcloud/mail/issues/new/choose) and open any questions and support tickets at [the community forum](https://help.nextcloud.com/c/apps/mail).

## Documentation

Need help? Check out our documentation. It's split into three parts.
* [Admin documentation](doc/admin.md) (installation, configuration, troubleshooting)
* [Developer documentation](doc/developer.md) (developer setup, nightly builds)
* [User documentation](doc/user.md) (usage, keyboard shortcuts)

## Credits
This project uses [CKEditor](https://ckeditor.com), which is licensed under the [GPLv2](https://www.gnu.org/licenses/old-licenses/gpl-2.0.html).
