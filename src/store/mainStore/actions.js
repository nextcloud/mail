/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import flatMapDeep from 'lodash/fp/flatMapDeep.js'
import orderBy from 'lodash/fp/orderBy.js'
import {
	andThen,
	complement,
	curry,
	filter,
	flatten,
	gt,
	lt,
	identity,
	last,
	map,
	pipe,
	prop,
	propEq,
	slice,
	tap,
	where, defaultTo, head, sortBy,
} from 'ramda'

import { savePreference } from '../../service/PreferenceService.js'
import {
	create as createAccount,
	deleteAccount,
	fetch as fetchAccount,
	fetchAll as fetchAllAccounts,
	patch as patchAccount,
	update as updateAccount,
	updateSignature,
	updateSmimeCertificate as updateAccountSmimeCertificate,
} from '../../service/AccountService.js'
import {
	create as createMailbox,
	clearMailbox,
	deleteMailbox,
	fetchAll as fetchAllMailboxes,
	markMailboxRead,
	patchMailbox,
} from '../../service/MailboxService.js'
import {
	createEnvelopeTag,
	deleteMessage,
	fetchEnvelope,
	fetchEnvelopes,
	fetchMessage,
	fetchMessageDkim,
	fetchMessageItineraries,
	fetchThread,
	moveMessage,
	removeEnvelopeTag,
	setEnvelopeFlags,
	setEnvelopeTag,
	snoozeMessage,
	syncEnvelopes as syncEnvelopesExternal,
	unSnoozeMessage,
	updateEnvelopeTag,
	deleteTag,
} from '../../service/MessageService.js'
import { moveDraft, updateDraft } from '../../service/DraftService.js'
import * as AliasService from '../../service/AliasService.js'
import logger from '../../logger.js'
import { normalizedEnvelopeListId } from '../../util/normalization.js'
import { showNewMessagesNotification } from '../../service/NotificationService.js'
import { matchError } from '../../errors/match.js'
import SyncIncompleteError from '../../errors/SyncIncompleteError.js'
import MailboxLockedError from '../../errors/MailboxLockedError.js'
import { wait } from '../../util/wait.js'
import {
	getActiveScript,
	updateAccount as updateSieveAccount,
	updateActiveScript,
} from '../../service/SieveService.js'
import {
	FOLLOW_UP_TAG_LABEL,
	PAGE_SIZE,
	UNIFIED_INBOX_ID,
	FOLLOW_UP_MAILBOX_ID,
	UNIFIED_ACCOUNT_ID,
} from '../constants.js'
import * as ThreadService from '../../service/ThreadService.js'
import {
	getPrioritySearchQueries,
	priorityImportantQuery,
	priorityOtherQuery,
} from '../../util/priorityInbox.js'
import Axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { handleHttpAuthErrors } from '../../http/sessionExpiryHandler.js'
import { showError, showWarning } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import {
	buildForwardSubject,
	buildRecipients as buildReplyRecipients,
	buildReplySubject,
} from '../../ReplyBuilder.js'
import DOMPurify from 'dompurify'
import {
	getCurrentUserPrincipal,
	initializeClientForUserView,
	findAll,
} from '../../service/caldavService.js'
import * as SmimeCertificateService from '../../service/SmimeCertificateService.js'
import useOutboxStore from '../outboxStore.js'
import * as FollowUpService from '../../service/FollowUpService.js'
import { addInternalAddress, removeInternalAddress } from '../../service/InternalAddressService.js'
import { createTextBlock, fetchMyTextBlocks, fetchSharedTextBlocks, deleteTextBlock, updateTextBlock } from '../../service/TextBlockService.js'

import escapeRegExp from 'lodash/fp/escapeRegExp.js'
import uniq from 'lodash/fp/uniq.js'
import Vue from 'vue'

import { sortMailboxes } from '../../imap/MailboxSorter.js'
import { createQuickAction, deleteQuickAction, updateQuickAction } from '../../service/QuickActionsService.js'

const sliceToPage = slice(0, PAGE_SIZE)

const findIndividualMailboxes = curry((getMailboxes, specialRole) =>
	pipe(
		filter(complement(prop('isUnified'))),
		map(prop('id')),
		map(getMailboxes),
		flatten,
		filter(propEq(specialRole, 'specialRole')),
	),
)

const combineEnvelopeLists = (sortOrder) => {
	if (sortOrder === 'oldest') {
		return pipe(flatten, orderBy(prop('dateInt'), 'asc'))
	}

	return pipe(flatten, orderBy(prop('dateInt'), 'desc'))
}

const addMailboxToState = curry((mailboxes, account, mailbox) => {
	mailbox.accountId = account.id
	mailbox.mailboxes = []
	Vue.set(mailbox, 'envelopeLists', {})

	transformMailboxName(account, mailbox)

	Vue.set(mailboxes, mailbox.databaseId, mailbox)
	const parent = Object.values(mailboxes)
		.filter(mb => mb.accountId === account.id)
		.find(mb => mb.name === mailbox.path)
	if (mailbox.path === '' || !parent) {
		account.mailboxes.push(mailbox.databaseId)
	} else {
		parent.mailboxes.push(mailbox.databaseId)
	}

	Object.defineProperty(mailbox, 'isSubscribed', {
		get() {
			return this.attributes?.includes('\\subscribed') ?? false
		},
	})
})

