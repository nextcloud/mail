### Steps to reproduce
1.
2.
3.

### Expected behaviour
Tell us what should happen

### Actual behaviour
Tell us what happens instead

### Mail app

**Mail app version:** (see apps admin page)

**Mailserver or service:** (e.g. Outlook, Yahoo, Gmail, Exchange,...)

**Transport security - IMAP:** (None, SSL, TLS, STARTTLS) 

**Transport security - SMTP:** (None, SSL, TLS, STARTTLS)

**Number of accounts:**

**Mail app version build date:** (only if you are using a Nightly Build)


### Server configuration
<!--
You can use the Issue Template application to prefill most of the required information: https://apps.nextcloud.com/apps/issuetemplate
-->
**Operating system**:

**Web server:**

**Database:**

**PHP version:**

**Version:** (see admin page)

**Updated from an older version or fresh install:**

**List of activated apps:**

```
If you have access to your command line run e.g.:
sudo -u www-data php occ app:list
from within your server installation folder
```

**The content of config/config.php:**

<details>
```
If you have access to your command line run e.g.:
sudo -u www-data php occ config:list system
from within your Nextcloud installation folder

or

Insert your config.php content here
Make sure to remove all sensitive content such as passwords. (e.g. database password, passwordsalt, secret, smtp password, …)
```
</details>

#### Client configuration
**Browser:**

**Operating system:**

#### Logs
##### Web server error log
```
Insert your webserver log here
```

##### Server log (data/nextcloud.log)
<details>
```
Insert your server log here
```
</details>

##### Horde IMAP log (data/horde_imap.log)
<details>
```
Insert your horde IMAP log here, see https://github.com/nextcloud/mail#debug-mode
```
</details>

##### Horde SMTP log (data/horde_smtp.log)
<details>
```
Insert your horde SMTP log here, see https://github.com/nextcloud/mail#debug-mode
```
</details>

##### Browser log
<details>
```
Insert your browser log here, this could for example include:

a) The javascript console log
b) The network log 
c) ...
```
</details>
