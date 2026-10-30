/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * @param {{ email: string, type: 'individual'|'domain' }} a A trusted sender
 * @param {{ email: string, type: 'individual'|'domain' }} b Another trusted sender
 * @return {boolean}
 */
export function isSameTrustedSender(a, b) {
	return a.email === b.email && a.type === b.type
}

/**
 * @param {Array<{ email: string, type: 'individual'|'domain' }>} trustedSenders The list to search
 * @param {{ email: string, type: 'individual'|'domain' }} trustedSender The sender to look for
 * @return {boolean}
 */
export function isTrustedSenderListed(trustedSenders, trustedSender) {
	return trustedSenders.some((sender) => isSameTrustedSender(sender, trustedSender))
}

/**
 * @param {string} email An email address
 * @return {string|null} The domain after the last at sign, which a quoted local part can't fake, or null without one
 */
export function emailDomain(email) {
	const separator = email.lastIndexOf('@')
	return separator === -1 ? null : email.slice(separator + 1)
}

/**
 * Trust or stop trusting a sender in a list of trusted senders.
 *
 * @param {Array<{ email: string, type: 'individual'|'domain' }>} trustedSenders The list to change, which is not modified
 * @param {{ trustedSender: { email: string, type: 'individual'|'domain' }, trust: boolean }} change The sender and whether to trust it
 * @return {Array<{ email: string, type: 'individual'|'domain' }>} The changed list, or the given list if the change does not alter it
 */
export function applyTrustedSenderChange(trustedSenders, { trustedSender, trust }) {
	if (trust === isTrustedSenderListed(trustedSenders, trustedSender)) {
		return trustedSenders
	}

	return trust
		? [...trustedSenders, trustedSender]
		: trustedSenders.filter((sender) => !isSameTrustedSender(sender, trustedSender))
}
