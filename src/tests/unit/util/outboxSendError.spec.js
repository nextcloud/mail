/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { describe, expect, it, vi } from 'vitest'
import {
	STATUS_IMAP_SENT_MAILBOX_FAIL,
	STATUS_NO_SENT_MAILBOX,
	STATUS_RATELIMIT,
	STATUS_SMIME_ENCRYPT_FAIL,
	STATUS_SMIME_SIGN_NO_CERT_ID,
	STATUS_SMTP_ERROR,
	STATUS_SMTP_SEND_FAIL,
	STATUS_TOO_MANY_RECIPIENTS,
} from '../../../store/constants.js'
import { getSendErrorMessage } from '../../../util/outboxSendError.js'

vi.mock('@nextcloud/l10n', async (importOriginal) => ({
	...await importOriginal(),
	getLanguage: () => 'en',
}))

describe('getSendErrorMessage', () => {
	it('names a single rejected recipient', () => {
		const message = { status: STATUS_SMTP_ERROR, rejectedRecipients: ['a@example.test'] }

		expect(getSendErrorMessage(message)).toBe('The mail server rejected the recipient a@example.test. Check the address and try again.')
	})

	it('names all rejected recipients', () => {
		const message = { status: STATUS_SMTP_ERROR, rejectedRecipients: ['a@example.test', 'b@example.test'] }

		expect(getSendErrorMessage(message)).toBe('The mail server rejected the recipients a@example.test and b@example.test. Check the addresses and try again.')
	})

	it('does not escape special characters in recipient addresses', () => {
		const message = { status: STATUS_SMTP_ERROR, rejectedRecipients: ['o&r@example.test'] }

		expect(getSendErrorMessage(message)).toContain('o&r@example.test')
	})

	it('explains a missing sent mailbox', () => {
		expect(getSendErrorMessage({ status: STATUS_NO_SENT_MAILBOX })).toBe('No "sent" folder configured. Please pick one in the account settings.')
	})

	it('explains too many recipients', () => {
		expect(getSendErrorMessage({ status: STATUS_TOO_MANY_RECIPIENTS })).toBe('You are trying to send to many recipients in To and/or Cc. Consider using Bcc to hide recipient addresses.')
	})

	it('explains rate limiting', () => {
		expect(getSendErrorMessage({ status: STATUS_RATELIMIT })).toBe('You sent too many messages in a short time. Please try again later.')
	})

	it('explains a temporary SMTP failure', () => {
		expect(getSendErrorMessage({ status: STATUS_SMTP_SEND_FAIL })).toBe('The mail server could not accept the message right now. It will be sent again automatically later.')
	})

	it('explains a failed copy to the sent folder', () => {
		expect(getSendErrorMessage({ status: STATUS_IMAP_SENT_MAILBOX_FAIL })).toBe('Could not copy to "Sent" folder')
	})

	it.each([
		STATUS_SMIME_SIGN_NO_CERT_ID,
		STATUS_SMIME_ENCRYPT_FAIL,
	])('explains an S/MIME failure with status %s', (status) => {
		expect(getSendErrorMessage({ status })).toBe('Could not sign or encrypt the message with S/MIME. Check the S/MIME certificates.')
	})

	it('falls back to the generic message without a reason', () => {
		expect(getSendErrorMessage({ status: STATUS_SMTP_ERROR, rejectedRecipients: [] })).toBe('Could not send message')
	})

	it('falls back to the generic message for an object without a status', () => {
		expect(getSendErrorMessage({})).toBe('Could not send message')
	})

	it('falls back to the generic message without a message', () => {
		expect(getSendErrorMessage(undefined)).toBe('Could not send message')
	})
})
