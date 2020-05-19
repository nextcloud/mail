# Nextcloud Mail Developer Documentation

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
DELETE FROM oc_migrations WHERE app = 'mail';
DROP TABLE oc_mail_accounts;
DROP TABLE oc_mail_aliases;
DROP TABLE oc_mail_coll_addresses;
DROP TABLE oc_mail_attachments;
DROP TABLE oc_mail_mailboxes;
DROP TABLE oc_mail_messages;
DROP TABLE oc_mail_recipients;
```
