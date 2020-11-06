/*
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
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

import { curry } from 'ramda'
import escapeRegExp from 'lodash/fp/escapeRegExp'
import orderBy from 'lodash/fp/orderBy'
import uniq from 'lodash/fp/uniq'
import Vue from 'vue'

import { sortMailboxes } from '../imap/MailboxSorter'
import { normalizedEnvelopeListId } from './normalization'
import { UNIFIED_ACCOUNT_ID } from './constants'

const addMailboxToState = curry((state, account, mailbox) => {
	mailbox.accountId = account.id
	mailbox.mailboxes = []
	Vue.set(mailbox, 'envelopeLists', {})

	// Add all mailboxes (including submailboxes to state, but only toplevel to account
	const nameWithoutPrefix = account.personalNamespace
		? mailbox.name.replace(new RegExp(escapeRegExp(account.personalNamespace)), '')
		: mailbox.name
	if (nameWithoutPrefix.includes(mailbox.delimiter)) {
		/**
		 * Sub-mailbox, e.g. 'Archive.2020' or 'INBOX.Archive.2020'
		 */
		mailbox.displayName = mailbox.name.substr(mailbox.name.lastIndexOf(mailbox.delimiter) + 1)
		mailbox.path = mailbox.name.substr(0, mailbox.name.lastIndexOf(mailbox.delimiter))
	} else if (account.personalNamespace && mailbox.name.startsWith(account.personalNamespace)) {
		/**
		 * Top-level mailbox, but with a personal namespace, e.g. 'INBOX.Sent'
		 */
		mailbox.displayName = nameWithoutPrefix
		mailbox.path = account.personalNamespace
	} else {
		/**
		 * Top-level mailbox, e.g. 'INBOX' or 'Draft'
		 */
		mailbox.displayName = nameWithoutPrefix
		mailbox.path = ''
	}

	Vue.set(state.mailboxes, mailbox.databaseId, mailbox)
	const parent = Object.values(state.mailboxes).find(mb => mb.name === mailbox.path)
	if (mailbox.path === '' || !parent) {
		account.mailboxes.push(mailbox.databaseId)
	} else {
		parent.mailboxes.push(mailbox.databaseId)
	}
})

const sortAccounts = (accounts) => {
	accounts.sort((a1, a2) => a1.order - a2.order)
	return accounts
}

