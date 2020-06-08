# Changelog
All notable changes to this project will be documented in this file.

## 1.4.0 - 2020-05-16
### Added
- Priority Inbox: the combined inbox is now classified into important messages, favorites and others to keep you organized
- Actions on multiple message (multiselect)
- Mark as junk
- Make as important
- Search by subject
- Confirmation when deleting account
### Changed
- Migrate from php-ml to rubix-ml
- Show only subscribed folders
- Open inbox when clicking on account item
- Consistency of "important" icon
- Make loading spinner not move all messages
- Change empty-content message on mail
- Incomplete initial sync now logs the current progress
### Fixed
- Delete all user's accounts when the user is being deleted
- Navigation from account settings to new message composer
- Navigating back from small screens
- User data cleanup after user deletion
- Message deletion from priority inbox
- Vertical space between sections in priority inbox
- Endless initial sync due to empty partial page

## 1.3.4 - 2020-04-27
### Added
- Incomplete initial sync now logs the current progress
### Fixed
- Sync'ing of provisioned accounts before their password is set
- UidValidityChangedException namespace error
- UID validity change logic
- Nextcloud groups integration

## 1.3.3 - 2020-04-21
### Added
- UI option to clear the cache of a single mailbox (debug mode only)
### Fixed
- Long recipient labels that are too big for the database column
### Changed
- Sync also the currently viewed mailbox in the background, not just the inboxes
- New and updated translations
- New screenshot

## 1.3.2 - 2020-04-16
### Fixed
- Initial synchronization on installations with high message UID numbers

## 1.3.1 - 2020-04-16
### Fixed
- Also sync mailboxes before sync'ing messages in cron
- Handling of partial initial sync
- Endless loading of paginated unified inbox

## 1.3.0 - 2020-04-15
### Added
- A database cache for messages, so many operations do not need a connection to IMAP. This can drastically improve the overall app performance, especially searching got very fast. The change is most noticeable on IMAP server with poor support for IMAP capabilities. The initial sync may take a few seconds or minutes, but afterwards the app should be snappy for everyone. The app now also syncs in the background (cron job), so when you open it it already has most of the recent changes in your IMAP account.
- Make it possible to view the source of the message
- Possibility to add message attachments as link shares
- Mark all as read in the unified inbox
- Improved account signature setting
- Hide folder collapse button if there is only one folder to hide
- Floating attachment button with popover
- Alignment and headings as formatting option in text edit
- Better handling of server errors and possible recovery logic for some error types
### Changed
- Move favorite toggle to menu, otherwise not distinguishable
- Update dependencies
- Update CKEditor to v18
### Fixed
- Handling of plain/html replies in plain/html
- Formatting of aliases in recipient dropdown
- Navigation from account settings to new message composer
- Missing padding-top of composer
- Missing In-Reply-To header for replies
- URLs of embedded images
- Design issues with iFrame and floating attachment button
- Fix more layout of message list until we move to component, ref #2827
- Change mark all as read icon
- Message iframe vertically to fit the container / available space
- Make mark all as read, usable for unified account
- Do not all-caps Cc and Bcc label Re and Fwd prefix
- Customizes formatting of paragraph elements in html-to-text's fromString function so that they get converted to a single newline only
- Enable translation of some strings
- Label recipients correctly in Composer.vue
- Jump to correct message after deleting current
- Distinct select in strict mode (mysql error)
- Missing translation
- Folder stats text in actions menu
- Remove statistics for favorites
- Missing translated default string for unnamed
- Change forward message sent feedback

## 1.1.4 - 2020-03-23
### Fixed
- Security: verify TLS host by default. This can be a *breaking change* for self-hosted servers. If you want to return to the old insecure behavior, set `app.mail.verify-tls-peer` to `true` in `config.php`.

## 1.1.3 - 2020-03-02
### Fixed
- Error in recipient selection

## 1.1.2 - 2020-01-30
### Changed
- New and updated translations
### Fixed
- Saving all attachments to Files
- Saving embedded messages to Files
- Octal value of KItinerary binary chmod from 744 to 0744

## 1.1.1 - 2020-01-27
### Fixed
- Missing file in release tarball

## 1.1.0 - 2020-01-27
### Added
- Nextcloud 19 support
### Changed
- New and updated translations
- Updated dependencies
### Fixed
- Clearing recipient input when focus is lost
- Broken mailbox stats on unified inbox
- Invisible embedded message parts
- Mailto protocol handler registration
- KItinerary executable permissions
- CC/BCC label
- Sending messages without a recipient
- No category for contacts autocompletion edge case
- Catch more error in error middleware
- Remove non-working mark all read action from unified inbox
- Remove non-working add subfolder action from unified inbox
- Missing inner exception in debug json error response
- Reply sender handling
- Provisioning for new users
- Creating a new mailbox

## 1.0.0 - 2020-01-17
### Added
- Itinerary extraction with KItinerary
- Sending to contacts groups
### Changed
- New and updated translations
- Updated dependencies
### Fixed
- Vanishing newlines in plaintext drafts
- Missing newlines on top of the reply body
- Unnecessary spaces in reply recipient line
- Automatic focus on reply body
- Display name change propagation for provisioned accounts

## 0.21.1 - 2020-01-07
### Changed
- New and updated translations
- Updated dependencies
### Fixed
- Move delete action down in folder actions menu
- Fix image hover effect on attachment
- Encoding of mailbox in URL
- Account selected when replying to message in unified inbox

