/*
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author 2021 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
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

const transformMailboxName = (account, mailbox) => {
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
}

const addMailboxToState = curry((state, account, mailbox) => {
	mailbox.accountId = account.id
	mailbox.mailboxes = []
	Vue.set(mailbox, 'envelopeLists', {})

	transformMailboxName(account, mailbox)

	Vue.set(state.mailboxes, mailbox.databaseId, mailbox)
	const parent = Object.values(state.mailboxes)
		.filter(mb => mb.accountId === account.id)
		.find(mb => mb.name === mailbox.path)
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

/**
 * Convert envelope tag objects to references and add new tags to global list.
 *
 * @param {object} state vuex state
 * @param {object} envelope envelope with tag objects
 */
const normalizeTags = (state, envelope) => {
	if (Array.isArray(envelope.tags)) {
		// Tags have been normalized already
		return
	}

	const tags = Object
		.entries(envelope.tags ?? {})
		.map(([imapLabel, tag]) => {
			if (!state.tags[tag.id]) {
				Vue.set(state.tags, tag.id, tag)
			}
			if (!state.tagList.includes(tag.id)) {
				state.tagList.push(tag.id)
			}
			return tag.id
		})

	Vue.set(envelope, 'tags', tags)
}

/**
 * Append or replace an envelope id for an existing message list
 *
 * If the given thread root id exist the message is replaced
 * otherwise appended
 *
 * @param {object} state vuex state
 * @param {Array} existing list of envelope ids for a message list
 * @param {object} envelope envelope with tag objects
 * @return {Array} list of envelope ids
 */
const appendOrReplaceEnvelopeId = (state, existing, envelope) => {
	const index = existing.findIndex((id) => state.envelopes[id].threadRootId === envelope.threadRootId)
	if (index === -1) {
		existing.push(envelope.databaseId)
	} else {
		existing[index] = envelope.databaseId
	}
	return existing
}

