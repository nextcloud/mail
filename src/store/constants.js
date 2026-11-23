/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { TOAST_UNDO_TIMEOUT } from '@nextcloud/dialogs'

export const UNIFIED_ACCOUNT_ID = 0
export const UNIFIED_INBOX_ID = 'unified'
export const PRIORITY_INBOX_ID = 'priority'
export const FOLLOW_UP_MAILBOX_ID = 'follow-up'
export const PAGE_SIZE = 20
export const UNDO_DELAY = TOAST_UNDO_TIMEOUT
export const EDITOR_MODE_HTML = 'richtext'
export const EDITOR_MODE_TEXT = 'plaintext'
export const STATUS_RAW = 0
// Mirrors the STATUS_* constants in lib/Db/LocalMessage.php. Keep both in sync.
export const STATUS_NO_SENT_MAILBOX = 1
export const STATUS_SMIME_SIGN_NO_CERT_ID = 2
export const STATUS_SMIME_SIGN_CERT = 3
export const STATUS_SMIME_SIGN_FAIL = 4
export const STATUS_SMIME_ENCRYPT_NO_CERT_ID = 5
export const STATUS_SMIME_ENCRYPT_CERT = 6
export const STATUS_SMIME_ENCRYPT_FAIL = 7
export const STATUS_TOO_MANY_RECIPIENTS = 8
export const STATUS_RATELIMIT = 9
export const STATUS_SMTP_SEND_FAIL = 10
export const STATUS_IMAP_SENT_MAILBOX_FAIL = 11
export const STATUS_SMTP_ERROR = 13

export const FOLLOW_UP_TAG_LABEL = '$follow_up'
