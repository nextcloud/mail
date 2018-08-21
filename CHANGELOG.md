# Changelog
All notable changes to this project will be documented in this file.

## 0.10.0 – 2018-08-21
### Added
- Account export command (`occ mail:account:export <UID>`)
- Popover menu for messages
- Ability to mark messages as unread in the UI
### Changed
- New and updated translations
### Fixed
- Non-existent variable access warning
- SMTP FQDN hostname when sending messages
- Text alignment on redirect page
- Message header on mobile devices
- Sending messages with local attachments
- Removed usage of deprecated server APIs

## 0.9.0 – 2018-08-09
### Fixed
- Nextcloud 14 compatibility
- Cache permissions of favicon library
### Changed
- Dropped Nextcloud 13 support
- Requires php7+

## 0.8.3 – 2018-07-24
### Added
- New and updated translations
- Performance improvements

## 0.8.2 – 2018-06-28
### Added
- Advanced search
- New and updated translations
### Fixed
- Preserve URI hash in sanitized HTML messages
- Pagination of incoming messages
- Autoconfig issues
- Security issues in third party JavaScript libraries
- Folder sorting
- Issue template URL in README

## 0.8.1 – 2018-05-14
### Added
- New and updated translations
### Fixed
- Installation on Nextcloud 13.x

## 0.8.0 – 2018-05-07
### Fixed
- Nextcloud version requirements
- Auto completion suggestions of other users
- Exception logging of handled errors
- Unread message counter update on message deletion
- Non-square avatars
- Clearing message view when last message of a folder is deleted

## 0.7.10 – 2018-02-19
### Added
- Warn when sending messages or replies to a norepy address
- Better caching for HTML messages
### Changed
- Compatible with Nextcloud 13 and 14
### Fixed
- Dovecot INBOX prefixes
- Deprecation warning on php7.2
- Dovecot sieve folder error
- Concurrency issues with saving drafts and sending a message
- Client-side errors caused to large message UIDs and session storage
- Syncing the favorites folder

## 0.7.9 – 2018-01-23
### Fixed
- Undefined variable warning in nextcloud.log
- Inconsistent spelling
- Saving attachments to Nextcloud files

## 0.7.8 – 2018-01-15
### Fixed
- Loading of text messages
- HTML to text conversion for replies
- Recipient name rendering in sent folder

## 0.7.7 – 2018-01-09
### Added
- New and updated translations
- Editable account settings

## 0.7.6 – 2017-12-12
### Added
- Opt-out for external avatars (Gravatar, favicon)
### Fixed
- Icon scraper warnings
- Undefined index bugs
- Draft message active state in message list

## 0.7.5 – 2017-11-27
### Added
- Avatar from Gravatar and favicons
- Sourcemap support to debug release erros on client-side
### Fixed
- Attachment download to Nextcloud files
- Setup autoconfig issues
- Reply to/cc field prefilling
- Removed development log statement
- Favorites folder appeares as subfolder of inbox
- Folder unread counter visibility
- Prefilled sender name in replies
- Two security issues
- Subfolder collapsing

## 0.7.4 – 2017-10-30
### Added
- IMAP/SMTP logging enabled by default if debug mode is turned on
- Stricter CSRF checks
### Fixed
- Button loading state when saving all attachments
- Icon for 'Add from files' button
- Handling of to/bb/bcc values
- Favorites inbox separator
- Loading view wording
- Dovecot special (hidden) folders
- HTML message layout

## 0.7.3 – 2017-09-12
### Fixed
- Notification favicon for incoming messages
- Background sync if only one account is configured
- Error propagation in case folders cannot be loaded

## 0.7.2 – 2017-09-06
### Added
- Earlier loading feedback
### Fixed
- Subject of replied messages
- Navigation to next message in unified inbox
- Encoding of saved messages (new, draft)
- Account color indicator for new messages in unified inbox
- Selection of active message

## 0.7.1 – 2017-08-31
### Fixed
- Drafts encoding of special characters

