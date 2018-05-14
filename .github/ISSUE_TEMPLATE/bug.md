---
name: 🐛 Bug
about: Have you encountered a bug?
version: 0.1
---

### Expected behavior
Tell us what should happen

### Actual behavior
Tell us what happens instead

### Mail app

**Mail app version:** (see apps admin page, e.g. 0.5.3)

**Mailserver or service:** (e.g. Outlook, Yahoo, Gmail, Exchange,...)

**Number of accounts:** (e.g. 1 or 2)


### Server configuration

**Operating system**: (e.g. Debian 8)

**Web server:** (e.g. Apache, Nginx,...)

**Database:** (e.g. MariaDB, SQLite, PostgreSQL,...)

**PHP version:** (e.g. 7.0)

**Nextcloud Version:** (see admin page, e.g. 13.0.2)

**Updated from an older version or fresh install:**

**List of activated apps:**
```
If you have access to your command line run e.g.:
sudo -u www-data php occ app:list
from within your server installation folder
```

**The content of config/config.php:**
```
If you have access to your command line run e.g.:
sudo -u www-data php occ config:list system
from within your Nextcloud installation folder

or

Insert your config.php content here
Make sure to remove all sensitive content such as passwords. (e.g. database password, passwordsalt, secret, smtp password, …)
```

#### Client configuration
**Browser:** (e.g. Firefox 48)

**Operating system:** (e.g. Arch Linux)

#### Logs
##### Web server error log
```
Insert your webserver log here (e.g. /var/log/apache2/...)
```

##### Server log (data/nextcloud.log)
```
Insert your server log here
```

##### IMAP log (data/horde_imap.log)
```
Enable debug mode and insert your horde IMAP log here, see https://docs.nextcloud.com/server/13/developer_manual/general/debugging.html#debug-mode
```

##### SMTP log (data/horde_smtp.log)
```
Enable debug mode and insert your horde IMAP log here, see https://docs.nextcloud.com/server/13/developer_manual/general/debugging.html#debug-mode
```

##### Browser log
```
Insert your browser log here, this could for example include:

a) The javascript console log
b) The network log 
```