export default {
	savePreference(state, { key, value }) {
		Vue.set(state.preferences, key, value)
	},
	addAccount(state, account) {
		account.collapsed = true
		Vue.set(state.accounts, account.id, account)
		Vue.set(
			state,
			'accountList',
			sortAccounts(state.accountList.concat([account.id]).map((id) => state.accounts[id])).map((a) => a.id)
		)

		// Save the mailboxes to the store, but only keep IDs in the account's mailboxes list
		const mailboxes = sortMailboxes(account.mailboxes || [])
		Vue.set(account, 'mailboxes', [])
		mailboxes.map(addMailboxToState(state, account))
	},
	editAccount(state, account) {
		Vue.set(state.accounts, account.id, Object.assign({}, state.accounts[account.id], account))
	},
	patchAccount(state, { account, data }) {
		Vue.set(state.accounts, account.id, Object.assign({}, state.accounts[account.id], data))
	},
	saveAccountsOrder(state, { account, order }) {
		Vue.set(account, 'order', order)
		Vue.set(
			state,
			'accountList',
			sortAccounts(state.accountList.map((id) => state.accounts[id])).map((a) => a.id)
		)
	},
	toggleAccountCollapsed(state, accountId) {
		state.accounts[accountId].collapsed = !state.accounts[accountId].collapsed
	},
	expandAccount(state, accountId) {
		state.accounts[accountId].collapsed = false
	},
	addMailbox(state, { account, mailbox }) {
		addMailboxToState(state, account, mailbox)
	},
	updateMailbox(state, { mailbox }) {
		Vue.set(state.mailboxes, mailbox.databaseId, mailbox)
	},
	removeMailbox(state, { id }) {
		const mailbox = state.mailboxes[id]
		if (mailbox === undefined) {
			throw new Error(`Mailbox ${id} does not exist`)
		}
		const account = state.accounts[mailbox.accountId]
		if (account === undefined) {
			throw new Error(`Account ${mailbox.accountId} of mailbox ${id} is unknown`)
		}
		Vue.delete(state.mailboxes, id)

		// Travers through the account and the full mailbox tree to find any dangling pointers
		const removeRec = (parent) => {
			parent.mailboxes = parent.mailboxes.filter((mbId) => mbId !== id)
			parent.mailboxes.map(mbid => removeRec(state.mailboxes[mbid]))
		}
		removeRec(account)
	},
	addEnvelope(state, { query, envelope }) {
		const mailbox = state.mailboxes[envelope.mailboxId]
		Vue.set(state.envelopes, envelope.databaseId, Object.assign({}, state.envelopes[envelope.databaseId] || {}, envelope))
		Vue.set(envelope, 'accountId', mailbox.accountId)
		const listId = normalizedEnvelopeListId(query)
		const existing = mailbox.envelopeLists[listId] || []
		const idToDateInt = (id) => state.envelopes[id].dateInt
		const orderByDateInt = orderBy(idToDateInt, 'desc')
		Vue.set(mailbox.envelopeLists, listId, uniq(orderByDateInt(existing.concat([envelope.databaseId]))))

		const unifiedAccount = state.accounts[UNIFIED_ACCOUNT_ID]
		unifiedAccount.mailboxes
			.map((mbId) => state.mailboxes[mbId])
			.filter((mb) => mb.specialRole && mb.specialRole === mailbox.specialRole)
			.forEach((mailbox) => {
				const existing = mailbox.envelopeLists[listId] || []
				Vue.set(
					mailbox.envelopeLists,
					listId,
					uniq(orderByDateInt(existing.concat([envelope.databaseId])))
				)
			})
	},
	updateEnvelope(state, { envelope }) {
		const existing = state.envelopes[envelope.uid]
		if (!existing) {
			return
		}
		Vue.set(existing, 'flags', envelope.flags)
	},
	flagEnvelope(state, { envelope, flag, value }) {
		envelope.flags[flag] = value
	},
	removeEnvelope(state, { id }) {
		const envelope = state.envelopes[id]
		if (!envelope) {
			console.warn('envelope ' + id + ' is unknown, can\'t remove it')
			return
		}
		const mailbox = state.mailboxes[envelope.mailboxId]
		for (const listId in mailbox.envelopeLists) {
			if (!Object.hasOwnProperty.call(mailbox.envelopeLists, listId)) {
				continue
			}
			const list = mailbox.envelopeLists[listId]
			const idx = list.indexOf(id)
			if (idx < 0) {
				continue
			}
			console.debug('envelope ' + id + ' removed from mailbox list ' + listId)
			list.splice(idx, 1)
		}

		state.accounts[UNIFIED_ACCOUNT_ID].mailboxes
			.map((mailboxId) => state.mailboxes[mailboxId])
			.filter((mb) => mb.specialRole && mb.specialRole === mailbox.specialRole)
			.forEach((mailbox) => {
				for (const listId in mailbox.envelopeLists) {
					if (!Object.hasOwnProperty.call(mailbox.envelopeLists, listId)) {
						continue
					}
					const list = mailbox.envelopeLists[listId]
					const idx = list.indexOf(id)
					if (idx < 0) {
						console.warn(
							'envelope does not exist in unified mailbox',
							mailbox.databaseId,
							id,
							listId,
							list
						)
						continue
					}
					console.debug('envelope removed from unified mailbox', mailbox.databaseId, id)
					list.splice(idx, 1)
				}
			})
	},
	addMessage(state, { message }) {
		Vue.set(state.messages, message.databaseId, message)
	},
	addEnvelopeThread(state, { id, thread }) {
		// Store the envelopes, merge into any existing object if one exists
		thread.map(e => {
			const mailbox = state.mailboxes[e.mailboxId]
			Vue.set(e, 'accountId', mailbox.accountId)
			Vue.set(state.envelopes, e.databaseId, Object.assign({}, state.envelopes[e.databaseId] || {}, e))
		})

		// Store the references
		Vue.set(state.envelopes[id], 'thread', thread.map(e => e.databaseId))
	},
	removeMessage(state, { id }) {
		Vue.delete(state.messages, id)
	},
	createAlias(state, { account, alias }) {
		account.aliases.push(alias)
	},
	deleteAlias(state, { account, alias }) {
		account.aliases.splice(account.aliases.indexOf(alias), 1)
	},

}
