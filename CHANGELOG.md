# Changelog
All notable changes to this project will be documented in this file.

## 2.2.3 - 2023-02-09
### Fixed
- Allow sending of messages with empty body content
- Check if  is empty before trying to make it an iterator
- ProvisioningMiddleware method return type
- Select correct account/alias when opening messages
- Unread counter position for outbox

## 2.2.2 – 2022-12-21
### Fixed
- Validate remote hosts
- Sending password for OAUTH accounts
- Storing password when we don't expect one
- Check for quoted-printable transfer encoding and decode messages before parsing html

## 2.2.1 – 2022-12-06
### Fixed
- Mailbox cache sync scope of current mailbox

## 2.2.0 - 2022-12-05
### Added
- Google OAuth support
- Skeleton for thread
- Make HTML body loading a single step experience
- Refactor HTML body element ID to class
- Give thread messages a minimum height
- Show thread subject while loading thread messages
- Local Draft Handling Backend
- Search box for outbox and PI
- Support for email field
### Fixed
- Advanced search does not scroll
- Dark theme loading envelops and thread colour
- Scrolling for long threads on narrow screens
- Replace emptycontent div with EmptyContent vue component
- Drop right border from navigation header
- Saving provisioning and anti-spam settings
- Don't decode content for preview twice
- Broken encoding for outgoing messages
- Add empty content for loading thread
- Improve the size of padding bottom
- Provisioning config not saving
- Sending PGP messages as HTML
- Copy to clipboard
- Envelope and message previews showing PGP ciphertext
- Double scrollbar in thread view
- Alias provisioning: Skip alias when identical to account email
- Improve the dropdown multiselect menu of default folders
- XOAUTH2 auth via SMTP
- Disable provisioned accounts if using passwordless authentication
- Dragover indicator being inherited to children

### Changed
- Remove loading icons when refreshing
- Migrate Vuex actions from promises to async-await
- Update app screenshot to match Nextcloud 25 design
- Don't collapse if threading has one message only
- Increase the clicable area on envelope thread

## 2.1.4 - 2022-11-30
### Fixed
- Show all mailboxes

## 2.1.3 – 2022-11-29
### Fixed
- Show only existing mailboxes, not all subscribed mailboxes
- Scrolling for long threads on narrow screens
- XOAUTH2 auth via SMTP

## 2.1.2 – 2022-11-22
### Fixed
- Don't collapse if threading has one message only
- Saving provisioning and anti-spam settings
- Undefined errorMessage in ThreadEnvelope
- Server error accessing message routes anonymously

## 2.1.1 – 2022-11-14
### Fixed
- Improve the size of padding bottom on threads
- Don't decode content for preview twice
- Broken encoding for outgoing messages
- Provisioning config not saving
- Envelope and message previews showing PGP ciphertext
- Sending PGP messages as HTML
- Copy to clipboard
- Double scrollbar in thread view

## 2.1.0 – 2022-11-03
### Added
- Hide important section when no important messages
- Basic messages filter and search
- Skeleton for loading state
- Implement archive functionality
- Add the option to disable the new account button
- Implement IAPIWidget
- Implement IIconWidget and IOptionWidget interfaces
### Fixed
- Signature above quote
- Background for default folders
- Loading-refresh-icon
- Disabling the add account button
- Keyboard button misalignment
- Scrolling on small screen
- Empty content when opening drafts
- Transfer encoding issue

## 2.0.3 – 2022-10-18
### Fixed
- Signature above quote

## 2.0.2 – 2022-10-17
### Fixed
- Modal glitches on Firefox when toggling autoresponder

## 2.0.1 – 2022-10-13
### Fixed
- Int value out of range when accepting calendar invitation
- Editor block quote handling

## 2.0.0 – 2022-10-11
### Added
- Image support for the message editor and signatures
- Automatic out-of-office replies (based on Sieve)
- Preview of images, audio, video and PDF with the Viewer app
- Add imip processing
- Users can clear their mailboxes
- Show warning for large signatures
- Add ckeditor plugin for mail

### Fixed
- Enable last day checkbox not being parsed correctly
- Date formatting for sieve autoresponder
- Missing line breaks when parsing message from sieve script
- Avatar being vanished for threads
- Show empty content when opening drafts
- Scrolling on small screen
- Focus on *To* when the composer opens

### Changed
- Adapt to Nextcloud 25 design
- Remember last used mailbox
- Make primary action icons bigger
- Improving the appearance of the modal window

### Removed
- Nextcloud 22-24 support

