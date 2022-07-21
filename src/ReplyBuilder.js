/**
 * @copyright 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

import moment from '@nextcloud/moment'
import negate from 'lodash/fp/negate'

import { html } from './util/text'

/**
 * @param {Text} original original
 * @param {object} from from
 * @param {number} date date
 * @param {boolean} replyOnTop put reply on top?
 * @return {Text}
 */
export const buildReplyBody = (original, from, date, replyOnTop = true) => {
	const startEnd = '<p></p><p></p>'
	const plainBody = '<br>&gt; ' + original.value.replace(/\n/g, '<br>&gt; ')
	const htmlBody = `<blockquote>${original.value}</blockquote>`
	const quoteStart = '<div class="quote">'
	const quoteEnd = '</div>'

	switch (original.format) {
	case 'plain':
		if (from) {
			const dateString = moment.unix(date).format('LLL')
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
			const dateString = moment.unix(date).format('LLL')
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

export const buildRecipients = (envelope, ownAddress) => {
	let recipientType = RecipientType.None
	const isOwnAddress = (a) => a.email === ownAddress.email
	const isNotOwnAddress = negate(isOwnAddress)

	// Locate why we received this envelope
	// Can be in 'to', 'cc' or unknown
	let replyingAddress = envelope.to.find(isOwnAddress)
	if (replyingAddress !== undefined) {
		recipientType = RecipientType.To
	} else {
		replyingAddress = envelope.cc.find(isOwnAddress)
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
		to = envelope.to.filter(isNotOwnAddress)
		to = to.concat(envelope.from.filter(isNotOwnAddress))

		// CC remains the same
		cc = envelope.cc
	} else if (recipientType === RecipientType.Cc) {
		// Send to the same people, plus the sender if not ourself
		to = envelope.to.concat(envelope.from.filter(isNotOwnAddress))

		// All CC values are being kept except the replying address
		cc = envelope.cc.filter(isNotOwnAddress)
	} else {
		// Send to the same recipient and the sender (if not ourself) -> answer all
		to = envelope.to
		to = to.concat(envelope.from.filter(isNotOwnAddress))

		// Keep CC values
		cc = envelope.cc
	}

	// edge case: pure self-sent email
	if (to.length === 0) {
		to = envelope.from
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
export const buildReplySubject = (original) => {
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

export const buildForwardSubject = (original) => {
	if (forwardPrepends.some((prepend) => original.toLowerCase().startsWith(`${prepend}:`))) {
		return original
	}

	return `Fwd: ${original}`
}
