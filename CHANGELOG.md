# Changelog
All notable changes to this project will be documented in this file.

## 0.6.5 – unreleased
### Added
- Add helper view for keyboard shortcuts
  [#91](https://github.com/nextcloud/mail/pull/91) @Gomez 

### Changed
- TODO

### Fixed
- TODO

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