## 0.21.0 - 2019-12-17
### Added
- Ability to move accounts up and down in the sidebar
### Changed
- New and updated translations
- Updated dependencies
### Fixed
- Default enable state for imported provisioned account
- Don't provision accounts when config is disabled
- Caching of Favicon library to a local app directory

## 0.20.3 - 2019-12-16
### Changed
- New and updated translations
### Fixed
- Attachment handling of non-integer MIME IDs like `2.2`
- Php warning of `each` usage in a Horde library

## 0.20.2 - 2019-12-13
### Changed
- New and updated translations
### Fixed
- JavaScript vulnerability in `serialize-javascript` dependency

## 0.20.1 - 2019-12-09
### Added
- occ command to diagnose an account (# of mailboxes, messages; IMAP capabilities)
### Changed
- New and updated translations
- Updated dependencies
### Fixed
- Provisioned account update password check when password is set to empty string

## 0.20.0 - 2019-12-04
### Added
- Admin settings UI to configure provisioned accounts (formerly known as "default account") -> provisioned accounts are now stored in the database
### Removed
- Default account configuration via config.php

## 0.19.1 - 2019-12-03
### Changed
- New and updated translations
- Updated dependencies

## 0.19.0 - 2019-11–25
### Added
- php7.4 support
### Changed
- Mail detail view now only has one back/menu button
- Composer attachment buttons moved into action menu
- Simpler wording for plain text/rich text switch
### Fixed
- Fix opening messages with attachment content-type and content-disposition
- Spacing in settings section
- No popover for addresses without a display name
- Horizontal scrolling issue on mobile
- Show recipient avatar instead of sender avatar in \sent mailboxes
- Focus *to* field automatically when writing a new message
- Virtual flagged inbox shown as child of the inbox
- Missing label of unified inbox
- .ics attachment mime detection
- .ics attachment import
- Unwanted flowed text formatting
### Removed
- php7.1 support

## 0.18.1 - 2019-11-04
### Fixed
- JavaScript transpilation for older browsers
- Groups expansion with special characters and spaces

## 0.18.0 - 2019-10-28
### Added
- Html editor
- Html signatures support
- Better layout for reading and writing messages
- Reply, reply-all and forward overhaul
- Message composer design improvements
- Account setup error feedback
- Account settings page design overhaul
### Fixed
- Removed reply-to email address from quoted text
- Logging error in drafts error handler
- Unnecessary line breaks in quoted reply text
- Umlauts encoding on folder (mailbox) names
- Missing navigation when adding an account
- Account form should be a HTML form
- Marking another message as read without navigation
- Deleting another message without navigation
- Fix missing redirect on account deletion
- Fix missing navigation when first account is added
- Envelope delete animation
- Broken auto redirect on external links of a HTML message
- Show external images
- Handling of internationalized FWD and AW prefixes
- Flagging drafts as \draft
### Changed
- Dependency updates
- New and updated translations
- JavaScript is now generated for @nextcloud/browserslist-config browsers

## 0.17.0 - 2019-09-02
### Added
- Warning when a message does not have a subject
- Cache mailbox special use as well for faster page loads
### Fixed
- SQL error at mailbox initialization
- SQL error at mailbox cache update
- Handling of avatars for invalid e-mail addresses
### Changed
- Dependency updates
- New and updated translations

## 0.16.0 - 2019-08-29
### Added
- Folder cache to decrease page load times significantly
### Changed
- Updated description
- New and updated translations

## 0.15.5 - 2019-08-28
### Fixed
- Wrong navigation to deleted message
- Wrong navigation to next/prev message of deleted message
- Non-translatable "forward" button

## 0.15.4 - 2019-08-26
### Fixed
- Vulnerable eslint-utils

## 0.15.3 - 2019-08-26
### Added
- Ability to print messages
### Changed
- New and updated translations
### Fixed
- JavaScript errors caused by a bug in the logging library

## 0.15.2 - 2019-08-06
### Added
- Better logging on the client-side
### Fixed
- Vulnerable `lodash` dependencies
### Changed
- New and updated translations

## 0.15.1 - 2019-05-14
### Fixed
- Cached Webpack chunks cause errors on upgrade
### Changed
- New and updated translations

## 0.15.0 - 2019-05-13
### Added
- Ability to mark folders as read
- Ability to create subfolders more easily
### Changed
- New and updated translations

## 0.14.0 - 2019-04-17
### Added
- Ability to create new folders (mailboxes)
- Nextcloud 17 compatibility
### Changed
- New and updated translations
### Fixed
- Update vulnerable js dependencies
- Navigation toggle on mobile

## 0.13.0 - 2019-04-12
### Added
- Signatures support
### Changed
- New and updated translations
### Fixed
- Mailvelope hint in settings menu

## 0.11.1 – 2019-04-10
### Added
- php7.3 support for Nextcloud 15

## 0.12.0 - 2018-04-08
### Added
- Nextcloud 16 support
- php7.3 support
- A new front-end (with lots of improvements)
- Many UX enhancements
- New and updated translations
### Fixed
- Various front-end issues
### Removed
- Nextcloud 15 support
- php7.0 support

## 0.11.0 – 2018-10-16
### Added
- Nextcloud 15 support
### Fixed
- Recipient autocompletion
- New message composer width
- Database incompatibilites
- Setup issue with port numbers
- Errors for messages without a sender
- Notifications for messages with no sender

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
