Mail app
============

Maintainers:
------------
- [Thomas Müller](https://github.com/DeepDiver1975)
- [Bart Visscher](https://github.com/bartv2)
- [Jan C. Borchardt](https://github.com/jancborchardt)
- [Sebastian Schmid](https://github.com/sebastian-schmid)

Alumni:
--------
- [Jakob Sack](https://github.com/jakobsack)

Contact us if you'd like to join!

Developer setup info:
---------------------
### Master branch:
Just clone this repo into your apps directory. Additionally you need Composer to install dependencies:
```bash
curl -sS https://getcomposer.org/installer | php
php composer.phar install
```

### appframework branch:
Get the latest version of the appframework into the apps directory:
```bash
git clone git://github.com/owncloud/appframework.git
```
Enable the appframework in the app settings of ownCloud.

Get the lastest version of the rework:
```bash
git clone git://github.com/owncloud/mail.git
cd mail
git checkout appframework
```
