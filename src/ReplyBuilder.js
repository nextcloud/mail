/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import negate from 'lodash/fp/negate.js'
import { formatDateTimeFromUnix } from './util/formatDateTime.js'
import { html } from './util/text.js'

/**
 * @param {Text} original original
 * @param {object} from from
 * @param {number} date date
 * @param {boolean} replyOnTop put reply on top?
 * @return {Text}
 */
export function buildReplyBody(original, from, date, replyOnTop = true) {
	const startEnd = '<p></p><p></p>'
	const plainBody = '<br>&gt; ' + original.value.replace(/\n/g, '<br>&gt; ')
	const htmlBody = `<blockquote>${original.value}</blockquote>`
	const quoteStart = '<div class="quote">'
	const quoteEnd = '</div>'

	switch (original.format) {
		case 'plain':
			if (from) {
				const dateString = formatDateTimeFromUnix(date)
				return replyOnTop
					? html(`${startEnd}${quoteStart}"${from.label}" ${from.email} – ${dateString}` + plainBody + quoteEnd)
					: html(`${quoteStart}"${from.label}" ${from.email} – ${dateString}` + plainBody + quoteEnd + startEnd)
			} else {
				return replyOnTop
					? html(`${startEnd}${quoteStart}${plainBody}${quoteEnd}`)
					: html(`${quoteStart}${plainBody}${quoteEnd}${startEnd}`)
			}
		case 'html':
			if (from) {
				const dateString = formatDateTimeFromUnix(date)
				return replyOnTop
					? html(`${startEnd}${quoteStart}"${from.label}" ${from.email} – ${dateString}<br>${htmlBody}${quoteEnd}`)
					: html(`${quoteStart}"${from.label}" ${from.email} – ${dateString}<br>${htmlBody}${quoteEnd}${startEnd}`)
			} else {
				return replyOnTop
					? html(`${startEnd}${quoteStart}${htmlBody}${quoteEnd}`)
					: html(`${quoteStart}${htmlBody}${quoteEnd}${startEnd}`)
			}
	}

	throw new Error(`can't build a reply for the format ${original.format}`)
}

const RecipientType = Object.seal({
	None: 0,
	To: 1,
	Cc: 2,
})

export function isSameEmailAddress(left, right) {
	const normalizedLeft = left?.trim().toLowerCase()
	return !!normalizedLeft && normalizedLeft === right?.trim().toLowerCase()
}

export function selectReplyIdentity(envelope, accounts, preferSender = false) {
	const identities = accounts
		.filter((account) => !account.isUnified && account.connectionStatus !== false)
		.flatMap((account) => [
			{
				accountId: account.id,
				aliasId: null,
				email: account.emailAddress,
				label: account.name,
			},
			...(account.aliases ?? []).map((alias) => ({
				accountId: account.id,
				aliasId: alias.id,
				email: alias.alias,
				label: alias.name,
			})),
		])
	const identityGroups = [
		identities.filter((identity) => identity.accountId === envelope.accountId),
		identities.filter((identity) => identity.accountId !== envelope.accountId),
	]
	const addressLists = preferSender
		? [envelope.from, envelope.to, envelope.cc]
		: [envelope.to, envelope.cc, envelope.from]

	for (const addresses of addressLists) {
		for (const candidates of identityGroups) {
			for (const address of addresses ?? []) {
				const identity = candidates.find((candidate) => isSameEmailAddress(candidate.email, address.email))
				if (identity) {
					return identity
				}
			}
		}
	}
}

export function buildRecipients(envelope, ownAddress, replyTo, followUp = false) {
	let recipientType = RecipientType.None
	const isOwnAddress = (a) => isSameEmailAddress(a.email, ownAddress.email)
	const isNotOwnAddress = negate(isOwnAddress)
	const originalTo = envelope.to ?? []
	const originalCc = envelope.cc ?? []

	// The Reply-To header has higher precedence than the From header.
	// This reuses Horde's handling of the reply_to field directly.
	const from = !followUp && replyTo?.length > 0 ? replyTo : (envelope.from ?? [])
	const senders = followUp ? [] : from.filter(isNotOwnAddress)

	// Locate why we received this envelope
	// Can be in 'to', 'cc' or unknown
	let replyingAddress = originalTo.find(isOwnAddress)
	if (replyingAddress !== undefined) {
		recipientType = RecipientType.To
	} else {
		replyingAddress = originalCc.find(isOwnAddress)
		if (replyingAddress !== undefined) {
			recipientType = RecipientType.Cc
		} else {
			replyingAddress = ownAddress
		}
	}

	let to = []
	let cc = []
	if (recipientType === RecipientType.To) {
		// Send to everyone except yourself, plus the original sender if not ourself
		to = originalTo.filter(isNotOwnAddress)
		to = to.concat(senders)

		cc = originalCc.filter(isNotOwnAddress)
	} else if (recipientType === RecipientType.Cc) {
		// Send to the same people, plus the sender if not ourself
		to = originalTo.concat(senders)

		// All CC values are being kept except the replying address
		cc = originalCc.filter(isNotOwnAddress)
	} else {
		// Send to the same recipient and the sender (if not ourself) -> answer all
		to = originalTo
		to = to.concat(senders)

		cc = originalCc.filter(isNotOwnAddress)
	}

	// edge case: pure self-sent email
	if (to.length === 0 && cc.length === 0) {
		to = followUp ? (originalTo.length > 0 ? originalTo : originalCc) : from
	}

	return {
		to,
		from: replyingAddress ? [replyingAddress] : [],
		cc,
	}
}

const replyPrepends = [
	'antw',
	'atb',
	'aw',
	'bls',
	'odp',
	'r',
	're',
	'ref',
	'res',
	'rif',
	'sv',
	'vá',
	'vs',
	'ynt',
	'απ',
	'σχετ',
	'השב',
	'回复',
	'回覆',
]

/*
 * Ref https://tools.ietf.org/html/rfc5322#section-3.6.5
 */
export function buildReplySubject(original) {
	if (replyPrepends.some((prepend) => original.toLowerCase().startsWith(`${prepend}:`))) {
		return original
	}

	return `Re: ${original}`
}

// TODO: https://en.wikipedia.org/wiki/List_of_email_subject_abbreviations#Abbreviations_in_other_languages
const forwardPrepends = [
	'doorst',
	'enc',
	'fs',
	'fw',
	'fwd',
	'i',
	'i̇lt',
	'pd',
	'rv',
	'továbbítás',
	'tr',
	'trs',
	'vb',
	'vl',
	'vs',
	'wg',
	'yml',
	'ΠΡΘ',
	'הועבר',
	'إعادة توجيه',
	'رد',
	'轉寄',
	'转发',
]

export function buildForwardSubject(original) {
	if (forwardPrepends.some((prepend) => original.toLowerCase().startsWith(`${prepend}:`))) {
		return original
	}

	return `Fwd: ${original}`
}