## 1.14.3 - 2022-11-14
### Fixed
- Improve the size of padding bottom on threads
- Improving the appearance of the modal window of composer
- Scrolling and bottom padding for threads
- Provisioning config not saving
- Sending PGP messages as HTML
- Important and favorite icon position
- Copy to clipboard
- Don't decode content for preview twice

## 1.14.2 - 2022-11-03
### Fixed
- Transfer encoding issue

## 1.14.1 - 2022-10-13
### Changed
- Updated Vue component library to v6

## 1.14.0 - 2022-10-10
### Added
- Hide system tags from UI
- Users can clear their mailboxes
- Improve the image attachment viewer
- Send oldest outbox message first
- 'No subject' on thread when there is none
- Migrate all icons to material design icons
- Support for XOAUTH2
- Preview message content in the thread view
- Migrate icons to material design icons
- Show message actions only for expanded messages in thread
- Show mailbox counter if sub mailbox has unread messages
- Show message preview in envelope list
- Envelope action links to download whole message


### Fixed
- Performance logger message for vanished messages
- Two-way binding for envelope unselect after delete
- Split auto config and account creation
- Account form styling
- Edit drafts
- Download all as zip
- Restore ability to write mails in safari
- Improve the way attachment look
- Change the opacity of leftside icons to fit the rest of the icons
- Infinite scrolling on mobile
- Image preview when the menu is clicked
- Make sender to be centered with the avatar and timestamps
- Work around breaking server change and QB expressions
- Better signature detection: always store the HTML version for the CKeditor body.
- Quoting forwarded messages
- l10n: Delete apostrophe
- l10n: Delete a shortcut
- Sending erroneous message repeatedly
- Remove color from flagged message star
- PI sync problems
- Sending outbox message of deleted user accounts
- Collecting addresses in the background
- Remove account color
- Shorten the relative time of envelopes
- General design improvements
- Redirect to setup page if no accounts are configured
- Show empty content when opening drafts

## 1.13.9 – 2022-10-07
### Changed
- Dependency updates

## 1.13.6 - 2022-06-29
### Fixed
- Editing draft
- Image attachment viewer

## 1.13.5 - 2022-06-22
- Make sender to be centered with the avatar and timestamp
- Restore ability to write mails in safari

## 1.13.4 - 2022-06-13
### Fixed
- Better signature detection
- Forwarding more than one attachment

## 1.13.3 - 2022-06-08
### Fixed
- Editing of outbox messages

## 1.13.2 - 2022-06-07
### Fixed
- Quoting forwarded messages
- Sending erroneous message repeatedly
- Database platform check with Nextcloud <22.2
- Missing Composer.vue::initBody call after mount

## 1.13.1 – 2022-06-07
### Fixed
- Upgrade error on Nextcloud <22.2
- Missing reply flagging and headers for edited outbox messages

## 1.13.0 – 2022-06-02
### Added
- PHP8.1 support
- Many performance improvements
### Changed

## 1.12.3 – 2022-06-07
### Fixed
- Missing reply flagging and headers for edited outbox messages

- New material icons
- Dropped Nextcloud 21 support (EOL)
- Dropped PHP7.3 support (EOL)
### Fixed
- Leaking database cursor
- Memory leaks
- Causal read during outbox message/recipient insert
- OCI compatibility
- Forwarding attachments
- Creating duplicate tags
- Collecting recipient addresses in background
- Priority inbox synchronization

## 1.12.2 – 2022-06-02
### Fixed
- Modal width
- Attachment forwarding
- Reply/forward editor body
- Sending messages of deleted accounts
- OCI compatibility
- Collecting recipient addresses in background
- Priority inbox synchronization

## 1.12.1 - 2022-05-24
### Fixed
- Prevent causal read for outbox message and recipient inserts
- Hide subscribed / unsubscribed checkbox for "Favourites" folder
- Fix attachment loss during outbox message update
- Fix sending messages to groups
- Fix composer not to open when we select envelope
- Change the timeout value to fit the dialogs
- Fix text format when closing the composer modal
- Do not override existing aliases
- Stop message from sending while editing
- Fix html editor being always selected
- Fix flagging replied messages
- Fix modal size in different size of the screen
- Show account color indicator only if several accounts are present
- Consider passwordless signings when provisioning