export default {
	savePreference(state, { key, value }) {
		Vue.set(state.preferences, key, value)
	},
	setSessionExpired(state) {
		Vue.set(state, 'isExpiredSession', true)
	},
	addAccount(state, account) {
		account.collapsed = account.collapsed ?? true
		Vue.set(state.accounts, account.id, account)
		Vue.set(
			state,
			'accountList',
			sortAccounts(state.accountList.concat([account.id]).map((id) => state.accounts[id])).map((a) => a.id)
		)

		// Save the mailboxes to the store, but only keep IDs in the account's mailboxes list
		const mailboxes = sortMailboxes(account.mailboxes || [])
		Vue.set(account, 'mailboxes', [])
		Vue.set(account, 'aliases', account.aliases ?? [])
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
	setAccountSetting(state, { accountId, key, value }) {
		const accountSettings = state.allAccountSettings.find(settings => settings.accountId === accountId)
		if (accountSettings) {
			accountSettings[key] = value
		} else {
			const newAccountSettings = { accountId }
			newAccountSettings[key] = value
			state.allAccountSettings.push(newAccountSettings)
		}
	},
	addMailbox(state, { account, mailbox }) {
		addMailboxToState(state, account, mailbox)
	},
	updateMailbox(state, { mailbox }) {
		const account = state.accounts[mailbox.accountId]
		transformMailboxName(account, mailbox)
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
	showMessageComposer(state, { type, data, forwardedMessages, originalSendAt }) {
		Vue.set(state, 'newMessage', {
			type,
			data,
			options: {
				forwardedMessages,
				originalSendAt,
			},
		})
	},
	convertComposerMessageToOutbox(state, { message }) {
		Vue.set(state.newMessage, 'type', 'outbox')
		Vue.set(state.newMessage.data, 'id', message.id)
	},
	hideMessageComposer(state) {
		Vue.delete(state, 'newMessage')
	},
	addEnvelope(state, { query, envelope, addToUnifiedMailboxes = true }) {
		normalizeTags(state, envelope)
		const mailbox = state.mailboxes[envelope.mailboxId]
		Vue.set(state.envelopes, envelope.databaseId, Object.assign({}, state.envelopes[envelope.databaseId] || {}, envelope))
		Vue.set(envelope, 'accountId', mailbox.accountId)

		const idToDateInt = (id) => state.envelopes[id].dateInt
		const orderByDateInt = orderBy(idToDateInt, 'desc')

		const listId = normalizedEnvelopeListId(query)
		const existing = mailbox.envelopeLists[listId] || []
		Vue.set(mailbox.envelopeLists, listId, uniq(orderByDateInt(appendOrReplaceEnvelopeId(state, existing, envelope))))

		if (!addToUnifiedMailboxes) {
			return
		}
		const unifiedAccount = state.accounts[UNIFIED_ACCOUNT_ID]
		unifiedAccount.mailboxes
			.map((mbId) => state.mailboxes[mbId])
			.filter((mb) => mb.specialRole && mb.specialRole === mailbox.specialRole)
			.forEach((mailbox) => {
				const existing = mailbox.envelopeLists[listId] || []
				Vue.set(
					mailbox.envelopeLists,
					listId,
					uniq(orderByDateInt(appendOrReplaceEnvelopeId(state, existing, envelope)))
				)
			})
	},
	updateEnvelope(state, { envelope }) {
		const existing = state.envelopes[envelope.databaseId]
		if (!existing) {
			return
		}
		normalizeTags(state, envelope)
		Vue.set(existing, 'flags', envelope.flags)
		Vue.set(existing, 'tags', envelope.tags)
	},
	flagEnvelope(state, { envelope, flag, value }) {
		const mailbox = state.mailboxes[envelope.mailboxId]
		if (mailbox && flag === 'seen') {
			const unread = mailbox.unread ?? 0
			if (envelope.flags[flag] && !value) {
				Vue.set(mailbox, 'unread', unread + 1)
			} else if (!envelope.flags[flag] && value) {
				Vue.set(mailbox, 'unread', Math.max(unread - 1, 0))
			}
		}
		Vue.set(envelope.flags, flag, value)
	},
	addTag(state, { tag }) {
		Vue.set(state.tags, tag.id, tag)
		state.tagList.push(tag.id)
	},
	addEnvelopeTag(state, { envelope, tagId }) {
		Vue.set(envelope, 'tags', uniq([...envelope.tags, tagId]))
	},
	updateTag(state, { tag, displayName, color }) {
		tag.displayName = displayName
		tag.color = color
	},
	removeEnvelopeTag(state, { envelope, tagId }) {
		Vue.set(envelope, 'tags', envelope.tags.filter((id) => id !== tagId))
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

		if (!envelope.seen && mailbox.unread) {
			Vue.set(mailbox, 'unread', mailbox.unread - 1)
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

		// Delete references from other threads
		for (const [key, env] of Object.entries(state.envelopes)) {
			if (!env.thread) {
				continue
			}

			const thread = env.thread.filter(threadId => threadId !== id)
			Vue.set(state.envelopes[key], 'thread', thread)
		}

		Vue.delete(state.envelopes, id)
	},
	removeEnvelopes(state, { id }) {
		Vue.set(state.mailboxes[id], 'envelopeLists', [])
	},
	addMessage(state, { message }) {
		Vue.set(state.messages, message.databaseId, message)
	},
	addMessageItineraries(state, { id, itineraries }) {
		const message = state.messages[id]
		if (!message) {
			return
		}
		Vue.set(message, 'itineraries', itineraries)
	},
	addEnvelopeThread(state, { id, thread }) {
		// Store the envelopes, merge into any existing object if one exists
		thread.forEach(e => {
			normalizeTags(state, e)
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
	deleteAlias(state, { account, aliasId }) {
		const index = account.aliases.findIndex(temp => aliasId === temp.id)
		if (index !== -1) {
			account.aliases.splice(index, 1)
		}
	},
	patchAlias(state, { account, aliasId, data }) {
		const index = account.aliases.findIndex(temp => aliasId === temp.id)
		if (index !== -1) {
			account.aliases[index] = Object.assign({}, account.aliases[index], data)
		}
	},
	setMailboxUnreadCount(state, { id, unread }) {
		Vue.set(state.mailboxes[id], 'unread', unread ?? 0)
	},
	setScheduledSendingDisabled(state, value) {
		state.isScheduledSendingDisabled = value
	},
	setActiveSieveScript(state, { accountId, scriptData }) {
		Vue.set(state.sieveScript, accountId, scriptData)
	},
	setCurrentUserPrincipal(state, { currentUserPrincipal }) {
		state.currentUserPrincipal = currentUserPrincipal
	},
	addCalendar(state, { calendar }) {
		state.calendars = [...state.calendars, calendar]
	},
	setGoogleOauthUrl(state, url) {
		state.googleOauthUrl = url
	},
}