function transformMailboxName(account, mailbox) {
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

export default function mainStoreActions() {
	return {
		updateSyncTimestamp() {
			this.syncTimestamp = Date.now()
		},
		savePreference({
			key,
			value,
		}) {
			return handleHttpAuthErrors(async () => {
				const newValue = await savePreference(key, value)
				this.savePreferenceMutation({
					key,
					value: newValue.value,
				})
			})
		},
		async fetchAccounts() {
			return handleHttpAuthErrors(async () => {
				const accounts = await fetchAllAccounts()
				accounts.forEach((account) => this.addAccountMutation(account))
				return this.getAccounts
			})
		},
		async fetchAccount(id) {
			return handleHttpAuthErrors(async () => {
				const account = await fetchAccount(id)
				this.addAccountMutation(account)
				return account
			})
		},
		async startAccountSetup(config) {
			const account = await createAccount(config)
			logger.debug(`account ${account.id} created`, { account })
			return account
		},
		async finishAccountSetup({ account }) {
			logger.debug(`Fetching mailboxes for account ${account.id},  …`, { account })
			account.mailboxes = await fetchAllMailboxes(account.id)
			this.addAccountMutation(account)
			logger.debug('New account mailboxes fetched', {
				account,
				mailboxes: account.mailboxes,
			})
			return account
		},
		async updateAccount(config) {
			return handleHttpAuthErrors(async () => {
				const account = await updateAccount(config)
				logger.debug('account updated', { account })
				this.editAccountMutation(account)
				return account
			})
		},
		async patchAccount({
			account,
			data,
		}) {
			return handleHttpAuthErrors(async () => {
				const patchedAccount = await patchAccount(account, data)
				logger.debug('account patched', {
					account: patchedAccount,
					data,
				})
				this.patchAccountMutation({
					account,
					data,
				})
				return account
			})
		},
		async updateAccountSignature({
			account,
			signature,
		}) {
			return handleHttpAuthErrors(async () => {
				await updateSignature(account, signature)
				logger.debug('account signature updated', {
					account,
					signature,
				})
				const updated = Object.assign({}, account, { signature })
				this.editAccountMutation(updated)
				return account
			})
		},
		async setAccountSetting({
			accountId,
			key,
			value,
		}) {
			return handleHttpAuthErrors(async () => {
				this.setAccountSettingMutation({
					accountId,
					key,
					value,
				})
				return await savePreference('account-settings', JSON.stringify(this.allAccountSettings))
			})
		},
		async deleteAccount(account) {
			return handleHttpAuthErrors(async () => {
				try {
					await deleteAccount(account.id)
				} catch (error) {
					logger.error('could not delete account', { error })
					throw error
				}
			})
		},
		async deleteMailbox({ mailbox }) {
			return handleHttpAuthErrors(async () => {
				await deleteMailbox(mailbox.databaseId)
				this.removeMailboxMutation({ id: mailbox.databaseId })
			})
		},
		async clearMailbox({ mailbox }) {
			return handleHttpAuthErrors(async () => {
				await clearMailbox(mailbox.databaseId)
				this.removeEnvelopesMutation({ id: mailbox.databaseId })
				this.setMailboxUnreadCountMutation({ id: mailbox.databaseId })
			})
		},
		async createMailbox({
			account,
			name,
		}) {
			return handleHttpAuthErrors(async () => {
				const prefixed = (account.personalNamespace && !name.startsWith(account.personalNamespace))
					? account.personalNamespace + name
					: name
				const mailbox = await createMailbox(account.id, prefixed)
				console.debug(`mailbox ${prefixed} created for account ${account.id}`, { mailbox })
				this.addMailboxMutation({
					account,
					mailbox,
				})
				this.expandAccountMutation(account.id)
				this.setAccountSettingMutation({
					accountId: account.id,
					key: 'collapsed',
					value: false,
				})
				return mailbox
			})
		},
		async moveAccount({
			account,
			up,
		}) {
			return handleHttpAuthErrors(async () => {
				const accounts = this.getAccounts
				const index = accounts.indexOf(account)
				if (up) {
					const previous = accounts[index - 1]
					accounts[index - 1] = account
					accounts[index] = previous
				} else {
					const next = accounts[index + 1]
					accounts[index + 1] = account
					accounts[index] = next
				}
				return await Promise.all(
					accounts.map((account, idx) => {
						if (account.id === 0) {
							return Promise.resolve()
						}
						this.saveAccountsOrderMutation({
							account,
							order: idx,
						})
						return patchAccount(account, { order: idx })
					}),
				)
			})
		},
		async markMailboxRead({
			accountId,
			mailboxId,
		}) {
			return handleHttpAuthErrors(async () => {
				const mailbox = this.getMailbox(mailboxId)

				if (mailbox.isUnified) {
					const findIndividual = findIndividualMailboxes(this.getMailboxes, mailbox.specialRole)
					const individualMailboxes = findIndividual(this.getAccounts)
					return Promise.all(
						individualMailboxes.map((mb) =>
							this.markMailboxReadMutation({
								accountId: mb.accountId,
								mailboxId: mb.databaseId,
							}),
						),
					)
				}

				const updated = Object.assign({}, mailbox)
				updated.unread = 0

				await markMailboxRead(mailboxId)
				this.updateMailboxMutation({
					mailbox: updated,
				})

				await this.syncEnvelopes({
					accountId,
					mailboxId,
				})
			})
		},
		async changeMailboxSubscription({
			mailbox,
			subscribed,
		}) {
			return handleHttpAuthErrors(async () => {
				logger.debug(`toggle subscription for mailbox ${mailbox.databaseId}`, {
					mailbox,
					subscribed,
				})
				const updated = await patchMailbox(mailbox.databaseId, { subscribed })

				this.updateMailboxMutation({
					mailbox: updated,
				})
				logger.debug(`subscription for mailbox ${mailbox.databaseId} updated`, {
					mailbox,
					updated,
				})
			})
		},
		async patchMailbox({
			mailbox,
			attributes,
		}) {
			return handleHttpAuthErrors(async () => {
				logger.debug('patching mailbox', {
					mailbox,
					attributes,
				})

				const updated = await patchMailbox(mailbox.databaseId, attributes)

				this.updateMailboxMutation({
					mailbox: updated,
				})
				logger.debug(`mailbox ${mailbox.databaseId} patched`, {
					mailbox,
					updated,
				})
			})
		},
		async startComposerSession({
			type = 'imap',
			data = {},
			reply,
			forwardedMessages = [],
			templateMessageId,
			isBlankMessage = false,
		}) {
			// Silently close old session if already saved and show a discard modal otherwise
			if (this.composerSessionId && !this.composerMessageIsSaved) {
				// TODO: Nice to have: Add button to save current pending message
				const discard = await new Promise((resolve) => OC.dialogs.confirmDestructive(
					t('mail', 'There is already a message in progress. All unsaved changes will be lost if you continue!'),
					t('mail', 'Discard changes'),
					{
						type: OC.dialogs.YES_NO_BUTTONS,
						confirm: t('mail', 'Discard unsaved changes'),
						confirmClasses: 'error',
						cancel: t('mail', 'Keep editing message'),
					},
					(decision) => {
						resolve(decision)
					},
				))
				if (!discard) {
					this.showMessageComposer()
					return
				}
			}

			return handleHttpAuthErrors(async () => {
				if (reply) {
					const original = await this.fetchMessage(reply.data.databaseId)

					// Fetch and transform the body into a rich text object
					if (original.hasHtmlBody) {
						const resp = await Axios.get(
							generateUrl('/apps/mail/api/messages/{id}/html?plain=true', {
								id: original.databaseId,
							}),
						)

						resp.data = DOMPurify.sanitize(resp.data, {
							FORBID_TAGS: ['style'],
						})

						data.isHtml = true
						data.bodyHtml = resp.data
						if (reply.suggestedReply) {
							data.bodyHtml = `<p>${reply.suggestedReply}<\\p>` + data.bodyHtml
						}
					} else {
						data.isHtml = false
						data.bodyPlain = original.body
						if (reply.suggestedReply) {
							data.bodyPlain = `${reply.suggestedReply}\n` + data.bodyPlain
						}
					}

					if (reply.mode === 'reply') {
						logger.debug('Show simple reply composer', { reply })
						let to = original.replyTo !== undefined ? original.replyTo : reply.data.from
						if (reply.followUp) {
							to = reply.data.to
						}
						this.startComposerSessionMutation({
							data: {
								accountId: reply.data.accountId,
								to,
								cc: [],
								subject: buildReplySubject(reply.data.subject),
								isHtml: data.isHtml,
								bodyHtml: data.bodyHtml,
								bodyPlain: data.bodyPlain,
								replyTo: reply.data,
								smartReply: reply.smartReply,
							},
						})
						return
					} else if (reply.mode === 'replyAll') {
						logger.debug('Show reply all reply composer', { reply })
						const account = this.getAccount(reply.data.accountId)
						const recipients = buildReplyRecipients(reply.data, {
							email: account.emailAddress,
							label: account.name,
						}, original.replyTo)
						this.startComposerSessionMutation({
							data: {
								accountId: reply.data.accountId,
								to: recipients.to,
								cc: recipients.cc,
								subject: buildReplySubject(reply.data.subject),
								isHtml: data.isHtml,
								bodyHtml: data.bodyHtml,
								bodyPlain: data.bodyPlain,
								replyTo: reply.data,
							},
						})
						return
					} else if (reply.mode === 'forward') {
						logger.debug('Show forward composer', { reply })
						this.startComposerSessionMutation({
							data: {
								accountId: reply.data.accountId,
								to: [],
								cc: [],
								subject: buildForwardSubject(reply.data.subject),
								isHtml: data.isHtml,
								bodyHtml: data.bodyHtml,
								bodyPlain: data.bodyPlain,
								forwardFrom: reply.data,
								attachments: original.attachments.map(attachment => ({
									...attachment,
									mailboxId: original.mailboxId,
									// messageId for attachments is actually the uid
									uid: attachment.messageId,
									type: 'message-attachment',
								})),
							},
						})
						return
					}
				} else if (templateMessageId) {
					const message = await this.fetchMessage(templateMessageId)
					// Merge the original into any existing data
					data = {
						...data,
						message,
					}

					// Fetch and transform the body into a rich text object
					if (message.hasHtmlBody) {
						const resp = await Axios.get(
							generateUrl('/apps/mail/api/messages/{id}/html?plain=true', {
								id: templateMessageId,
							}),
						)

						resp.data = DOMPurify.sanitize(resp.data, {
							FORBID_TAGS: ['style'],
						})

						data.isHtml = true
						data.bodyHtml = resp.data
					} else {
						data.isHtml = false
						data.bodyPlain = message.body
					}

					// TODO: implement attachments
					if (message.attachments.length) {
						showWarning(t('mail', 'Attachments were not copied. Please add them manually.'))
					}
				}

				// Stop schedule when editing outbox messages and backup sendAt timestamp
				let originalSendAt
				if (type === 'outbox' && data.id && data.sendAt) {
					originalSendAt = data.sendAt
					const outboxStore = useOutboxStore()
					await outboxStore.stopMessage({ message: { ...data } })
				}

				this.startComposerSessionMutation({
					type,
					data,
					forwardedMessages,
					templateMessageId,
					originalSendAt,
				})

				// Blank messages can be safely discarded (without saving a draft) until changes are made
				if (isBlankMessage) {
					this.setComposerMessageSavedMutation(true)
				}
			})
		},
		async stopComposerSession({
			restoreOriginalSendAt = false,
			moveToImap = false,
			id,
		} = {}) {
			return handleHttpAuthErrors(async () => {
				// Restore original sendAt timestamp when requested
				const message = this.composerMessage
				const messageData = { ...this.composerMessage.data }
				if (restoreOriginalSendAt && message.type === 'outbox' && message.options?.originalSendAt) {
					messageData.sendAt = message.options.originalSendAt
					updateDraft(messageData)
				}
				if (moveToImap) {
					await moveDraft(id)
				}

				this.stopComposerSessionMutation()
			})
		},
		patchComposerData(data) {
			this.patchComposerDataMutation(data)
			this.setComposerMessageSavedMutation(false)
		},
		async fetchEnvelope({
			accountId,
			id,
		}) {
			return handleHttpAuthErrors(async () => {
				const cached = this.getEnvelope(id)
				if (cached) {
					logger.debug(`using cached value for envelope ${id}`)
					return cached
				}

				const envelope = await fetchEnvelope(accountId, id)
				// Only commit if not undefined (not found)
				if (envelope) {
					this.addEnvelopesMutation({
						envelopes: [envelope],
					})
				}

				// Always use the object from the store
				return this.getEnvelope(id)
			})
		},
		fetchEnvelopes({
			mailboxId,
			query,
			addToUnifiedMailboxes = true,
			includeCacheBuster = false,
		}) {
			return handleHttpAuthErrors(async () => {
				const mailbox = this.getMailbox(mailboxId)

				if (mailbox.isUnified) {
					const fetchIndividualLists = pipe(
						map((mb) =>
							this.fetchEnvelopes({
								mailboxId: mb.databaseId,
								query,
								addToUnifiedMailboxes: false,
								sort: this.getPreference('sort-order'),
								view: this.getPreference('layout-message-view'),
							}),
						),
						Promise.all.bind(Promise),
						andThen(map(sliceToPage)),
					)
					const fetchUnifiedEnvelopes = pipe(
						findIndividualMailboxes(this.getMailboxes, mailbox.specialRole),
						fetchIndividualLists,
						andThen(combineEnvelopeLists(this.getPreference('sort-order'))),
						andThen(sliceToPage),
						andThen(
							tap((envelopes) =>
								this.addEnvelopesMutation({
									envelopes,
									query,
								}),
							),
						),
					)

					return fetchUnifiedEnvelopes(this.getAccounts)
				}

				return pipe(
					fetchEnvelopes,
					andThen(
						tap((envelopes) =>
							this.addEnvelopesMutation({
								query,
								envelopes,
								addToUnifiedMailboxes,
							}),
						),
					),
				)(mailbox.accountId, mailboxId, query, undefined, PAGE_SIZE, this.getPreference('sort-order'), this.getPreference('layout-message-view'), includeCacheBuster ? mailbox.cacheBuster : undefined)
			})
		},
		async fetchNextEnvelopePage({
			mailboxId,
			query,
		}) {
			return handleHttpAuthErrors(async () => {
				const envelopes = await this.fetchNextEnvelopes({
					mailboxId,
					query,
					quantity: PAGE_SIZE,
				})
				return envelopes
			})
		},
		async fetchNextEnvelopes({
			mailboxId,
			query,
			quantity,
			rec = true,
			addToUnifiedMailboxes = true,
		}) {
			return handleHttpAuthErrors(async () => {
				const mailbox = this.getMailbox(mailboxId)

				if (mailbox.isUnified) {
					const getIndivisualLists = curry((query, m) => this.getEnvelopes(m.databaseId, query))
					const individualCursor = curry((query, m) =>
						prop('dateInt', last(this.getEnvelopes(m.databaseId, query))),
					)
					const cursor = individualCursor(query, mailbox)

					if (cursor === undefined) {
						throw new Error('Unified list has no tail')
					}
					const newestFirst = this.getPreference('sort-order') === 'newest'
					const nextLocalUnifiedEnvelopes = pipe(
						findIndividualMailboxes(this.getMailboxes, mailbox.specialRole),
						map(getIndivisualLists(query)),
						combineEnvelopeLists(this.getPreference('sort-order')),
						filter(
							where({
								dateInt: newestFirst ? gt(cursor) : lt(cursor),
							}),
						),
						slice(0, quantity),
					)
					// We know the next envelopes based on local data
					// We have to fetch individual envelopes only if it ends in the known
					// next fetch. If it ends after, we have all the relevant data already.
					const needsFetch = curry((query, nextEnvelopes, mb) => {
						const c = individualCursor(query, mb)
						if (nextEnvelopes.length < quantity) {
							return true
						}

						if (this.getPreference('sort-order') === 'newest') {
							return c >= last(nextEnvelopes).dateInt
						} else {
							return c <= last(nextEnvelopes).dateInt
						}
					})

					const mailboxesToFetch = (accounts) =>
						pipe(
							findIndividualMailboxes(this.getMailboxes, mailbox.specialRole),
							tap(mbs => console.info('individual mailboxes', mbs)),
							filter(needsFetch(query, nextLocalUnifiedEnvelopes(accounts))),
						)(accounts)
					const mbs = mailboxesToFetch(this.getAccounts)

					if (rec && mbs.length) {
						logger.debug('not enough local envelopes for the next unified page. ' + mbs.length + ' fetches required', {
							mailboxes: mbs.map(mb => mb.databaseId),
						})
						return pipe(
							map((mb) =>
								this.fetchNextEnvelopes({
									mailboxId: mb.databaseId,
									query,
									quantity,
									addToUnifiedMailboxes: false,
								}),
							),
							Promise.all.bind(Promise),
							andThen(() =>
								this.fetchNextEnvelopes({
									mailboxId,
									query,
									quantity,
									rec: false,
									addToUnifiedMailboxes: true,
								}),
							),
						)(mbs)
					}

					const envelopes = nextLocalUnifiedEnvelopes(this.getAccounts)
					logger.debug('next unified page can be built locally and consists of ' + envelopes.length + ' envelopes', { addToUnifiedMailboxes })
					this.addEnvelopesMutation({
						query,
						envelopes,
						addToUnifiedMailboxes,
					})
					return envelopes
				}

				const list = mailbox.envelopeLists[normalizedEnvelopeListId(query)]
				if (list === undefined) {
					console.warn("envelope list is not defined, can't fetch next envelopes", mailboxId, query)
					return Promise.resolve([])
				}
				const lastEnvelopeId = last(list)
				if (typeof lastEnvelopeId === 'undefined') {
					console.error('mailbox is empty', list)
					return Promise.reject(new Error('Local mailbox has no envelopes, cannot determine cursor'))
				}
				const lastEnvelope = this.getEnvelope(lastEnvelopeId)
				if (typeof lastEnvelope === 'undefined') {
					return Promise.reject(new Error('Cannot find last envelope. Required for the mailbox cursor'))
				}

				return fetchEnvelopes(mailbox.accountId, mailboxId, query, lastEnvelope.dateInt, quantity, this.getPreference('sort-order')).then((envelopes) => {
					logger.debug(`fetched ${envelopes.length} messages for mailbox ${mailboxId}`, {
						envelopes,
						addToUnifiedMailboxes,
					})
					this.addEnvelopesMutation({
						query,
						envelopes,
						addToUnifiedMailboxes,
					})
					return envelopes
				})
			})
		},
		async syncEnvelopes({
			mailboxId,
			query,
			init = false,
		}) {
			return handleHttpAuthErrors(async () => {
				logger.debug(`starting mailbox sync of ${mailboxId} (${query})`)

				const mailbox = this.getMailbox(mailboxId)

				// Skip superfluous requests if using passwordless authentication. They will fail anyway.
				const passwordIsUnavailable = this.getPreference('password-is-unavailable', false)
				const isDisabled = (account) => passwordIsUnavailable && !!account.provisioningId

				if (mailbox.isUnified) {
					return Promise.all(
						this.getAccounts
							.filter((account) => !account.isUnified && !isDisabled(account))
							.map((account) =>
								Promise.all(
									this
										.getMailboxes(account.id)
										.filter((mb) => mb.specialRole === mailbox.specialRole)
										.map((mailbox) =>
											this.syncEnvelopes({
												mailboxId: mailbox.databaseId,
												query,
												init,
											}),
										),
								),
							),
					)
				} else if (mailbox.isPriorityInbox && query === undefined) {
					return Promise.all(
						getPrioritySearchQueries().map((query) => {
							return Promise.all(
								this.getAccounts
									.filter((account) => !account.isUnified && !isDisabled(account))
									.map((account) =>
										Promise.all(
											this
												.getMailboxes(account.id)
												.filter((mb) => mb.specialRole === mailbox.specialRole)
												.map((mailbox) =>
													this.syncEnvelopes({
														mailboxId: mailbox.databaseId,
														query,
														init,
													}),
												),
										),
									),
							)
						}),
					)
				}

				const ids = this.getEnvelopes(mailboxId, query).map((env) => env.databaseId)
				const lastTimestamp = this.getPreference('sort-order') === 'newest' ? null : this.getEnvelopes(mailboxId, query)[0]?.dateInt
				logger.debug(`mailbox sync of ${mailboxId} (${query}) has ${ids.length} known IDs. ${lastTimestamp} is the last known message timestamp`, { mailbox })
				return syncEnvelopesExternal(mailbox.accountId, mailboxId, ids, lastTimestamp, query, init, this.getPreference('sort-order'))
					.then((syncData) => {
						logger.debug(`mailbox ${mailboxId} (${query}) synchronized, ${syncData.newMessages.length} new, ${syncData.changedMessages.length} changed and ${syncData.vanishedMessages.length} vanished messages`)

						const unifiedMailbox = this.getUnifiedMailbox(mailbox.specialRole)

						this.addEnvelopesMutation({
							envelopes: syncData.newMessages,
							query,
						})

						syncData.newMessages.forEach((envelope) => {
							if (unifiedMailbox) {
								this.updateEnvelopeMutation({
									envelope,
								})
							}
						})
						syncData.changedMessages.forEach((envelope) => {
							this.updateEnvelopeMutation({
								envelope,
							})
						})
						syncData.vanishedMessages.forEach((id) => {
							this.removeEnvelopeMutation({
								id,
							})
							// Already removed from unified inbox
						})

						this.setMailboxUnreadCountMutation({
							id: mailboxId,
							unread: syncData.stats.unread,
						})

						return syncData.newMessages
					})
					.catch((error) => {
						return matchError(error, {
							[SyncIncompleteError.getName()]: () => {
								console.warn(`(initial) sync of mailbox ${mailboxId} (${query}) is incomplete, retriggering`)
								return this.syncEnvelopes({
									mailboxId,
									query,
									init,
								})
							},
							[MailboxLockedError.getName()]: (error) => {
								if (init) {
									logger.info('Sync failed because the mailbox is locked, stopping here because this is an initial sync', { error })
									throw error
								}

								logger.info('Sync failed because the mailbox is locked, retriggering', { error })
								return wait(1500).then(() => this.syncEnvelopes({
									mailboxId,
									query,
									init,
								}))
							},
							default(error) {
								console.error('Could not sync envelopes: ' + error.message, error)
								throw error
							},
						})
					})
			})
		},
		async syncInboxes() {
			// Skip superfluous requests if using passwordless authentication. They will fail anyway.
			const passwordIsUnavailable = this.getPreference('password-is-unavailable', false)
			const isDisabled = (account) => passwordIsUnavailable && !!account.provisioningId

			return handleHttpAuthErrors(async () => {
				const results = await Promise.all(
					this.getAccounts
						.filter((a) => !a.isUnified && !isDisabled(a))
						.map((account) => {
							return Promise.all(
								this.getMailboxes(account.id).map(async (mailbox) => {
									if (mailbox.specialRole !== 'inbox') {
										return
									}

									const list = mailbox.envelopeLists[normalizedEnvelopeListId(undefined)]
									if (list === undefined) {
										await this.fetchEnvelopes({
											mailboxId: mailbox.databaseId,
										})
									}

									return await this.syncEnvelopes({
										mailboxId: mailbox.databaseId,
									})
								}),
							)
						}),
				)
				const newMessages = flatMapDeep(identity, results).filter((m) => m !== undefined)
				if (newMessages.length === 0) {
					return
				}

				try {
					// Make sure the priority inbox is updated as well
					logger.info('updating priority inbox')
					for (const query of [priorityImportantQuery, priorityOtherQuery]) {
						logger.info("sync'ing priority inbox section", { query })
						const mailbox = this.getMailbox(UNIFIED_INBOX_ID)
						const list = mailbox.envelopeLists[normalizedEnvelopeListId(query)]
						if (list === undefined) {
							await this.fetchEnvelopes({
								mailboxId: UNIFIED_INBOX_ID,
								query,
							})
						}

						await this.syncEnvelopes({
							mailboxId: UNIFIED_INBOX_ID,
							query,
						})
					}
				} finally {
					showNewMessagesNotification(newMessages)
				}
			})
		},
		toggleEnvelopeFlagged(envelope) {
			return handleHttpAuthErrors(async () => {
				// Change immediately and switch back on error
				const oldState = envelope.flags.flagged
				this.flagEnvelopeMutation({
					envelope,
					flag: 'flagged',
					value: !oldState,
				})

				try {
					await setEnvelopeFlags(envelope.databaseId, {
						flagged: !oldState,
					})
				} catch (error) {
					logger.error('Could not toggle message flagged state', { error })

					// Revert change
					this.flagEnvelopeMutation({
						envelope,
						flag: 'flagged',
						value: oldState,
					})

					throw error
				}
			})
		},
		async toggleEnvelopeImportant(envelope) {
			return handleHttpAuthErrors(async () => {
				const importantLabel = '$label1'
				const hasTag = this
					.getEnvelopeTags(envelope.databaseId)
					.some((tag) => tag.imapLabel === importantLabel)
				if (hasTag) {
					await this.removeEnvelopeTag({
						envelope,
						imapLabel: importantLabel,
					})
				} else {
					await this.addEnvelopeTag({
						envelope,
						imapLabel: importantLabel,
					})
				}
			})
		},
		async toggleEnvelopeSeen({
			envelope,
			seen,
		}) {
			return handleHttpAuthErrors(async () => {
				// Change immediately and switch back on error
				const oldState = envelope.flags.seen
				const newState = seen === undefined ? !oldState : seen
				this.flagEnvelopeMutation({
					envelope,
					flag: 'seen',
					value: newState,
				})

				try {
					await setEnvelopeFlags(envelope.databaseId, {
						seen: newState,
					})
				} catch (error) {
					console.error('could not toggle message seen state', error)

					// Revert change
					this.flagEnvelopeMutation({
						envelope,
						flag: 'seen',
						value: oldState,
					})

					throw error
				}
			})
		},
		async toggleEnvelopeJunk({
			envelope,
			removeEnvelope,
		}) {
			return handleHttpAuthErrors(async () => {
				// Change immediately and switch back on error
				const oldState = envelope.flags.$junk
				this.flagEnvelopeMutation({
					envelope,
					flag: '$junk',
					value: !oldState,
				})
				this.flagEnvelopeMutation({
					envelope,
					flag: '$notjunk',
					value: oldState,
				})

				if (removeEnvelope) {
					this.removeEnvelopeMutation({ id: envelope.databaseId })
				}

				try {
					await setEnvelopeFlags(envelope.databaseId, {
						$junk: !oldState,
						$notjunk: oldState,
					})
				} catch (error) {
					console.error('could not toggle message junk state', error)

					if (removeEnvelope) {
						this.addEnvelopesMutation([envelope])
					}

					// Revert change
					this.flagEnvelopeMutation({
						envelope,
						flag: '$junk',
						value: oldState,
					})
					this.flagEnvelopeMutation({
						envelope,
						flag: '$notjunk',
						value: !oldState,
					})

					throw error
				}
			})
		},
		async markEnvelopeFavoriteOrUnfavorite({
			envelope,
			favFlag,
		}) {
			return handleHttpAuthErrors(async () => {
				// Change immediately and switch back on error
				const oldState = envelope.flags.flagged
				this.flagEnvelopeMutation({
					envelope,
					flag: 'flagged',
					value: favFlag,
				})

				try {
					await setEnvelopeFlags(envelope.databaseId, {
						flagged: favFlag,
					})
				} catch (error) {
					console.error('could not favorite/unfavorite message ' + envelope.uid, error)

					// Revert change
					this.flagEnvelopeMutation({
						envelope,
						flag: 'flagged',
						value: oldState,
					})

					throw error
				}
			})
		},
		async markEnvelopeImportantOrUnimportant({
			envelope,
			addTag,
		}) {
			return handleHttpAuthErrors(async () => {
				const importantLabel = '$label1'
				const hasTag = this
					.getEnvelopeTags(envelope.databaseId)
					.some((tag) => tag.imapLabel === importantLabel)
				if (hasTag && !addTag) {
					await this.removeEnvelopeTag({
						envelope,
						imapLabel: importantLabel,
					})
				} else if (!hasTag && addTag) {
					await this.addEnvelopeTag({
						envelope,
						imapLabel: importantLabel,
					})
				}
			})
		},
		async fetchThread(id) {
			return handleHttpAuthErrors(async () => {
				const thread = await fetchThread(id)
				this.addEnvelopeThreadMutation({
					id,
					thread,
				})
				return thread
			})
		},
		async fetchMessage(id) {
			if (this.messages[id]) {
				return this.messages[id]
			}

			return handleHttpAuthErrors(async () => {
				const message = await fetchMessage(id)
				// Only commit if not undefined (not found)
				if (message) {
					this.addMessageMutation({
						message,
					})
				}
				return message
			})
		},
		async fetchItineraries(id) {
			return handleHttpAuthErrors(async () => {
				const itineraries = await fetchMessageItineraries(id)
				this.addMessageItinerariesMutation({
					id,
					itineraries,
				})
				return itineraries
			})
		},
		async fetchDkim(id) {
			return handleHttpAuthErrors(async () => {
				const result = await fetchMessageDkim(id)
				this.addMessageDkimMutation({
					id,
					result,
				})
				return result
			})
		},
		async addInternalAddress({
			address,
			type,
		}) {
			return handleHttpAuthErrors(async () => {
				const internalAddress = await addInternalAddress(address, type)
				this.addInternalAddressMutation(internalAddress)
				console.debug('internal address added')
			})
		},
		async removeInternalAddress({
			id,
			address,
			type,
		}) {
			return handleHttpAuthErrors(async () => {
				try {
					await removeInternalAddress(address, type)
					this.removeInternalAddressMutation({ addressId: id })
					console.debug('internal address removed')
				} catch (error) {
					console.error('could not delete internal address', error)
					throw error
				}
			})
		},
		async deleteMessage({ id }) {
			return handleHttpAuthErrors(async () => {
				this.removeEnvelopeMutation({ id })

				try {
					await deleteMessage(id)
					this.removeMessageMutation({ id })
					console.debug('message removed')
				} catch (err) {
					console.error('could not delete message', err)
					const envelope = this.getEnvelope(id)
					if (envelope) {
						this.addEnvelopesMutation({ envelopes: [envelope] })
					} else {
						logger.error('could not find envelope', { id })
					}
					throw err
				}
			})
		},
		async createAlias({
			account,
			alias,
			name,
		}) {
			return handleHttpAuthErrors(async () => {
				const entity = await AliasService.createAlias(account.id, alias, name)
				this.createAliasMutation({
					account,
					alias: entity,
				})
			})
		},
		async deleteAlias({
			account,
			aliasId,
		}) {
			return handleHttpAuthErrors(async () => {
				const entity = await AliasService.deleteAlias(account.id, aliasId)
				this.deleteAliasMutation({
					account,
					aliasId: entity.id,
				})
			})
		},
		async updateAlias({
			account,
			aliasId,
			alias,
			name,
			smimeCertificateId,
		}) {
			return handleHttpAuthErrors(async () => {
				const entity = await AliasService.updateAlias(
					account.id,
					aliasId,
					alias,
					name,
					smimeCertificateId,
				)
				this.patchAliasMutation({
					account,
					aliasId: entity.id,
					data: {
						alias: entity.alias,
						name: entity.name,
						smimeCertificateId: entity.smimeCertificateId,
					},
				})
				this.editAccountMutation(account)
			})
		},
		async updateAliasSignature({
			account,
			aliasId,
			signature,
		}) {
			return handleHttpAuthErrors(async () => {
				const entity = await AliasService.updateSignature(account.id, aliasId, signature)
				this.patchAliasMutation({
					account,
					aliasId: entity.id,
					data: { signature: entity.signature },
				})
				this.editAccountMutation(account)
			})
		},
		async renameMailbox({
			account,
			mailbox,
			newName,
		}) {
			return handleHttpAuthErrors(async () => {
				const newMailbox = await patchMailbox(mailbox.databaseId, {
					name: newName,
				})

				console.debug(`mailbox ${mailbox.databaseId} renamed to ${newName}`, { mailbox })
				this.removeMailboxMutation({ id: mailbox.databaseId })
				this.addMailboxMutation({
					account,
					mailbox: newMailbox,
				})
			})
		},
		async moveMessage({
			id,
			destMailboxId,
		}) {
			return handleHttpAuthErrors(async () => {
				await moveMessage(id, destMailboxId)
				this.removeEnvelopeMutation({ id })
				this.removeMessageMutation({ id })
			})
		},
		async snoozeMessage({
			id,
			unixTimestamp,
			destMailboxId,
		}) {
			return handleHttpAuthErrors(async () => {
				await snoozeMessage(id, unixTimestamp, destMailboxId)
				this.removeEnvelopeMutation({ id })
				this.removeMessageMutation({ id })
			})
		},
		async unSnoozeMessage({ id }) {
			return handleHttpAuthErrors(async () => {
				await unSnoozeMessage(id)
				this.removeEnvelopeMutation({ id })
				this.removeMessageMutation({ id })
			})
		},
		async fetchActiveSieveScript({ accountId }) {
			return handleHttpAuthErrors(async () => {
				const scriptData = await getActiveScript(accountId)
				this.setActiveSieveScriptMutation({
					accountId,
					scriptData,
				})
			})
		},
		async updateActiveSieveScript({
			accountId,
			scriptData,
		}) {
			return handleHttpAuthErrors(async () => {
				await updateActiveScript(accountId, scriptData)
				this.setActiveSieveScriptMutation({
					accountId,
					scriptData,
				})
			})
		},
		async updateSieveAccount({
			account,
			data,
		}) {
			return handleHttpAuthErrors(async () => {
				logger.debug(`update sieve settings for account ${account.id}`)
				try {
					await updateSieveAccount(account.id, data)
					this.patchAccountMutation({
						account,
						data,
					})
				} catch (error) {
					logger.error('failed to update sieve account: ', { error })
					throw error
				}
			})
		},
		async createTag({
			displayName,
			color,
		}) {
			return handleHttpAuthErrors(async () => {
				const tag = await createEnvelopeTag(displayName, color)
				this.addTagMutation({ tag })
			})

		},
		async addEnvelopeTag({
			envelope,
			imapLabel,
		}) {
			return handleHttpAuthErrors(async () => {
				// TODO: fetch tags indepently of envelopes and only send tag id here
				const tag = await setEnvelopeTag(envelope.databaseId, imapLabel)
				if (!this.getTag(tag.id)) {
					this.addTagMutation({ tag })
				}

				this.addEnvelopeTagMutation({
					envelope,
					tagId: tag.id,
				})
			})
		},
		async removeEnvelopeTag({
			envelope,
			imapLabel,
		}) {
			return handleHttpAuthErrors(async () => {
				const tag = await removeEnvelopeTag(envelope.databaseId, imapLabel)
				this.removeEnvelopeTagMutation({
					envelope,
					tagId: tag.id,
				})
			})
		},
		async updateTag({
			tag,
			displayName,
			color,
		}) {
			return handleHttpAuthErrors(async () => {
				await updateEnvelopeTag(tag.id, displayName, color)
				this.updateTagMutation({
					tag,
					displayName,
					color,
				})
				logger.debug('tag updated', {
					tag,
					displayName,
					color,
				})
			})
		},
		async deleteTag({
			tag,
			accountId,
		}) {
			return handleHttpAuthErrors(async () => {
				await deleteTag(tag.id, accountId)
				this.deleteTagMutation({ tagId: tag.id })
				logger.debug('tag deleted', { tag })
			})
		},
		async deleteThread({ envelope }) {
			return handleHttpAuthErrors(async () => {
				this.removeEnvelopeMutation({ id: envelope.databaseId })

				try {
					await ThreadService.deleteThread(envelope.databaseId)
					console.debug('thread removed')
				} catch (e) {
					this.addEnvelopesMutation({ envelopes: [envelope] })
					console.error('could not delete thread', e)
					throw e
				}
			})
		},
		async moveThread({
			envelope,
			destMailboxId,
		}) {
			return handleHttpAuthErrors(async () => {
				this.removeEnvelopeMutation({ id: envelope.databaseId })

				try {
					await ThreadService.moveThread(envelope.databaseId, destMailboxId)
					console.debug('thread removed')
				} catch (e) {
					this.addEnvelopesMutation({ envelopes: [envelope] })
					console.error('could not move thread', e)
					throw e
				}
			})
		},
		async snoozeThread({
			envelope,
			unixTimestamp,
			destMailboxId,
		}) {
			return handleHttpAuthErrors(async () => {
				try {
					await ThreadService.snoozeThread(envelope.databaseId, unixTimestamp, destMailboxId)
					console.debug('thread snoozed')
				} catch (e) {
					this.addEnvelopesMutation({ envelopes: [envelope] })
					console.error('could not snooze thread', e)
					throw e
				}
				this.removeEnvelopeMutation({ id: envelope.databaseId })
			})
		},
		async unSnoozeThread({ envelope }) {
			return handleHttpAuthErrors(async () => {
				try {
					await ThreadService.unSnoozeThread(envelope.databaseId)
					console.debug('thread unSnoozed')
				} catch (e) {
					console.error('could not unsnooze thread', e)
					throw e
				}
				this.removeEnvelopeMutation({ id: envelope.databaseId })
			})
		},

		/**
		 * Retrieve and commit the principal of the current user.
		 *
		 * @param {object} context Vuex store context
		 * @param {Function} context.commit Vuex store mutations
		 */
		async fetchCurrentUserPrincipal() {
			return handleHttpAuthErrors(async () => {
				await initializeClientForUserView()
				this.setCurrentUserPrincipalMutation({ currentUserPrincipal: getCurrentUserPrincipal() })
			})
		},

		/**
		 * Retrieve and commit calendars.
		 *
		 * @param {object} context Vuex store context
		 * @param {Function} context.commit Vuex store mutations
		 * @return {Promise<void>}
		 */
		async loadCollections() {
			await handleHttpAuthErrors(async () => {
				const { calendars } = await findAll()
				for (const calendar of calendars) {
					this.addCalendarMutation({ calendar })
				}
			})
		},

		/**
		 * Fetch and commit all S/MIME certificate of the current user.
		 *
		 * @param {object} context Vuex store context
		 * @param {Function} context.commit Vuex store mutations
		 * @return {Promise<void>}
		 */
		async fetchSmimeCertificates() {
			return handleHttpAuthErrors(async () => {
				const certificates = await SmimeCertificateService.fetchAll()
				this.setSmimeCertificatesMutation(certificates)
			})
		},

		/**
		 * Delete an imported S/MIME certificate.
		 *
		 * @param {object} context Vuex store context
		 * @param {Function} context.commit Vuex store mutations
		 * @param id The id of the certificate to be deleted
		 * @return {Promise<void>}
		 */
		async deleteSmimeCertificate(id) {
			return handleHttpAuthErrors(async () => {
				await SmimeCertificateService.deleteCertificate(id)
				this.deleteSmimeCertificateMutation({ id })
			})
		},

		/**
		 * Create a new S/MIME certificate and persist it on the backend.
		 *
		 * @param {object} context Vuex store context
		 * @param {Function} context.commit Vuex store mutations
		 * @param {object} files
		 * @param {Blob} files.certificate
		 * @param {Blob=} files.privateKey
		 * @return {Promise<object>}
		 */
		async createSmimeCertificate(files) {
			return handleHttpAuthErrors(async () => {
				const certificate = await SmimeCertificateService.createCertificate(files)
				this.addSmimeCertificateMutation({ certificate })
				return certificate
			})
		},

		/**
		 * Update the S/MIME certificate of an account.
		 *
		 * @param {object} context Vuex store context
		 * @param {Function} context.commit Vuex store mutations
		 * @param {Function} context.this Vuex store this
		 * @param {object} data
		 * @param {object} data.accountId
		 * @param {number=} data.smimeCertificateId
		 * @param data.account
		 * @param context.account
		 * @param context.smimeCertificateId
		 * @return {Promise<void>}
		 */
		async updateAccountSmimeCertificate({
			account,
			smimeCertificateId,
		}) {
			return handleHttpAuthErrors(async () => {
				await updateAccountSmimeCertificate(account.id, smimeCertificateId)
				this.patchAccountMutation({
					account,
					data: { smimeCertificateId },
				})
			})
		},

		/**
		 * Should the envelope moved to the junk (or back to inbox)
		 *
		 * @param {object} context Vuex store context
		 * @param {object} context.this Vuex store this
		 * @param {object} envelope envelope object@
		 * @return {boolean}
		 */
		async moveEnvelopeToJunk(envelope) {
			const account = this.getAccount(envelope.accountId)
			if (account.junkMailboxId === null) {
				return false
			}

			if (!envelope.flags.$junk) {
				// move message to junk
				return envelope.mailboxId !== account.junkMailboxId
			}

			const inbox = this.getInbox(account.id)
			if (inbox === undefined) {
				return false
			}

			// move message to inbox
			return envelope.mailboxId !== inbox.databaseId
		},
		async createAndSetSnoozeMailbox(account) {
			const name = 'Snoozed'
			let snoozeMailboxId

			try {
				const createMailboxResponse = await this.createMailbox({
					account,
					name,
				})
				snoozeMailboxId = createMailboxResponse.databaseId
				logger.info(`mailbox ${name} created as ${snoozeMailboxId}`)
			} catch (e) {
				logger.error('could not create mailbox', { e })
			}

			if (snoozeMailboxId === undefined) {
				snoozeMailboxId = this.findMailboxByName(account.id, name).databaseId
			}

			if (snoozeMailboxId === undefined) {
				logger.error('Could not create snooze mailbox')
				showError(t('mail', 'Could not create snooze mailbox'))
				return
			}

			await this.patchAccount({
				account,
				data: {
					snoozeMailboxId,
				},
			})
		},
		async setLayout({ list }) {
			try {
				this.setOneLineLayoutMutation({
					list,
				})
			} catch (error) {
				logger.error('Could not set layouts', { error })
			}
		},
		async clearFollowUpReminder({ envelope }) {
			await this.removeEnvelopeTag({
				envelope,
				imapLabel: FOLLOW_UP_TAG_LABEL,
			})
			this.removeEnvelopeFromFollowUpMailboxMutation({
				id: envelope.databaseId,
			})
		},
		async checkFollowUpReminders() {
			const envelopes = this.getFollowUpReminderEnvelopes
			const messageIds = envelopes.map((envelope) => envelope.databaseId)
			if (messageIds.length === 0) {
				return
			}

			const data = await FollowUpService.checkMessageIds(messageIds)
			for (const messageId of data.wasFollowedUp) {
				const envelope = this.getEnvelope(messageId)
				if (!envelope) {
					continue
				}

				await this.clearFollowUpReminder({ envelope })
			}
		},
		async fetchMyTextBlocks() {
			const textBlocks = await fetchMyTextBlocks()
			this.setMyTextBlocks(textBlocks)
		},
		async fetchSharedTextBlocks() {
			const textBlocks = await fetchSharedTextBlocks()
			this.setSharedTextBlocks(textBlocks)
		},
		async createTextBlock({ title, content }) {
			const textBlock = await createTextBlock(title, content)
			this.addTextBlock(textBlock)
		},
		async deleteTextBlock({ id }) {
			await deleteTextBlock(id)
			this.deleteTextBlockLocally(id)
		},
		async patchTextBlock(textBlock) {
			const result = await updateTextBlock(textBlock)
			this.patchTextBlockLocally(result)
		},
		async createQuickAction(name, accountId) {
			const quickAction = await createQuickAction(name, accountId)
			this.addQuickActionLocally(quickAction)
			return quickAction
		},
		async deleteQuickAction(id) {
			await deleteQuickAction(id)
			this.deleteQuickActionLocally(id)
		},
		async patchQuickAction(id, name) {
			const quickAction = await updateQuickAction(id, name)
			this.patchQuickActionLocally(quickAction)
			return quickAction
		},
		sortAccounts(accounts) {
			accounts.sort((a1, a2) => a1.order - a2.order)
			return accounts
		},
		/**
		 * Convert envelope tag objects to references and add new tags to global list.
		 *
		 * @param {object} envelope envelope with tag objects
		 */
		normalizeTags(envelope) {
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
		},

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
		appendOrReplaceEnvelopeId(existing, envelope) {

			if (this.getPreference('layout-message-view') === 'singleton') {
				existing.push(envelope.databaseId)
			} else {
				const index = existing.findIndex((id) => this.envelopes[id].threadRootId === envelope.threadRootId)
				if (index === -1) {
					existing.push(envelope.databaseId)
				} else {
					existing[index] = envelope.databaseId
				}
			}

			return existing
		},
		savePreferenceMutation({
			key,
			value,
		}) {
			Vue.set(this.preferences, key, value)
		},
		setSessionExpiredMutation() {
			this.isExpiredSession = true
		},
		addAccountMutation(account) {
			account.collapsed = account.collapsed ?? true

			Vue.set(this.accountsUnmapped, account.id, account)

			this.accountList.push(account.id)

			const mappedAccounts = this.accountList.map((id) => this.accountsUnmapped[id])
			this.accountList = this.sortAccounts(mappedAccounts).map((a) => a.id)

			// Save the mailboxes to the store, but only keep IDs in the account's mailboxes list
			const mailboxes = sortMailboxes(account.mailboxes || [], account)
			Vue.set(account, 'mailboxes', [])
			Vue.set(account, 'aliases', account.aliases ?? [])

			mailboxes.map(addMailboxToState(this.mailboxes, account))
		},
		editAccountMutation(account) {
			Vue.set(this.accountsUnmapped, account.id, Object.assign({}, this.accountsUnmapped[account.id], account))
		},
		patchAccountMutation({
			account,
			data,
		}) {
			Vue.set(this.accountsUnmapped, account.id, Object.assign({}, this.accountsUnmapped[account.id], data))
		},
		saveAccountsOrderMutation({
			account,
			order,
		}) {
			Vue.set(account, 'order', order)
			this.accountList = this
				.sortAccounts(this.accountList.map((id) => this.accountsUnmapped[id]))
				.map((a) => a.id)
		},
		toggleAccountCollapsedMutation(accountId) {
			this.accountsUnmapped[accountId].collapsed = !this.accountsUnmapped[accountId].collapsed
		},
		expandAccountMutation(accountId) {
			this.accountsUnmapped[accountId].collapsed = false
		},
		setAccountSettingMutation({
			accountId,
			key,
			value,
		}) {
			const accountSettings = this.allAccountSettings.find(settings => settings.accountId === accountId)
			if (accountSettings) {
				accountSettings[key] = value
			} else {
				const newAccountSettings = { accountId }
				newAccountSettings[key] = value
				this.allAccountSettings.push(newAccountSettings)
			}
		},
		addMailboxMutation({
			account,
			mailbox,
		}) {
			addMailboxToState(this.mailboxes, account, mailbox)
		},
		updateMailboxMutation({ mailbox }) {
			const account = this.accountsUnmapped[mailbox.accountId]
			transformMailboxName(account, mailbox)
			Vue.set(this.mailboxes, mailbox.databaseId, mailbox)
		},
		removeMailboxMutation({ id }) {
			const mailbox = this.mailboxes[id]
			if (mailbox === undefined) {
				throw new Error(`Mailbox ${id} does not exist`)
			}
			const account = this.accountsUnmapped[mailbox.accountId]
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
		startComposerSessionMutation({
			type,
			data,
			forwardedMessages,
			originalSendAt,
			smartReply,
		}) {
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
		stopComposerSessionMutation() {
			this.composerSessionId = undefined
			this.newMessage = undefined
			this.showMessageComposer = false
		},
		/**
		 * Show composer modal if there is an ongoing session.
		 *
		 */
		showMessageComposerMutation() {
			if (this.composerSessionId) {
				this.showMessageComposer = true
			}
		},
		/**
		 * Hide composer modal without ending the current session.
		 *
		 */
		hideMessageComposerMutation() {
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
		addEnvelopesMutation({
			query,
			envelopes,
			addToUnifiedMailboxes = true,
		}) {
			if (envelopes.length === 0) {
				return
			}

			const idToDateInt = (id) => this.envelopes[id].dateInt

			const listId = normalizedEnvelopeListId(query)
			const orderByDateInt = orderBy(idToDateInt, this.preferences['sort-order'] === 'newest' ? 'desc' : 'asc')

			envelopes.forEach((envelope) => {
				const mailbox = this.mailboxes[envelope.mailboxId]
				const existing = mailbox.envelopeLists[listId] || []
				this.normalizeTags(envelope)
				Vue.set(this.envelopes, envelope.databaseId, Object.assign({}, this.envelopes[envelope.databaseId] || {}, envelope))
				Vue.set(envelope, 'accountId', mailbox.accountId)
				Vue.set(mailbox.envelopeLists, listId, uniq(orderByDateInt(this.appendOrReplaceEnvelopeId(existing, envelope))))
				if (!addToUnifiedMailboxes) {
					return
				}
				const unifiedAccount = this.accountsUnmapped[UNIFIED_ACCOUNT_ID]
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
			this.normalizeTags(envelope)
			Vue.set(existing, 'flags', envelope.flags)
			Vue.set(existing, 'tags', envelope.tags)
		},
		flagEnvelopeMutation({
			envelope,
			flag,
			value,
		}) {
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
		addEnvelopeTagMutation({
			envelope,
			tagId,
		}) {
			Vue.set(envelope, 'tags', uniq([...envelope.tags, tagId]))
		},
		updateTagMutation({
			tag,
			displayName,
			color,
		}) {
			tag.displayName = displayName
			tag.color = color
		},
		removeEnvelopeTagMutation({
			envelope,
			tagId,
		}) {
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

			this.accountsUnmapped[UNIFIED_ACCOUNT_ID].mailboxes
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
		removeAllEnvelopesMutation() {
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
		addMessageItinerariesMutation({
			id,
			itineraries,
		}) {
			const message = this.messages[id]
			if (!message) {
				return
			}
			Vue.set(message, 'itineraries', itineraries)
		},
		addMessageDkimMutation({
			id,
			result,
		}) {
			const message = this.messages[id]
			if (!message) {
				return
			}
			Vue.set(message, 'dkimValid', result.valid)
		},
		addEnvelopeThreadMutation({
			id,
			thread,
		}) {
			// Store the envelopes, merge into any existing object if one exists
			thread.forEach(e => {
				this.normalizeTags(e)
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
		createAliasMutation({
			account,
			alias,
		}) {
			account.aliases.push(alias)
		},
		deleteAliasMutation({
			account,
			aliasId,
		}) {
			const index = account.aliases.findIndex(temp => aliasId === temp.id)
			if (index !== -1) {
				account.aliases.splice(index, 1)
			}
		},
		patchAliasMutation({
			account,
			aliasId,
			data,
		}) {
			const index = account.aliases.findIndex(temp => aliasId === temp.id)
			if (index !== -1) {
				account.aliases[index] = Object.assign({}, account.aliases[index], data)
			}
		},
		setMailboxUnreadCountMutation({
			id,
			unread,
		}) {
			Vue.set(this.mailboxes[id], 'unread', unread ?? 0)
		},
		setScheduledSendingDisabledMutation(value) {
			this.isScheduledSendingDisabled = value
		},
		setSnoozeDisabledMutation(value) {
			this.isSnoozeDisabled = value
		},
		setActiveSieveScriptMutation({
			accountId,
			scriptData,
		}) {
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
			Vue.set(this, 'list', list)
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
		setMyTextBlocks(textBlocks) {
			this.myTextBlocks = textBlocks
			this.textBlocksFetched = true
		},
		setSharedTextBlocks(textBlocks) {
			this.sharedTextBlocks = textBlocks
			this.textBlocksFetched = true
		},
		addTextBlock(textBlock) {
			this.myTextBlocks.push(textBlock)
		},
		deleteTextBlockLocally(id) {
			const index = this.myTextBlocks.findIndex(textBlock => textBlock.id === id)
			this.myTextBlocks.splice(index, 1)
		},
		patchTextBlockLocally(textBlock) {
			const index = this.myTextBlocks.findIndex(s => s.id === textBlock.id)
			if (index !== -1) {
				Vue.set(this.myTextBlocks, index, textBlock)
			}
		},
		setQuickActions(quickActions) {
			this.quickActions = quickActions
		},
		patchQuickActionLocally(quickAction) {
			const index = this.quickActions.findIndex(s => s.id === quickAction.id)
			if (index !== -1) {
				Vue.set(this.quickActions, index, quickAction)
			}
		},
		deleteQuickActionLocally(id) {
			const index = this.quickActions.findIndex(s => s.id === id)
			if (index !== -1) {
				this.quickActions.splice(index, 1)
			}
		},
		addQuickActionLocally(quickAction) {
			this.quickActions.push(quickAction)
		},
		getPreference(key, def) {
			return defaultTo(def, this.preferences[key])
		},
		getAccount(id) {
			return this.accountsUnmapped[id]
		},
		getMailbox(id) {
			return this.mailboxes[id]
		},
		getMailboxes(accountId) {
			return this.accountsUnmapped[accountId].mailboxes.map((id) => this.mailboxes[id])
		},
		* getRecursiveMailboxIterator(accountId) {
			for (const mailbox of this.getMailboxes(accountId)) {
				yield mailbox

				for (const subMailboxId of mailbox.mailboxes) {
					yield this.getMailbox(subMailboxId)
				}
			}
		},
		getSubMailboxes(id) {
			const mailbox = this.getMailbox(id)
			return mailbox.mailboxes.map((id) => this.mailboxes[id])
		},
		getParentMailbox(id) {
			for (const mailbox of this.getMailboxes(this.getMailbox(id).accountId)) {
				if (mailbox.mailboxes.includes(id)) {
					return mailbox
				}
			}
			return undefined
		},
		getUnifiedMailbox(specialRole) {
			return head(
				this.accountsUnmapped[UNIFIED_ACCOUNT_ID].mailboxes
					.map((id) => this.mailboxes[id])
					.filter((mailbox) => mailbox.specialRole === specialRole),
			)
		},
		getEnvelope(id) {
			return this.envelopes[id]
		},
		getEnvelopes(mailboxId, query) {
			const list = this.getMailbox(mailboxId).envelopeLists[normalizedEnvelopeListId(query)] || []
			return list.map((msgId) => this.envelopes[msgId])
		},
		getEnvelopesByThreadRootId(accountId, threadRootId) {
			return sortBy(
				prop('dateInt'),
				Object.values(this.envelopes).filter(envelope => envelope.accountId === accountId && envelope.threadRootId === threadRootId),
			)
		},
		getMessage(id) {
			return this.messages[id]
		},
		getEnvelopeThread(id) {
			console.debug('get thread for envelope', id, this.envelopes[id], this.envelopes)
			const thread = this.envelopes[id]?.thread ?? []
			const envelopes = thread.map(id => this.envelopes[id])
			return sortBy(prop('dateInt'), envelopes)
		},
		getEnvelopeTags(id) {
			const tags = this.envelopes[id]?.tags ?? []
			return tags.map((tagId) => this.tags[tagId])
		},
		getTag(id) {
			return this.tags[id]
		},
		isInternalAddress(address) {
			const domain = address.split('@')[1]
			return this.internalAddress.some((internalAddress) => internalAddress.address === address || internalAddress.address === domain)
		},
		getActiveSieveScript(accountId) {
			return this.sieveScript[accountId]
		},
		getSmimeCertificate(id) {
			return this.smimeCertificates.find((cert) => cert.id === id)
		},
		getSmimeCertificateByEmail(email) {
			return this.smimeCertificates.find((cert) => cert.emailAddress === email)
		},
		findMailboxBySpecialRole(accountId, specialRole) {
			return this.getMailboxes(accountId).find(mailbox => mailbox.specialRole === specialRole)
		},
		findMailboxByName(accountId, name) {
			return this.getMailboxes(accountId).find(mailbox => mailbox.name === name)
		},
		getInbox(accountId) {
			return this.findMailboxBySpecialRole(accountId, 'inbox')
		},
		showSettingsForAccount(accountId) {
			return this.showAccountSettings === accountId
		},
		getMyTextBlocks() {
			return this.myTextBlocks
		},
		getSharedTextBlocks() {
			return this.sharedTextBlocks
		},
		areTextBlocksFetched() {
			return this.textBlocksFetched
		},
		getQuickActions() {
			return this.quickActions
		},
	}
}
