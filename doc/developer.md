# Nextcloud Mail Developer Documentation

[TOC]

## Developer setup info

Just clone this repo into your apps directory (Nextcloud server installation needed). Additionally, [npm](https://www.npmjs.com/) to fetch [Node.js](https://nodejs.org/en/download/package-manager/) is needed for installing JavaScript dependencies.

Once npm and Node.js are installed, PHP and JavaScript dependencies can be installed by running:
```bash
make install-composer-deps
make optimize-js
```

## Nightly builds

Instead of setting everything up manually, you can just [download the nightly builds](https://nightly.portknox.net/mail/?C=M;O=D) instead. These builds are updated every 24 hours, and are pre-configured with all the needed dependencies.

1. Download
2. Extract the tar archive to 'path-to-nextcloud/apps'
3. Navigate to »Apps«, choose the category »Productivity«, find the Mail app and enable it.

The nightly builds are provided by [Portknox.net](https://portknox.net)

## Resetting the app
Connect to your database and run the following commands (`oc_` is the default table prefix):
```sql
DELETE FROM oc_appconfig WHERE appid = 'mail';
DROP TABLE oc_mail_accounts;
DROP TABLE oc_mail_aliases;
DROP TABLE oc_mail_collected_addresses;
DROP TABLE oc_mail_attachments;
```