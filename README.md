Mail app
============

[![Build Status](https://travis-ci.org/owncloud/mail.svg?branch=master)](https://travis-ci.org/owncloud/mail)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/owncloud/mail/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/owncloud/mail/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/owncloud/mail/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/owncloud/mail/?branch=master)

An email app for [ownCloud](https://owncloud.org) (minimum version 7). At the moment it is a basic IMAP client and in heavy development. (A possibility for the future would be to also have it be a dedicated email server. But that would always be optional, require additional dependencies and is out of scope for now.)

We’re working towards a [0.1 release](https://github.com/owncloud/mail/issues?q=is%3Aopen+is%3Aissue+milestone%3A0.1) at the moment. If you experience any issues or have enhancement suggestions, please report them in our [issue tracker](https://github.com/owncloud/mail/issues).


Maintainers
-----------
- [Thomas Müller](https://github.com/DeepDiver1975)
- [Jan C. Borchardt](https://github.com/jancborchardt)
- [Lukas Reschke](https://github.com/LukasReschke)
- past contributors: [Jakob Sack](https://github.com/jakobsack), [Bart Visscher](https://github.com/bartv2), [Sebastian Schmid](https://github.com/sebastian-schmid)

If you’d like to join, just go through the [issue list](https://github.com/owncloud/mail/issues) and fix some. :)

Developer setup info
--------------------
Just clone this repo into your apps directory. Additionally you need Composer to install dependencies:
```bash
curl -sS https://getcomposer.org/installer | php
php composer.phar install
```