## 1.12.0 - 2022-04-26
### Added
- Local Outbox
- Scheduled Sending
- Undo Sending
- Anti abuse detection
- Time in-/senstive background jobs
- Clipboard support, copy recipient to clipboard
- Loading indicator for autosuggestion dropdown
### Changed
- General UI improvements and design fixes
- Account setup error reporting
- Downgrade KItinerary log line to info
### Fixed
- TypeError spamming logs when user not logged in
- Sync error with empty UID list
- array_merge needs at least one argument
- Missing account aliases for new accounts

## 1.11.7 - 2022-02-21
### Added
- Loading indicator for recipient suggestions
### Fixed
- Move KItinerary warning message to info

## 1.11.6 - 2022-01-25
### Fixed
- Priority Inbox Sync
- New Messages not loaded
- Full links in plain text mails
- Bold font for selected envelope(s) removed
- BCC for email recipients query

## 1.11.5 – 2021-12-28
### Fixed
- Use Parameters instead of Named Parameters for chunked queries
- Mailbox showing at the wrong account
- Column for account name that is too narrow
- Mark as spam action
- Chunk the recipients query

## 1.11.4 – 2021-12-15
### Fixed
- Erroneous repair step during app store upgrade

## 1.11.3 – 2021-12-15
### Fixed
- Fix message_id repair step

## 1.11.2 – 2021-12-14
### Fixed
- Invalid message cache message_id and in_reply_to values

## 1.11.1 – 2021-12-09
### Fixed
- Upgrades with Nextcloud 20
- Initial sync runs into infinite loop
- Initial sync stops too early
- Save all attachments button
- Database column types for message queries
- Missing debug logs

## 1.11.0 - 2021-11-29
### Added
- Nextcloud 23 support
- Mark as important/unimportant for multiple messages
- Provision aliases from an LDAP attribute
- Anti spam reporting
- Renaming of tag labels
### Changed
- Thread design overhaul
- Show attachments inline
- Spacing between accounts in sidebar
- Width handling of some HTML messages
- Show thread subject only once if unchanged
- Improved auto config
- Removed unread counter from trash mailbox
### Fixed
- IFrame sizing after print
- Text quotes in plain text messages
- Non-translatable strings
- Missing unified inboxes colors

## 1.10.5 – 2021-09-28
### Fixed
- Wrong iframe height
- Missing tags for provisioned accounts
- Catch all errors of faulty accounts
- Itinerary timestamp handling
- Deletion of provisioned accounts
- Hide delete button for provisioned accounts

## 1.10.4 – 2021-09-15
### Fixed
- External image handling when URL schema is missing
- Reply settings loading spinner

## 1.10.3 – 2021-08-17
### Changed
- Remove link proxy for external links
### Fixed
- Reply in quote block
- Handling of invalid IMAP message IDs
- Catch Guzzle error in proxy
- Transaction handling after errors
- Faulty HTML tidying
- Unrecoverable bug during new message sync
- Missing background clean-up job

## 1.10.2 – 2021–07-29
### Fixed
- Junk and nonjunk flagging
- Calendar ics attachment import
- Missing HTML headers inside the HTML message iframe
- Missing slash in shared file URL
- Signature indentation

## 1.10.1 – 2021-07-12
### Changed
- Updated and new translations
### Fixed
- Upgrade error due to missing method in cached application code

## 1.10.0 – 2021-07-05
### Added
- Message tagging – stores tags both locally as well as on IMAP (if supported), interoperable with other email clients
- Threaded message list – messages of the same thread are now only shown once in the message list
- Option to show images of a sender just temporarily
- Multi account provisioning – admins can now provide not just one configuration, and the user email will determine what config to use
- Refresh button to sync the currently open mailbox
- Unread counter for mailboxes in the sidebar
- Option to put the reply of a message underneath the quoted text
- Signatures for aliases
- Resizable message list column
### Changed
- Automatic message importance classification is now optional and can be turned off
- Reload mailbox when it's already open and clicked again in the sidebar
- Warn when a message is sent to many people in cc/bcc (e.g. when using groups)
- Handling of text/calendar attachments
- Updated and new translations
- Dependency updates
### Fixed
- Handling of missing IMAP message IDs
- Handling of inline message forwards
- Drafts handling
- Oracle compatibility
- Stop background sync for disabled users

## 1.9.6 – 2021-06-30
### Changed
- Reload mailbox when current mailbox is clicked in sidebar
- Dependency updates
### Fixed
- Don't run background syncs for disabled users
- Sanitize CSS style sheets
- Missing routes
- Multibyte string truncation
- Oracle compatibility with boolean columns

