/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { curry } from 'ramda'
import escapeRegExp from 'lodash/fp/escapeRegExp.js'
import orderBy from 'lodash/fp/orderBy.js'
import uniq from 'lodash/fp/uniq.js'
import Vue from 'vue'

import { sortMailboxes } from '../imap/MailboxSorter.js'
import { normalizedEnvelopeListId } from './normalization.js'
import { FOLLOW_UP_MAILBOX_ID, UNIFIED_ACCOUNT_ID } from './constants.js'

const transformMailboxName = (account, mailbox) => {
	// Add all mailboxes (including submailboxes to state, but only toplevel to account
	const nameWithoutPrefix = account.personalNamespace
		? mailbox.name.replace(new RegExp(escapeRegExp(account.personalNamespace)), '')
		: mailbox.name
	if (nameWithoutPrefix.includes(mailbox.delimiter)) {
		/**
		 * Sub-mailbox, e.g. 'Archive.2020' or 'INBOX.Archive.2020'
		 */
		mailbox.displayName = mailbox.name.substring(mailbox.name.lastIndexOf(mailbox.delimiter) + 1)
		mailbox.path = mailbox.name.substring(0, mailbox.name.lastIndexOf(mailbox.delimiter))
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
			sortAccounts(state.accountList.concat([account.id]).map((id) => state.accounts[id])).map((a) => a.id),
		)

		// Save the mailboxes to the store, but only keep IDs in the account's mailboxes list
		const mailboxes = sortMailboxes(account.mailboxes || [], account)
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
			sortAccounts(state.accountList.map((id) => state.accounts[id])).map((a) => a.id),
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
	/**
	 * Start a new composer session and open the modal.
	 *
	 * @param {object} state Vuex state
	 * @param {object} payload Data for the new message
	 * @param payload.type
	 * @param payload.data
	 * @param payload.forwardedMessages
	 * @param payload.originalSendAt
	 * @param payload.smartReply
	 */
	startComposerSession(state, { type, data, forwardedMessages, originalSendAt, smartReply }) {
		state.composerSessionId = state.nextComposerSessionId
		state.nextComposerSessionId++
		state.newMessage = {
			type,
			data,
			options: {
				forwardedMessages,
				originalSendAt,
				smartReply,
			},
			indicatorDisabled: false,
		}
		state.composerMessageIsSaved = false
		state.showMessageComposer = true
	},
	/**
	 * Stop current composer session and close the modal.
	 * This discards all data from the current message.
	 *
	 * @param {object} state Vuex state
	 */
	stopComposerSession(state) {
		state.composerSessionId = undefined
		state.newMessage = undefined
		state.showMessageComposer = false
	},
	/**
	 * Show composer modal if there is an ongoing session.
	 *
	 * @param {object} state Vuex state
	 */
	showMessageComposer(state) {
		if (state.composerSessionId) {
			state.showMessageComposer = true
		}
	},
	/**
	 * Hide composer modal without ending the current session.
	 *
	 * @param {object} state Vuex state
	 */
	hideMessageComposer(state) {
		state.showMessageComposer = false
	},
	setComposerMessageSaved(state, saved) {
		state.composerMessageIsSaved = saved
	},
	patchComposerData(state, data) {
		state.newMessage.data = {
			...state.newMessage.data,
			...data,
		}
	},
	setComposerIndicatorDisabled(state, disabled) {
		state.newMessage.indicatorDisabled = disabled
	},
	convertComposerMessageToOutbox(state, { message }) {
		if (!state.newMessage) {
			// If the message is dispatched in the background there is no newMessage data in state
			return
		}
		Vue.set(state.newMessage, 'type', 'outbox')
		Vue.set(state.newMessage.data, 'id', message.id)
	},
	addEnvelopes(state, { query, envelopes, addToUnifiedMailboxes = true }) {
		if (envelopes.length === 0) {
			return
		}

		const idToDateInt = (id) => state.envelopes[id].dateInt

		const listId = normalizedEnvelopeListId(query)
		const orderByDateInt = orderBy(idToDateInt, state.preferences['sort-order'] === 'newest' ? 'desc' : 'asc')

		envelopes.forEach((envelope) => {
			const mailbox = state.mailboxes[envelope.mailboxId]
			const existing = mailbox.envelopeLists[listId] || []
			normalizeTags(state, envelope)
			Vue.set(state.envelopes, envelope.databaseId, Object.assign({}, state.envelopes[envelope.databaseId] || {}, envelope))
			Vue.set(envelope, 'accountId', mailbox.accountId)
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
						uniq(orderByDateInt(existing.concat([envelope.databaseId]))),
					)
				})
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
	addInternalAddress(state, address) {
		Vue.set(state.internalAddress, address.id, address)
	},
	removeInternalAddress(state, { addressId }) {
		state.internalAddress = state.internalAddress.filter((address) => address.id !== addressId)
	},
	deleteTag(state, { tagId }) {
		state.tagList = state.tagList.filter((id) => id !== tagId)
		Vue.delete(state.tags, tagId)
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
							list,
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
	removeAllEnvelopes(state) {
		Object.keys(state.mailboxes).forEach(id => {
			Vue.set(state.mailboxes[id], 'envelopeLists', [])
		  })
	},
	removeEnvelopeFromFollowUpMailbox(state, { id }) {
		const filteredLists = {}
		const mailbox = state.mailboxes[FOLLOW_UP_MAILBOX_ID]
		for (const listId of Object.keys(mailbox.envelopeLists)) {
			filteredLists[listId] = mailbox.envelopeLists[listId]
				.filter((idInList) => id !== idInList)
		}
		Vue.set(state.mailboxes[FOLLOW_UP_MAILBOX_ID], 'envelopeLists', filteredLists)
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
	addMessageDkim(state, { id, result }) {
		const message = state.messages[id]
		if (!message) {
			return
		}
		Vue.set(message, 'dkimValid', result.valid)
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
	setSnoozeDisabled(state, value) {
		state.isSnoozeDisabled = value
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
	addAddressBook(state, { addressBook }) {
		state.addressBooks = [...state.addressBooks, addressBook]
	},
	setGoogleOauthUrl(state, url) {
		state.googleOauthUrl = url
	},
	setMasterPasswordEnabled(state, value) {
		state.masterPasswordEnabled = value
	},
	setMicrosoftOauthUrl(state, url) {
		state.microsoftOauthUrl = url
	},
	setSmimeCertificates(state, certificates) {
		state.smimeCertificates = certificates
	},
	deleteSmimeCertificate(state, { id }) {
		state.smimeCertificates = state.smimeCertificates.filter(cert => cert.id !== id)
	},
	addSmimeCertificate(state, { certificate }) {
		state.smimeCertificates = [...state.smimeCertificates, certificate]
	},
	setOneLineLayout(state, { list }) {
		Vue.set(state, 'list', list)
	},
	setHasFetchedInitialEnvelopes(state, hasFetchedInitialEnvelopes) {
		state.hasFetchedInitialEnvelopes = hasFetchedInitialEnvelopes
	},
	setFollowUpFeatureAvailable(state, followUpFeatureAvailable) {
		state.followUpFeatureAvailable = followUpFeatureAvailable
	},
}
