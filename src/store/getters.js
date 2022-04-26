/*
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

import { defaultTo, head, sortBy, prop } from 'ramda'

import { UNIFIED_ACCOUNT_ID } from './constants'
import { normalizedEnvelopeListId } from './normalization'

export const getters = {
	getPreference: (state) => (key, def) => {
		return defaultTo(def, state.preferences[key])
	},
	getAccount: (state) => (id) => {
		return state.accounts[id]
	},
	getAllAccountSettings: (state) => {
		return state.allAccountSettings
	},
	accounts: (state) => {
		return state.accountList.map((id) => state.accounts[id])
	},
	getMailbox: (state) => (id) => {
		return state.mailboxes[id]
	},
	getMailboxes: (state) => (accountId) => {
		return state.accounts[accountId].mailboxes.map((id) => state.mailboxes[id])
	},
	getSubMailboxes: (state, getters) => (id) => {
		const mailbox = getters.getMailbox(id)
		return mailbox.mailboxes.map((id) => state.mailboxes[id])
	},
	getUnifiedMailbox: (state) => (specialRole) => {
		return head(
			state.accounts[UNIFIED_ACCOUNT_ID].mailboxes
				.map((id) => state.mailboxes[id])
				.filter((mailbox) => mailbox.specialRole === specialRole)
		)
	},
	showMessageComposer: (state) => {
		return state.newMessage !== undefined
	},
	composerMessage: (state) => {
		return state.newMessage
	},
	composerMessageOptions: (state) => {
		return state.newMessage?.options
	},
	getEnvelope: (state) => (id) => {
		return state.envelopes[id]
	},
	getEnvelopes: (state, getters) => (mailboxId, query) => {
		const list = getters.getMailbox(mailboxId).envelopeLists[normalizedEnvelopeListId(query)] || []
		return list.map((msgId) => state.envelopes[msgId])
	},
	getMessage: (state) => (id) => {
		return state.messages[id]
	},
	getEnvelopeThread: (state) => (id) => {
		const thread = state.envelopes[id]?.thread ?? []
		const envelopes = thread.map(id => state.envelopes[id])
		return sortBy(prop('dateInt'), envelopes)
	},
	getEnvelopeTags: (state) => (id) => {
		const tags = state.envelopes[id]?.tags ?? []
		return tags.map((tagId) => state.tags[tagId])
	},
	getTag: (state) => (id) => {
		return state.tags[id]
	},
	getTags: (state) => {
		return state.tagList.map(tagId => state.tags[tagId])
	},
	isScheduledSendingDisabled: (state) => state.isScheduledSendingDisabled,
}