## 1.9.5 – 2021-04-08
### Fixed
- Handling of some inline attachments
- Missing lock for the initial sync
- Missing check for group sharing settings
- Alias handling
- Message background styling
- Warning with too many literals in an SQL IN clause
- Envelope change handling

## 1.9.4 – 2021-03-25
### Fixed
- "Show images" icon in HTML mails using Nextcloud's dark theme
- Drafts synchronization
- Drafts auto save timer
- Timeout-based unlocking a locked mailboxes after sync errors

## 1.9.3 – 2021-03-12
### Fixed
- Upgrade error from v1.9.1

## 1.9.2 – 2021-03-11
### Fixed
- Handling of IMAP messages without a Message-Id header
- Missing reply action
- Scrolling of message source
- Message important/flagged toggle
- Forwarding of messages with inline images
- Browser lag after the initial inbox synchronization

## 1.9.1 - 2021-03-03
### Fixed
- Missing route for attachments zip response

## 1.9.0 - 2021-03-03
### Added
- Sieve filter editor
- Download all attachments as zip archive
- Trust all senders of a domain
- Option to remove trust of a trusted sender or domain via account settings
- Bottom reply mode
- SCRAM-SHA-1 authentication support
- occ command to delete a Mail account as admin
- Stores the important flag as $important on IMAP for interoperability and being able to reset an account without data loss
- Nextcloud 22 development support
- PHP8 support
### Changed
- Envelope/message action icons are now streamlined
- Avoid the iconv //IGNORE option if possible for better compatibility with Alpine Linux
- SMTP host setting now has a label
- Attachment download and attachment zip now works without a new tab that opens briefly
- Loading message threads is roughly 2x faster thanks to an optimized query
- Updated and new translations
- Dependency updates
### Fixed
- Threading algorithm running into endless recursion and therefore causing a memory exhaustion
- Dependency conflicts with server and other apps
- App navigation toggle issues
- Move modal not showing
- Messages occasionally not vanishing from the database cache
- Ignore mailboxes that are not accessible (e.g. shared ones without sufficient permissions)
- Plural string of envelope actions
- Signature settings editor not showing editing controls
- Rendering the account in the sidebar despite authentication problems
- Handles some cases of invalid encoding better
- Wrong label for SMTP user in account settings
- Regression with missing name of local attachments
- Ensure an account exists before it's updated
- Empty mail server settings shown for provisioned accounts

## 1.8.3 - 2021-02-19
### Fixed
- Compatibility issues with PHP8
- Update account only if it exists

## 1.4.3 - 2021-02-19
### Fixed
- Update account only if it exists

## 1.8.2 – 2021-02-18
### Fixed
- Missing label for SMTP host setting
- Priority inbox filter expression
- Missing name of local attachments
- HTML signature editor hidden controls
- Navigation toggle issues
- Font issues with HTML emails and dark theme
- New message button not working under some conditions
- Updating accounts

## 1.8.1 – 2021-02-03
### Changed
- Updated translations
### Fixed
- Filter out invalid UTF-8 in subjects
- Regression that picks wrong attachment
- Rendering accounts in the navigation even when authentication fails

## 1.8.0 – 2021-01-20
### Added
- Drag and drop
- Remember trusted senders
- Message delivery notification
- Unread mail dashboard widget
- Forward original attachments
- Possibility to add multiple files as attachments
### Changed
- Account settings moved to modal
- Hide "All inboxes" if only one account is set up
- Improve the ckeditor list plugin
- Automatically sync mailbox on opening
### Fixed
- Always show Mail search results first
- Improve reply button icon styling
- Saving signature problems
- Layout issue for header
- Deleted drafts
- The route for "Register as application for mail links"
- Make links highlighted in the ckeditor
- Show sender in avatar header in all screen sizes
- The Dark theme on small screen
- Improve defaults layout
- Use consistent input vector size for the importance classifier

## 1.7.2 – 2020-12-07
### Fixed
- Priority inbox classifier input vector size

## 1.6.3 – 2020-12-07
### Fixed
- Priority inbox classifier input vector size

## 1.5.5 – 2020-12-07
### Fixed
- Priority inbox classifier input vector size

## 1.4.2 – 2020-12-07
### Fixed
- Priority inbox classifier input vector size
- CKEditor crashes on Firefox

## 1.7.1 – 2020-12-02
### Fixed
- Saving signatures
- Deletion of previous draft
- Mailto route
- Php7.2 compatibility issues
- Missing route for draft
- Default editor mode
- CKEditor crashes on Firefox