## 0.7.0 – 2017-08-24
### Added
- Helper view for keyboard shortcuts
  [#91](https://github.com/nextcloud/mail/pull/91) @Gomez 
- Generic moving of messages (drag and drop)
- Account menu labels
- Support for a automatically generated default account
- Better loading/error/retry views
- Local attachment support
- Shortcut c to compose a new message

### Changed
- php5.6 to php 7.1 are supported
- No longer stack Aw, Wg, Fwd
- App store now lists the app in 'Social & Communication'
- Improved message synchronization

### Fixed
- Use IDBConnection instead of removed IDb
- Missing files in release package
- Loading messages with undisclosed recipients
- Problems with UTF8 encoding
- Account color dot on Safari
- Database column width for long passwords
- Error when adding a new account
- Undefined index warning
- Do not show 'noSelect' folders
- Selected account when composing a reply
- Remove noreferrer from HTML messages
- Some issues with drafts

## 0.6.4 – 2017-05-02
### Fixed
- NC12 incompatibility (usage of deprecated interface)

## 0.6.3 – 2017-04-25
### Added
- php7.1 support
- Nextcloud 12 support

## 0.6.2 – 2016-12-12
### Added
- Various autocompletion enhancements
- Support for CSP nonces
- Many small enhancements in the user interface
- Updated info.xml for the new app store
- Timestamps are updated automatically

### Changed
- Sent folder is now shown in the collapsed folder list
- PSR-4 naming of source files
- The mail notification is not closed after 5sec anymore
- Collected mail addresses are now sanitized and split into name and address
- Update to Marionette 3
- Removed client-side message list cache
- Updated documentation (developer, shortcuts)
- Messages that cannot be deleted are added back to the list

### Fixed
- FTP url filtering in HTML mails
- Noopener attribute for external links
- Downloading attachments does no longer abort other connections

## 0.6.1 – 2016-12-05
### Added
- Nextcloud 11 compatibility
  [#196](https://github.com/nextcloud/mail/pull/196) @MorrisJobke

## 0.6.0 – 2016-09-20
### Added
- Alias support
  [#1523](https://github.com/owncloud/mail/pull/1523) @tahaalibra
- New incoming messages are prefetched
  [#1631](https://github.com/owncloud/mail/pull/1631) @ChristophWurst
- Custom app folder support
  [#1627](https://github.com/owncloud/mail/pull/1627) @juliushaertl
- Improved search
  [#1609](https://github.com/owncloud/mail/pull/1609) @ChristophWurst
- Scroll to refresh
  [#1595](https://github.com/owncloud/mail/pull/1593) @ChristophWurst
- Shortcuts to star and mark messages as unread
  [#1590](https://github.com/owncloud/mail/pull/1590) @ChristophWurst
- Shortcuts to select previous/next messsage
  [#1557](https://github.com/owncloud/mail/pull/1557) @ChristophWurst

## Changed
- Minimum server is Nextcloud 10/ownCloud 9.1
  [#84](https://github.com/nextcloud/mail/pull/84) @ChristophWurst
- Use session storage instead of local storage for client-side cache
  [#1612](https://github.com/owncloud/mail/pull/1612) @ChristophWurst
- When deleting the current message, the next one is selected immediatelly
  [#1585](https://github.com/owncloud/mail/pull/1585) @ChristophWurst

## Fixed
- Client error while composing a new message
  [#1609](https://github.com/owncloud/mail/pull/1609) @ChristophWurst
- Delay app start until page has finished loading
  [#1634](https://github.com/owncloud/mail/pull/1634) @ChristophWurst
- Auto-redirection of HTML mail links
  [#1603](https://github.com/owncloud/mail/pull/1603) @ChristophWurst
- Update folder counters when reading/deleting messages
  [#1585](https://github.com/owncloud/mail/pull/1585)

## 0.5.2 – 2016-06-16

### Added
- Enhanced client-side paging algorithm for IMAP servers without SORT
  [#1486](https://github.com/owncloud/mail/pull/1486) @ChristophWurst
### Fixed
- Close popover on clicking somewhere else
  [#1521](https://github.com/owncloud/mail/pull/1521) @tahaalibra
- Fix email length in the database
  [#1518](https://github.com/owncloud/mail/pull/1518) @tahaalibra
- Fix setup error 'folder is null'
  [#1532](https://github.com/owncloud/mail/pull/1532) @Gomez

## 0.5.1 – 2016-05-30

### Fixed
- Sub-folders can not be selected
  [#1505](https://github.com/owncloud/mail/pull/1505) @ChristophWurst

## 0.5.0 – 2016-05-28

### Added
- Ability to import ics attachments into the calendar
  [#1473](https://github.com/owncloud/mail/pull/1473) @ChristophWurst

### Fixed
- Bring back menu toggle button for mobile
  [#1483](https://github.com/owncloud/mail/pull/1483) @ChristophWurst
- Narrow address collector column width to 255 for compatibility with MySql/InnoDB
  [#1484](https://github.com/owncloud/mail/pull/1484) @drfuture
- Don't send messages in flowed text format
  [#1482](https://github.com/owncloud/mail/pull/1482) @ChristophWurst
- Show first folder of an account incase of invalid folder id
  [#1471] (https://github.com/owncloud/mail/pull/1471) @tahaalibra

## 0.4.4 – 2016-05-06

### Added
- Collapse folders and show only important ones
  [#1445](https://github.com/owncloud/mail/pull/1445) @ChristophWurst

### Changed
- Show attachments as blocks instead of list
  [#1448](https://github.com/owncloud/mail/pull/1448) @ChristophWurst

### Fixed
- Fix button and sidebar layout
  [#1476](https://github.com/owncloud/mail/pull/1476) @skjnldsv
- Invalidate js cache if app version changes
  [#1457](https://github.com/owncloud/mail/pull/1457) @ChristophWurst
- Fixed newly created account not being shown after successful setup
  [#1459](https://github.com/owncloud/mail/pull/1459) @ChristophWurst
  [#1462](https://github.com/owncloud/mail/pull/1462) @ChristophWurst
- Replace old drafts correctly
  [#1464](https://github.com/owncloud/mail/pull/1464) @ChristophWurst
- JavaScript tests are now excluded from the app archive
  [#1466](https://github.com/owncloud/mail/pull/1466) @ChristophWurst

## 0.4.3 – 2016-04-23

### Added
- Load next messages automatically when reaching end of the list
  [#499](https://github.com/owncloud/mail/pull/1432) @ChristophWurst

### Changed
- Improved autoconfig
  [#1407](https://github.com/owncloud/mail/pull/1407) @Scheirle
- Better color generator for accounts
  [#1428](https://github.com/owncloud/mail/pull/1425) @skjnldsv

### Fixed
- Fix bug with address collector (Data too long for column)
  [#1421](https://github.com/owncloud/mail/pull/1433) @ChristophWurst

## 0.4.2 – 2016-04-13

### Added
- Fix show total email count for drafts folder
  [#1396](https://github.com/owncloud/mail/pull/1396) @tahaalibra

### Fixed
- Fix autocompletion (regression)
  [#1394](https://github.com/owncloud/mail/pull/1394) @ChristophWurst

## 0.4.1 – 2016-03-30

### Fixed
- Fix js error when adding attachments
  [#1378](https://github.com/owncloud/mail/pull/1378) @ChristophWurst

## 0.4.0 – 2016-03-17

### Added
- Add console command for account creation 
  [#1202](https://github.com/owncloud/mail/pull/1202) @ChristophWurst
- Address collector - Addresses of sent mails will be used for auto-completion
  [#1276](https://github.com/owncloud/mail/pull/1276) @ChristophWurst
- PHP 7 support
  [#1300](https://github.com/owncloud/mail/pull/1300) @ChristophWurst

### Deprecated
- Drop owncloud 7 support 
  [#1267](https://github.com/owncloud/mail/pull/1267) @ChristophWurst 

## 0.3.1

### Fixed
- Message list is not hidden when adding a secondary account
  [#1295](https://github.com/owncloud/mail/issues/1295) @Gomez
- Sub-sub folders not handled correctly
  [#618](https://github.com/owncloud/mail/issues/618) @ErikPel

## 0.3 - 2016-02-03
