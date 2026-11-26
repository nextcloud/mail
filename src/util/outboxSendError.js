/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getLanguage, n, t } from '@nextcloud/l10n'
import {
	STATUS_IMAP_SENT_MAILBOX_FAIL,
	STATUS_NO_SENT_MAILBOX,
	STATUS_RATELIMIT,
	STATUS_SMIME_ENCRYPT_CERT,
	STATUS_SMIME_ENCRYPT_FAIL,
	STATUS_SMIME_ENCRYPT_NO_CERT_ID,
	STATUS_SMIME_SIGN_CERT,
	STATUS_SMIME_SIGN_FAIL,
	STATUS_SMIME_SIGN_NO_CERT_ID,
	STATUS_SMTP_SEND_FAIL,
	STATUS_TOO_MANY_RECIPIENTS,
} from '../store/constants.js'

const SMIME_STATUSES = [
	STATUS_SMIME_SIGN_NO_CERT_ID,
	STATUS_SMIME_SIGN_CERT,
	STATUS_SMIME_SIGN_FAIL,
	STATUS_SMIME_ENCRYPT_NO_CERT_ID,
	STATUS_SMIME_ENCRYPT_CERT,
	STATUS_SMIME_ENCRYPT_FAIL,
]

/**
 * Get a user-facing reason why sending an outbox message failed
 *
 * @param {object|undefined} message the outbox message as returned by the failed send
 * @return {string}
 */
export function getSendErrorMessage(message) {
	const rejectedRecipients = message?.rejectedRecipients ?? []
	if (rejectedRecipients.length > 0) {
		return n('mail', 'The mail server rejected the recipient {recipients}. Check the address and try again.', 'The mail server rejected the recipients {recipients}. Check the addresses and try again.', rejectedRecipients.length, { recipients: new Intl.ListFormat(getLanguage(), { type: 'conjunction' }).format(rejectedRecipients) }, { escape: false })
	}

	switch (message?.status) {
		case STATUS_NO_SENT_MAILBOX:
			return t('mail', 'No "sent" folder configured. Please pick one in the account settings.')
		case STATUS_TOO_MANY_RECIPIENTS:
			return t('mail', 'You are trying to send to many recipients in To and/or Cc. Consider using Bcc to hide recipient addresses.')
		case STATUS_RATELIMIT:
			return t('mail', 'You sent too many messages in a short time. Please try again later.')
		case STATUS_SMTP_SEND_FAIL:
			return t('mail', 'The mail server could not accept the message right now. It will be sent again automatically later.')
		case STATUS_IMAP_SENT_MAILBOX_FAIL:
			return t('mail', 'Could not copy to "Sent" folder')
	}

	if (SMIME_STATUSES.includes(message?.status)) {
		return t('mail', 'Could not sign or encrypt the message with S/MIME. Check the S/MIME certificates.')
	}

	return t('mail', 'Could not send message')
}