## 1.6.2 – 2020-12-02
### Fixed
- Mail search results not shown on top
- Saving signatures
- Deletion of previous draft
- Mailto route
- Php7.2 compatibility issues
- Missing route for draft
- Default editor mode
- CKEditor crashes on Firefox

## 1.5.4 – 2020-12-02
### Fixed
- Mail search results not shown on top
- Saving signatures
- Deletion of previous draft
- Mailto route
- Missing route for draft
- Default editor mode
- CKEditor crashes on Firefox

## 1.7.0 – 2020-11-11
### Added
* Possibility to move mailboxes
* Improved HTML email rendering
* Ability to edit existing messages as new ones
### Changed
* Admin documentation now contains more troubleshooting instructions
* Show admin and developer documentation on app store
### Fixed
* Null error for messages with no sender information
* Overlapping message with same timestamp
* Wrong php7.2 support claimed in info.xml of v1.6.0
* Unexpected borders on recipient pickers
* Styling of automatic/manual account settings tabs

## 1.6.1 – 2020-11-11
### Fixed
* Null error for messages with no sender information
* Wrong php7.2 support claimed in info.xml of v1.6.0
* Unexpected borders on recipient pickers
* Styling of automatic/manual account settings tabs

## 1.5.3 – 2020-11-11
### Fixed
* Null error for messages with no sender information
* Unexpected borders on recipient pickers
* Styling of automatic/manual account settings tabs

## 1.6.0 – 2020-11-04
### Added
- Moving mails to other mailboxes of the same account (also usable on mobile)
- New account preferences to pick the mailbox for draft, sent and trash mails
- Important/favorite/read indicators inside the threaded view
- Enhanced multiselect using the Shift key to select ranges
### Changed
- Minimum php version increased to 7.3 due to 7.2 EOL
- Reply button is now a link – user can write the reply in a new tab
- Internal code cleanup
- Dependency updates
### Fixed
- Saving all attachments of an email to Nextcloud Files
- Quoting the forwarded email
- Seen/unseen handling in multiselect
- Font weight in thread view
- Font color of "Add account" button in app settings
- Browser error when navigating away from a draft that hasn't loaded yet

## 1.5.2 – 2020-11-04
### Fixed
- Saving all attachments of an email to Nextcloud Files
- Quoting the forwarded email
- Seen/unseen handling in multiselect

## 1.5.1 - 2020-10-27
- Fix displaying of messages with empty from field
- Fix translation of delete account dialogue
- Fix the overlapping between 2 threads
- Fix saving aliases
- Improve the message-frame height to use the remaining space
- Change account-settings button colour to fit the theme
- Make sure the email is valid UTF-8
- Make the deserialized thread root ID nullable

## 1.5.0 - 2020-10-03
### Added
- Threading: related message are shown as conversations
- Mailbox management: add, move, rename and delete folders and subfolders
- Unified search integration: find emails everywhere, you don't even have to open Mail
- Dashboard integration: see all important email at a glance when you log into your cloud
- Display account quota: see how much storage your account is using
- Multilevel mailbox structure: no more limits on how deeply you next your folders
- Aliases: manage your identities
- Collapse quoted text and signatures: hide the parts of a message that are less important, but make it possible to expand if needed
- Create new folders when saving attachments
### Fixed
- Fix formatting when it is checked individually from the account default
- Catch throwable's during account sync
- Fix icon color in dark theme
- Fix inconsistend wording Spam vs Junk
- Add action menu for subfolders
- Add the missing references header for sent messages
- Improve design of the folder creation
- Show setup button if no accounts are set up
- Improve accessibility
- Make it possible to view messages of the same thread directly
- Improve signature design
- Do not navigate when clicking an envelope's menu
- Change keyboardshortcut into modal
- Allow toggeling of subscription status of folders
- Fix dashboard empty content
- Fix message source scroll bar
- Inject styles for using native fonts in html mails
- Fix unseen messages loading infinitely
- Collapse plain text signatures into a detail element
- Set file picker to copy mode
- Collapse quoted text in plain text messages
### Removed
- Nextcloud 18 support (due to hard dependency on new API)
- Nextcloud 19 support (due to hard dependency on new API)

## 1.4.1 - 2020-06-30
### Fixed
- Importance classifier debuggability
- Fix classification of senders with no email
- Add action menu to subfolders
- Add missing `References` header on replies
- Fix saving the serialized classified object on Nextcloud 18

## 1.3.6 - 2020-06-30
### Fixed
- User data cleanup after account deletion
- Navigating back on mobile
- Formatting toggle in message composer
- Missing `References` header for replies

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
