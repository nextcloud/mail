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
import { normalizedEnvelopeListId } from '../util/normalization.js'
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

const addMailboxToState = curry((account, mailbox) => {
	mailbox.accountId = account.id
	mailbox.mailboxes = []
	Vue.set(mailbox, 'envelopeLists', {})

	transformMailboxName(account, mailbox)

	Vue.set(this.mailboxes, mailbox.databaseId, mailbox)
	const parent = Object.values(this.mailboxes)
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
 * @param {object} envelope envelope with tag objects
 */
const normalizeTags = (envelope) => {
	if (Array.isArray(envelope.tags)) {
		// Tags have been normalized already
		return
	}

	const tags = Object
		.entries(envelope.tags ?? {})
		.map(([imapLabel, tag]) => {
			if (!this.tags[tag.id]) {
				Vue.set(this.tags, tag.id, tag)
			}
			if (!this.tagList.includes(tag.id)) {
				this.tagList.push(tag.id)
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
 * @param {Array} existing list of envelope ids for a message list
 * @param {object} envelope envelope with tag objects
 * @return {Array} list of envelope ids
 */
const appendOrReplaceEnvelopeId = (existing, envelope) => {
	const index = existing.findIndex((id) => this.envelopes[id].threadRootId === envelope.threadRootId)
	if (index === -1) {
		existing.push(envelope.databaseId)
	} else {
		existing[index] = envelope.databaseId
	}
	return existing
}

export default {
	savePreferenceMutation({ key, value }) {
		Vue.set(this.preferences, key, value)
	},
	setSessionExpiredMutation() {
		Vue.set(this.isExpiredSession, true)
	},
	addAccountMutation(account) {
		account.collapsed = account.collapsed ?? true
		Vue.set(this.accounts, account.id, account)
		Vue.set(
			this.accountList,
			sortAccounts(this.accountList.concat([account.id]).map((id) => this.accounts[id])).map((a) => a.id),
		)

		// Save the mailboxes to the store, but only keep IDs in the account's mailboxes list
		const mailboxes = sortMailboxes(account.mailboxes || [], account)
		Vue.set(account, 'mailboxes', [])
		Vue.set(account, 'aliases', account.aliases ?? [])
		mailboxes.map(addMailboxToState(account))
	},
	editAccountMutation(account) {
		Vue.set(this.accounts, account.id, Object.assign({}, this.accounts[account.id], account))
	},
	patchAccountMutation({ account, data }) {
		Vue.set(this.accounts, account.id, Object.assign({}, this.accounts[account.id], data))
	},
	saveAccountsOrderMutation({ account, order }) {
		Vue.set(account, 'order', order)
		Vue.set(
			this.accountsList,
			sortAccounts(this.accountList.map((id) => this.accounts[id])).map((a) => a.id),
		)
	},
	toggleAccountCollapsedMutation(accountId) {
		this.accounts[accountId].collapsed = !this.accounts[accountId].collapsed
	},
	expandAccountMutation(accountId) {
		this.accounts[accountId].collapsed = false
	},
	setAccountSettingMutation({ accountId, key, value }) {
		const accountSettings = this.allAccountSettings.find(settings => settings.accountId === accountId)
		if (accountSettings) {
			accountSettings[key] = value
		} else {
			const newAccountSettings = { accountId }
			newAccountSettings[key] = value
			this.allAccountSettings.push(newAccountSettings)
		}
	},
	addMailboxMutation({ account, mailbox }) {
		addMailboxToState(account, mailbox)
	},
	updateMailboxMutation({ mailbox }) {
		const account = this.accounts[mailbox.accountId]
		transformMailboxName(account, mailbox)
		Vue.set(this.mailboxes, mailbox.databaseId, mailbox)
	},
	removeMailboxMutation({ id }) {
		const mailbox = this.mailboxes[id]
		if (mailbox === undefined) {
			throw new Error(`Mailbox ${id} does not exist`)
		}
		const account = this.accounts[mailbox.accountId]
		if (account === undefined) {
			throw new Error(`Account ${mailbox.accountId} of mailbox ${id} is unknown`)
		}
		Vue.delete(this.mailboxes, id)

		// Travers through the account and the full mailbox tree to find any dangling pointers
		const removeRec = (parent) => {
			parent.mailboxes = parent.mailboxes.filter((mbId) => mbId !== id)
			parent.mailboxes.map(mbid => removeRec(this.mailboxes[mbid]))
		}
		removeRec(account)
	},
	/**
	 * Start a new composer session and open the modal.
	 *
	 * @param {object} payload Data for the new message
	 * @param payload.type
	 * @param payload.data
	 * @param payload.forwardedMessages
	 * @param payload.originalSendAt
	 * @param payload.smartReply
	 */
	startComposerSessionMutation({ type, data, forwardedMessages, originalSendAt, smartReply }) {
		this.composerSessionId = this.nextComposerSessionId
		this.nextComposerSessionId++
		this.newMessage = {
			type,
			data,
			options: {
				forwardedMessages,
				originalSendAt,
				smartReply,
			},
			indicatorDisabled: false,
		}
		this.composerMessageIsSaved = false
		this.showMessageComposer = true
	},
	/**
	 * Stop current composer session and close the modal.
	 * This discards all data from the current message.
	 *
	 */
	stopComposerSession() {
		this.composerSessionId = undefined
		this.newMessage = undefined
		this.showMessageComposer = false
	},
	/**
	 * Show composer modal if there is an ongoing session.
	 *
	 */
	showMessageComposer() {
		if (this.composerSessionId) {
			this.showMessageComposer = true
		}
	},
	/**
	 * Hide composer modal without ending the current session.
	 *
	hideMessageComposer() {
		this.showMessageComposer = false
	},
	setComposerMessageSavedMutation(saved) {
		this.composerMessageIsSaved = saved
	},
	patchComposerDataMutation(data) {
		this.newMessage.data = {
			...this.newMessage.data,
			...data,
		}
	},
	setComposerIndicatorDisabledMutation(disabled) {
		this.newMessage.indicatorDisabled = disabled
	},
	convertComposerMessageToOutboxMutation({ message }) {
		if (!this.newMessage) {
			// If the message is dispatched in the background there is no newMessage data in state
			return
		}
		Vue.set(this.newMessage, 'type', 'outbox')
		Vue.set(this.newMessage.data, 'id', message.id)
	},
	addEnvelopesMutation({ query, envelopes, addToUnifiedMailboxes = true }) {
		if (envelopes.length === 0) {
			return
		}

		const idToDateInt = (id) => this.envelopes[id].dateInt

		const listId = normalizedEnvelopeListId(query)
		const orderByDateInt = orderBy(idToDateInt, this.preferences['sort-order'] === 'newest' ? 'desc' : 'asc')

		envelopes.forEach((envelope) => {
			const mailbox = this.mailboxes[envelope.mailboxId]
			const existing = mailbox.envelopeLists[listId] || []
			normalizeTagsMutation(envelope)
			Vue.set(this.envelopes, envelope.databaseId, Object.assign({}, this.envelopes[envelope.databaseId] || {}, envelope))
			Vue.set(envelope, 'accountId', mailbox.accountId)
			Vue.set(mailbox.envelopeLists, listId, uniq(orderByDateInt(appendOrReplaceEnvelopeIdMutation(existing, envelope))))
			if (!addToUnifiedMailboxes) {
				return
			}
			const unifiedAccount = this.accounts[UNIFIED_ACCOUNT_ID]
			unifiedAccount.mailboxes
				.map((mbId) => this.mailboxes[mbId])
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
	updateEnvelopeMutation({ envelope }) {
		const existing = this.envelopes[envelope.databaseId]
		if (!existing) {
			return
		}
		normalizeTagsMutation(envelope)
		Vue.set(existing, 'flags', envelope.flags)
		Vue.set(existing, 'tags', envelope.tags)
	},
	flagEnvelopeMutation({ envelope, flag, value }) {
		const mailbox = this.mailboxes[envelope.mailboxId]
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
	addTagMutation({ tag }) {
		Vue.set(this.tags, tag.id, tag)
		this.tagList.push(tag.id)
	},
	addInternalAddressMutation(address) {
		Vue.set(this.internalAddress, address.id, address)
	},
	removeInternalAddressMutation({ addressId }) {
		this.internalAddress = this.internalAddress.filter((address) => address.id !== addressId)
	},
	deleteTagMutation({ tagId }) {
		this.tagList = this.tagList.filter((id) => id !== tagId)
		Vue.delete(this.tags, tagId)
	},
	addEnvelopeTagMutation({ envelope, tagId }) {
		Vue.set(envelope, 'tags', uniq([...envelope.tags, tagId]))
	},
	updateTagMutation({ tag, displayName, color }) {
		tag.displayName = displayName
		tag.color = color
	},
	removeEnvelopeTagMutation({ envelope, tagId }) {
		Vue.set(envelope, 'tags', envelope.tags.filter((id) => id !== tagId))
	},
	removeEnvelopeMutation({ id }) {
		const envelope = this.envelopes[id]
		if (!envelope) {
			console.warn('envelope ' + id + ' is unknown, can\'t remove it')
			return
		}
		const mailbox = this.mailboxes[envelope.mailboxId]
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

		this.accounts[UNIFIED_ACCOUNT_ID].mailboxes
			.map((mailboxId) => this.mailboxes[mailboxId])
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
		for (const [key, env] of Object.entries(this.envelopes)) {
			if (!env.thread) {
				continue
			}

			const thread = env.thread.filter(threadId => threadId !== id)
			Vue.set(this.envelopes[key], 'thread', thread)
		}

		Vue.delete(this.envelopes, id)
	},
	removeEnvelopesMutation({ id }) {
		Vue.set(this.mailboxes[id], 'envelopeLists', [])
	},
	removeAllEnvelopes() {
		Object.keys(this.mailboxes).forEach(id => {
			Vue.set(this.mailboxes[id], 'envelopeLists', [])
	  })
	},
	removeEnvelopeFromFollowUpMailboxMutation({ id }) {
		const filteredLists = {}
		const mailbox = this.mailboxes[FOLLOW_UP_MAILBOX_ID]
		for (const listId of Object.keys(mailbox.envelopeLists)) {
			filteredLists[listId] = mailbox.envelopeLists[listId]
				.filter((idInList) => id !== idInList)
		}
		Vue.set(this.mailboxes[FOLLOW_UP_MAILBOX_ID], 'envelopeLists', filteredLists)
	},
	addMessageMutation({ message }) {
		Vue.set(this.messages, message.databaseId, message)
	},
	addMessageItinerariesMutation({ id, itineraries }) {
		const message = this.messages[id]
		if (!message) {
			return
		}
		Vue.set(message, 'itineraries', itineraries)
	},
	addMessageDkimMutation({ id, result }) {
		const message = this.messages[id]
		if (!message) {
			return
		}
		Vue.set(message, 'dkimValid', result.valid)
	},
	addEnvelopeThreadMutation({ id, thread }) {
		// Store the envelopes, merge into any existing object if one exists
		thread.forEach(e => {
			normalizeTagsMutation(e)
			const mailbox = this.mailboxes[e.mailboxId]
			Vue.set(e, 'accountId', mailbox.accountId)
			Vue.set(this.envelopes, e.databaseId, Object.assign({}, this.envelopes[e.databaseId] || {}, e))
		})

		// Store the references
		Vue.set(this.envelopes[id], 'thread', thread.map(e => e.databaseId))
	},
	removeMessageMutation({ id }) {
		Vue.delete(this.messages, id)
	},
	createAliasMutation({ account, alias }) {
		account.aliases.push(alias)
	},
	deleteAliasMutation({ account, aliasId }) {
		const index = account.aliases.findIndex(temp => aliasId === temp.id)
		if (index !== -1) {
			account.aliases.splice(index, 1)
		}
	},
	patchAliasMutation({ account, aliasId, data }) {
		const index = account.aliases.findIndex(temp => aliasId === temp.id)
		if (index !== -1) {
			account.aliases[index] = Object.assign({}, account.aliases[index], data)
		}
	},
	setMailboxUnreadCountMutation({ id, unread }) {
		Vue.set(this.mailboxes[id], 'unread', unread ?? 0)
	},
	setScheduledSendingDisabledMutation(value) {
		this.isScheduledSendingDisabled = value
	},
	setSnoozeDisabledMutation(value) {
		this.isSnoozeDisabled = value
	},
	setActiveSieveScriptMutation({ accountId, scriptData }) {
		Vue.set(this.sieveScript, accountId, scriptData)
	},
	setCurrentUserPrincipalMutation({ currentUserPrincipal }) {
		this.currentUserPrincipal = currentUserPrincipal
	},
	addCalendarMutation({ calendar }) {
		this.calendars = [...this.calendars, calendar]
	},
	setGoogleOauthUrlMutation(url) {
		this.googleOauthUrl = url
	},
	setMasterPasswordEnabledMutation(value) {
		this.masterPasswordEnabled = value
	},
	setMicrosoftOauthUrlMutation(url) {
		this.microsoftOauthUrl = url
	},
	setSmimeCertificatesMutation(certificates) {
		this.smimeCertificates = certificates
	},
	deleteSmimeCertificateMutation({ id }) {
		this.smimeCertificates = this.smimeCertificates.filter(cert => cert.id !== id)
	},
	addSmimeCertificateMutation({ certificate }) {
		this.smimeCertificates = [...this.smimeCertificates, certificate]
	},
	setOneLineLayoutMutation({ list }) {
		Vue.setMutation('list', list)
	},
	setHasFetchedInitialEnvelopesMutation(hasFetchedInitialEnvelopes) {
		this.hasFetchedInitialEnvelopes = hasFetchedInitialEnvelopes
	},
	setFollowUpFeatureAvailableMutation(followUpFeatureAvailable) {
		this.followUpFeatureAvailable = followUpFeatureAvailable
	},
	hasCurrentUserPrincipalAndCollectionsMutation(hasCurrentUserPrincipalAndCollections) {
		this.hasCurrentUserPrincipalAndCollections = hasCurrentUserPrincipalAndCollections
	},
	showSettingsForAccountMutation(accountId) {
		this.showAccountSettings = accountId
	},
}
