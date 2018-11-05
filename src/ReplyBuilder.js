/**
 * @copyright 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
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

import _ from 'lodash'
import moment from 'moment'
import {getLocale} from 'nextcloud-server/dist/l10n'

moment.locale(getLocale())

export const buildReplyBody = (original, from, date) => {
	const start = '\n\n'
	const body = '\n> ' + original.replace(/\n/g, '\n> ')

	if (from) {
		const dateString = moment.unix(date).format('LLL')
		return start + `"${from.label}" <${from.email}> – ${dateString}` + body
	} else {
		return start + body
	}
}

const RecipientType = Object.seal({
	None: 0,
	To: 1,
	Cc: 2
})

export const buildRecipients = (envelope, ownAddress) => {
	let recipientType = RecipientType.None
	const isOwnAddress = a => a.email === ownAddress.email
	const isNotOwnAddress = _.negate(isOwnAddress)

	// Locate why we received this envelope
	// Can be in 'to', 'cc' or unknown
	let replyingAddress = envelope.to.find(isOwnAddress)
	if (!_.isUndefined(replyingAddress)) {
		recipientType = RecipientType.To
	} else {
		replyingAddress = envelope.cc.find(isOwnAddress)
		if (!_.isUndefined(replyingAddress)) {
			recipientType = RecipientType.Cc
		} else {
			replyingAddress = ownAddress
		}
	}

	let to = []
	let cc = []
	if (recipientType === RecipientType.To) {
		// Send to everyone except yourself plus the original sender
		to = envelope.to.filter(isNotOwnAddress)
		to = to.concat(envelope.from)

		// CC remains the same
		cc = envelope.cc
	} else if (recipientType === RecipientType.Cc) {
		// Send to the same people plus the sender
		to = envelope.to.concat(envelope.from)

		// All CC values are being kept except the replying address
		cc = envelope.cc.filter(isNotOwnAddress)
	} else {
		// Send to the same recipient and the sender -> answer all
		to = envelope.to
		to = to.concat(envelope.from)

		// Keep CC values
		cc = envelope.cc
	}

	return {
		to,
		from: replyingAddress ? [replyingAddress] : [],
		cc,
	}
}

// TODO: https://en.wikipedia.org/wiki/List_of_email_subject_abbreviations#Abbreviations_in_other_languages
const replyPrepends = [
	're',
]

/*
 * Ref https://tools.ietf.org/html/rfc5322#section-3.6.5
 */
export const buildReplySubject = original => {
	if (replyPrepends.some(prepend => original.toLowerCase().startsWith(`${prepend}:`))) {
		return original
	}

	return `RE: ${original}`
}
