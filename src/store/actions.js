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
	where,
} from 'ramda'

import { savePreference } from '../service/PreferenceService.js'
import {
	create as createAccount,
	deleteAccount,
	fetch as fetchAccount,
	fetchAll as fetchAllAccounts,
	patch as patchAccount,
	update as updateAccount,
	updateSignature,
	updateSmimeCertificate as updateAccountSmimeCertificate,
} from '../service/AccountService.js'
import {
	create as createMailbox,
	clearMailbox,
	deleteMailbox,
	fetchAll as fetchAllMailboxes,
	markMailboxRead,
	patchMailbox,
} from '../service/MailboxService.js'
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
	syncEnvelopes,
	unSnoozeMessage,
	updateEnvelopeTag,
	deleteTag,
} from '../service/MessageService.js'
import { moveDraft, updateDraft } from '../service/DraftService.js'
import * as AliasService from '../service/AliasService.js'
import logger from '../logger.js'
import { normalizedEnvelopeListId } from './normalization.js'
import { showNewMessagesNotification } from '../service/NotificationService.js'
import { matchError } from '../errors/match.js'
import SyncIncompleteError from '../errors/SyncIncompleteError.js'
import MailboxLockedError from '../errors/MailboxLockedError.js'
import { wait } from '../util/wait.js'
import {
	getActiveScript,
	updateAccount as updateSieveAccount,
	updateActiveScript,
} from '../service/SieveService.js'
import { FOLLOW_UP_TAG_LABEL, PAGE_SIZE, UNIFIED_INBOX_ID } from './constants.js'
import * as ThreadService from '../service/ThreadService.js'
import {
	getPrioritySearchQueries,
	priorityImportantQuery,
	priorityOtherQuery,
} from '../util/priorityInbox.js'
import { html, plain, toPlain } from '../util/text.js'
import Axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { handleHttpAuthErrors } from '../http/sessionExpiryHandler.js'
import { showError, showWarning } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import {
	buildForwardSubject,
	buildRecipients as buildReplyRecipients,
	buildReplySubject,
} from '../ReplyBuilder.js'
import DOMPurify from 'dompurify'
import {
	getCurrentUserPrincipal,
	initializeClientForUserView,
	findAll,
} from '../service/caldavService.js'
import * as SmimeCertificateService from '../service/SmimeCertificateService.js'
import useOutboxStore from './outboxStore.js'
import * as FollowUpService from '../service/FollowUpService.js'
import { addInternalAddress, removeInternalAddress } from '../service/InternalAddressService.js'

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

export default {
	savePreference({ commit, getters }, { key, value }) {
		return handleHttpAuthErrors(commit, async () => {
			const newValue = await savePreference(key, value)
			commit('savePreference', {
				key,
				value: newValue.value,
			})
		})
	},
	async fetchAccounts({ commit, getters }) {
		return handleHttpAuthErrors(commit, async () => {
			const accounts = await fetchAllAccounts()
			accounts.forEach((account) => commit('addAccount', account))
			return getters.accounts
		})
	},
	async fetchAccount({ commit }, id) {
		return handleHttpAuthErrors(commit, async () => {
			const account = await fetchAccount(id)
			commit('addAccount', account)
			return account
		})
	},
	async startAccountSetup({ commit }, config) {
		const account = await createAccount(config)
		logger.debug(`account ${account.id} created`, { account })
		return account
	},
	async finishAccountSetup({ commit }, { account }) {
		logger.debug(`Fetching mailboxes for account ${account.id},  …`, { account })
		account.mailboxes = await fetchAllMailboxes(account.id)
		commit('addAccount', account)
		logger.debug('New account mailboxes fetched', { account, mailboxes: account.mailboxes })
		return account
	},
	async updateAccount({ commit }, config) {
		return handleHttpAuthErrors(commit, async () => {
			const account = await updateAccount(config)
			logger.debug('account updated', { account })
			commit('editAccount', account)
			return account
		})
	},
	async patchAccount({ commit }, { account, data }) {
		return handleHttpAuthErrors(commit, async () => {
			const patchedAccount = await patchAccount(account, data)
			logger.debug('account patched', { account: patchedAccount, data })
			commit('patchAccount', { account, data })
			return account
		})
	},
	async updateAccountSignature({ commit }, { account, signature }) {
		return handleHttpAuthErrors(commit, async () => {
			await updateSignature(account, signature)
			logger.debug('account signature updated', { account, signature })
			const updated = Object.assign({}, account, { signature })
			commit('editAccount', updated)
			return account
		})
	},
	async setAccountSetting({ commit, getters }, { accountId, key, value }) {
		return handleHttpAuthErrors(commit, async () => {
			commit('setAccountSetting', { accountId, key, value })
			return await savePreference('account-settings', JSON.stringify(getters.getAllAccountSettings))
		})
	},
	async deleteAccount({ commit }, account) {
		return handleHttpAuthErrors(commit, async () => {
			try {
				await deleteAccount(account.id)
			} catch (error) {
				logger.error('could not delete account', { error })
				throw error
			}
		})
	},
	async deleteMailbox({ commit }, { mailbox }) {
		return handleHttpAuthErrors(commit, async () => {
			await deleteMailbox(mailbox.databaseId)
			commit('removeMailbox', { id: mailbox.databaseId })
		})
	},
	async clearMailbox({ commit }, { mailbox }) {
		return handleHttpAuthErrors(commit, async () => {
			await clearMailbox(mailbox.databaseId)
			commit('removeEnvelopes', { id: mailbox.databaseId })
			commit('setMailboxUnreadCount', { id: mailbox.databaseId })
		})
	},
	async createMailbox({ commit }, { account, name }) {
		return handleHttpAuthErrors(commit, async () => {
			const prefixed = (account.personalNamespace && !name.startsWith(account.personalNamespace))
				? account.personalNamespace + name
				: name
			const mailbox = await createMailbox(account.id, prefixed)
			console.debug(`mailbox ${prefixed} created for account ${account.id}`, { mailbox })
			commit('addMailbox', { account, mailbox })
			commit('expandAccount', account.id)
			commit('setAccountSetting', {
				accountId: account.id,
				key: 'collapsed',
				value: false,
			})
			return mailbox
		})
	},
	async moveAccount({ commit, getters }, { account, up }) {
		return handleHttpAuthErrors(commit, async () => {
			const accounts = getters.accounts
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
					commit('saveAccountsOrder', { account, order: idx })
					return patchAccount(account, { order: idx })
				}),
			)
		})
	},
	async markMailboxRead({ commit, getters, dispatch }, { accountId, mailboxId }) {
		return handleHttpAuthErrors(commit, async () => {
			const mailbox = getters.getMailbox(mailboxId)

			if (mailbox.isUnified) {
				const findIndividual = findIndividualMailboxes(getters.getMailboxes, mailbox.specialRole)
				const individualMailboxes = findIndividual(getters.accounts)
				return Promise.all(
					individualMailboxes.map((mb) =>
						dispatch('markMailboxRead', {
							accountId: mb.accountId,
							mailboxId: mb.databaseId,
						}),
					),
				)
			}

			const updated = Object.assign({}, mailbox)
			updated.unread = 0

			await markMailboxRead(mailboxId)
			commit('updateMailbox', {
				mailbox: updated,
			})

			dispatch('syncEnvelopes', {
				accountId,
				mailboxId,
			})
		})
	},
	async changeMailboxSubscription({ commit }, { mailbox, subscribed }) {
		return handleHttpAuthErrors(commit, async () => {
			logger.debug(`toggle subscription for mailbox ${mailbox.databaseId}`, {
				mailbox,
				subscribed,
			})
			const updated = await patchMailbox(mailbox.databaseId, { subscribed })

			commit('updateMailbox', {
				mailbox: updated,
			})
			logger.debug(`subscription for mailbox ${mailbox.databaseId} updated`, {
				mailbox,
				updated,
			})
		})
	},
	async patchMailbox({ commit }, { mailbox, attributes }) {
		return handleHttpAuthErrors(commit, async () => {
			logger.debug('patching mailbox', {
				mailbox,
				attributes,
			})

			const updated = await patchMailbox(mailbox.databaseId, attributes)

			commit('updateMailbox', {
				mailbox: updated,
			})
			logger.debug(`mailbox ${mailbox.databaseId} patched`, {
				mailbox,
				updated,
			})
		})
	},
	async startComposerSession({ dispatch, commit, getters }, {
		type = 'imap',
		data = {},
		reply,
		forwardedMessages = [],
		templateMessageId,
		isBlankMessage = false,
	}) {
		// Silently close old session if already saved and show a discard modal otherwise
		if (getters.composerSessionId && !getters.composerMessageIsSaved) {
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
				commit('showMessageComposer')
				return
			}
		}

		return handleHttpAuthErrors(commit, async () => {
			if (reply) {
				const original = await dispatch('fetchMessage', reply.data.databaseId)

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

					data.body = html(resp.data)
					if (reply.suggestedReply) {
						data.body.value = `<p>${reply.suggestedReply}<\\p>` + data.body.value
					}
				} else {
					data.body = plain(original.body)
					if (reply.suggestedReply) {
						data.body.value = `${reply.suggestedReply}\n` + data.body.value
					}
				}

				if (reply.mode === 'reply') {
					logger.debug('Show simple reply composer', { reply })
					let to = original.replyTo !== undefined ? original.replyTo : reply.data.from
					if (reply.followUp) {
						to = reply.data.to
					}
					commit('startComposerSession', {
						data: {
							accountId: reply.data.accountId,
							to,
							cc: [],
							subject: buildReplySubject(reply.data.subject),
							body: data.body,
							originalBody: data.body,
							replyTo: reply.data,
							smartReply: reply.smartReply,
						},
					})
					return
				} else if (reply.mode === 'replyAll') {
					logger.debug('Show reply all reply composer', { reply })
					const account = getters.getAccount(reply.data.accountId)
					const recipients = buildReplyRecipients(reply.data, {
						email: account.emailAddress,
						label: account.name,
					}, original.replyTo)
					commit('startComposerSession', {
						data: {
							accountId: reply.data.accountId,
							to: recipients.to,
							cc: recipients.cc,
							subject: buildReplySubject(reply.data.subject),
							body: data.body,
							originalBody: data.body,
							replyTo: reply.data,
						},
					})
					return
				} else if (reply.mode === 'forward') {
					logger.debug('Show forward composer', { reply })
					commit('startComposerSession', {
						data: {
							accountId: reply.data.accountId,
							to: [],
							cc: [],
							subject: buildForwardSubject(reply.data.subject),
							body: data.body,
							originalBody: data.body,
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
				const message = await dispatch('fetchMessage', templateMessageId)
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

					data.body = html(resp.data)
				} else {
					data.body = plain(message.body)
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
				const message = {
					...data,
					body: data.isHtml ? data.body.value : toPlain(data.body).value,
				}
				const outboxStore = useOutboxStore()
				await outboxStore.stopMessage({ message })
			}

			commit('startComposerSession', {
				type,
				data,
				forwardedMessages,
				templateMessageId,
				originalSendAt,
			})

			// Blank messages can be safely discarded (without saving a draft) until changes are made
			if (isBlankMessage) {
				commit('setComposerMessageSaved', true)
			}
		})
	},
	async stopComposerSession({ commit, dispatch, getters }, { restoreOriginalSendAt = false, moveToImap = false, id } = {}) {
		return handleHttpAuthErrors(commit, async () => {

			// Restore original sendAt timestamp when requested
			const message = getters.composerMessage
			if (restoreOriginalSendAt && message.type === 'outbox' && message.options?.originalSendAt) {
				const body = message.data.body
				message.body = message.data.isHtml ? body.value : toPlain(body).value
				message.sendAt = message.options.originalSendAt
				updateDraft(message)
			}
			if (moveToImap) {
				 await moveDraft(id)
			}

			commit('stopComposerSession')
		})
	},
	showMessageComposer({ commit }) {
		commit('showMessageComposer')
	},
	closeMessageComposer({ commit }) {
		commit('hideMessageComposer')
	},
	patchComposerData({ commit }, data) {
		commit('patchComposerData', data)
		commit('setComposerMessageSaved', false)
	},
	async fetchEnvelope({ commit, getters }, { accountId, id }) {
		return handleHttpAuthErrors(commit, async () => {
			const cached = getters.getEnvelope(id)
			if (cached) {
				logger.debug(`using cached value for envelope ${id}`)
				return cached
			}

			const envelope = await fetchEnvelope(accountId, id)
			// Only commit if not undefined (not found)
			if (envelope) {
				commit('addEnvelopes', {
					envelopes: [envelope],
				})
			}

			// Always use the object from the store
			return getters.getEnvelope(id)
		})
	},
	fetchEnvelopes({ state, commit, getters, dispatch }, { mailboxId, query, addToUnifiedMailboxes = true }) {
		return handleHttpAuthErrors(commit, async () => {
			const mailbox = getters.getMailbox(mailboxId)

			if (mailbox.isUnified) {
				const fetchIndividualLists = pipe(
					map((mb) =>
						dispatch('fetchEnvelopes', {
							mailboxId: mb.databaseId,
							query,
							addToUnifiedMailboxes: false,
						}),
					),
					Promise.all.bind(Promise),
					andThen(map(sliceToPage)),
				)
				const fetchUnifiedEnvelopes = pipe(
					findIndividualMailboxes(getters.getMailboxes, mailbox.specialRole),
					fetchIndividualLists,
					andThen(combineEnvelopeLists(getters.getPreference('sort-order'))),
					andThen(sliceToPage),
					andThen(
						tap((envelopes) =>
							commit('addEnvelopes', {
								envelopes,
								query,
							}),
						),
					),
				)

				return fetchUnifiedEnvelopes(getters.accounts)
			}

			return pipe(
				fetchEnvelopes,
				andThen(
					tap((envelopes) =>
						commit('addEnvelopes', {
							query,
							envelopes,
							addToUnifiedMailboxes,
						}),
					),
				),
			)(mailbox.accountId, mailboxId, query, undefined, PAGE_SIZE, getters.getPreference('sort-order'))
		})
	},
	async fetchNextEnvelopePage({ commit, getters, dispatch }, { mailboxId, query }) {
		return handleHttpAuthErrors(commit, async () => {
			const envelopes = await dispatch('fetchNextEnvelopes', {
				mailboxId,
				query,
				quantity: PAGE_SIZE,
			})
			return envelopes
		})
	},
	async fetchNextEnvelopes({ commit, getters, dispatch }, { mailboxId, query, quantity, rec = true, addToUnifiedMailboxes = true }) {
		return handleHttpAuthErrors(commit, async () => {
			const mailbox = getters.getMailbox(mailboxId)

			if (mailbox.isUnified) {
				const getIndivisualLists = curry((query, m) => getters.getEnvelopes(m.databaseId, query))
				const individualCursor = curry((query, m) =>
					prop('dateInt', last(getters.getEnvelopes(m.databaseId, query))),
				)
				const cursor = individualCursor(query, mailbox)

				if (cursor === undefined) {
					throw new Error('Unified list has no tail')
				}
				const newestFirst = getters.getPreference('sort-order') === 'newest'
				const nextLocalUnifiedEnvelopes = pipe(
					findIndividualMailboxes(getters.getMailboxes, mailbox.specialRole),
					map(getIndivisualLists(query)),
					combineEnvelopeLists(getters.getPreference('sort-order')),
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

					if (getters.getPreference('sort-order') === 'newest') {
						return c >= last(nextEnvelopes).dateInt
					} else {
						return c <= last(nextEnvelopes).dateInt
					}
				})

				const mailboxesToFetch = (accounts) =>
					pipe(
						findIndividualMailboxes(getters.getMailboxes, mailbox.specialRole),
						tap(mbs => console.info('individual mailboxes', mbs)),
						filter(needsFetch(query, nextLocalUnifiedEnvelopes(accounts))),
					)(accounts)
				const mbs = mailboxesToFetch(getters.accounts)

				if (rec && mbs.length) {
					logger.debug('not enough local envelopes for the next unified page. ' + mbs.length + ' fetches required', {
						mailboxes: mbs.map(mb => mb.databaseId),
					})
					return pipe(
						map((mb) =>
							dispatch('fetchNextEnvelopes', {
								mailboxId: mb.databaseId,
								query,
								quantity,
								addToUnifiedMailboxes: false,
							}),
						),
						Promise.all.bind(Promise),
						andThen(() =>
							dispatch('fetchNextEnvelopes', {
								mailboxId,
								query,
								quantity,
								rec: false,
								addToUnifiedMailboxes: true,
							}),
						),
					)(mbs)
				}

				const envelopes = nextLocalUnifiedEnvelopes(getters.accounts)
				logger.debug('next unified page can be built locally and consists of ' + envelopes.length + ' envelopes', { addToUnifiedMailboxes })
				commit('addEnvelopes', {
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
			const lastEnvelope = getters.getEnvelope(lastEnvelopeId)
			if (typeof lastEnvelope === 'undefined') {
				return Promise.reject(new Error('Cannot find last envelope. Required for the mailbox cursor'))
			}

			return fetchEnvelopes(mailbox.accountId, mailboxId, query, lastEnvelope.dateInt, quantity, getters.getPreference('sort-order')).then((envelopes) => {
				logger.debug(`fetched ${envelopes.length} messages for mailbox ${mailboxId}`, {
					envelopes,
					addToUnifiedMailboxes,
				})
				commit('addEnvelopes', {
					query,
					envelopes,
					addToUnifiedMailboxes,
				})
				return envelopes
			})
		})
	},
	syncEnvelopes({ commit, getters, dispatch }, { mailboxId, query, init = false }) {
		return handleHttpAuthErrors(commit, async () => {
			logger.debug(`starting mailbox sync of ${mailboxId} (${query})`)

			const mailbox = getters.getMailbox(mailboxId)

			// Skip superfluous requests if using passwordless authentication. They will fail anyway.
			const passwordIsUnavailable = getters.getPreference('password-is-unavailable', false)
			const isDisabled = (account) => passwordIsUnavailable && !!account.provisioningId

			if (mailbox.isUnified) {
				return Promise.all(
					getters.accounts
						.filter((account) => !account.isUnified && !isDisabled(account))
						.map((account) =>
							Promise.all(
								getters
									.getMailboxes(account.id)
									.filter((mb) => mb.specialRole === mailbox.specialRole)
									.map((mailbox) =>
										dispatch('syncEnvelopes', {
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
							getters.accounts
								.filter((account) => !account.isUnified && !isDisabled(account))
								.map((account) =>
									Promise.all(
										getters
											.getMailboxes(account.id)
											.filter((mb) => mb.specialRole === mailbox.specialRole)
											.map((mailbox) =>
												dispatch('syncEnvelopes', {
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

			const ids = getters.getEnvelopes(mailboxId, query).map((env) => env.databaseId)
			const lastTimestamp = getters.getPreference('sort-order') === 'newest' ? null : getters.getEnvelopes(mailboxId, query)[0]?.dateInt
			logger.debug(`mailbox sync of ${mailboxId} (${query}) has ${ids.length} known IDs. ${lastTimestamp} is the last known message timestamp`, { mailbox })
			return syncEnvelopes(mailbox.accountId, mailboxId, ids, lastTimestamp, query, init, getters.getPreference('sort-order'))
				.then((syncData) => {
					logger.debug(`mailbox ${mailboxId} (${query}) synchronized, ${syncData.newMessages.length} new, ${syncData.changedMessages.length} changed and ${syncData.vanishedMessages.length} vanished messages`)

					const unifiedMailbox = getters.getUnifiedMailbox(mailbox.specialRole)

					commit('addEnvelopes', {
						envelopes: syncData.newMessages,
						query,
					})

					syncData.newMessages.forEach((envelope) => {
						if (unifiedMailbox) {
							commit('updateEnvelope', {
								envelope,
							})
						}
					})
					syncData.changedMessages.forEach((envelope) => {
						commit('updateEnvelope', {
							envelope,
						})
					})
					syncData.vanishedMessages.forEach((id) => {
						commit('removeEnvelope', {
							id,
						})
						// Already removed from unified inbox
					})

					commit('setMailboxUnreadCount', {
						id: mailboxId,
						unread: syncData.stats.unread,
					})

					return syncData.newMessages
				})
				.catch((error) => {
					return matchError(error, {
						[SyncIncompleteError.getName()]() {
							console.warn(`(initial) sync of mailbox ${mailboxId} (${query}) is incomplete, retriggering`)
							return dispatch('syncEnvelopes', {
								mailboxId,
								query,
								init,
							})
						},
						[MailboxLockedError.getName()](error) {
							if (init) {
								logger.info('Sync failed because the mailbox is locked, stopping here because this is an initial sync', { error })
								throw error
							}

							logger.info('Sync failed because the mailbox is locked, retriggering', { error })
							return wait(1500).then(() => dispatch('syncEnvelopes', {
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
	async syncInboxes({ commit, getters, dispatch }) {
		// Skip superfluous requests if using passwordless authentication. They will fail anyway.
		const passwordIsUnavailable = getters.getPreference('password-is-unavailable', false)
		const isDisabled = (account) => passwordIsUnavailable && !!account.provisioningId

		return handleHttpAuthErrors(commit, async () => {
			const results = await Promise.all(
				getters.accounts
					.filter((a) => !a.isUnified && !isDisabled(a))
					.map((account) => {
						return Promise.all(
							getters.getMailboxes(account.id).map(async (mailbox) => {
								if (mailbox.specialRole !== 'inbox') {
									return
								}

								const list = mailbox.envelopeLists[normalizedEnvelopeListId(undefined)]
								if (list === undefined) {
									await dispatch('fetchEnvelopes', {
										mailboxId: mailbox.databaseId,
									})
								}

								return await dispatch('syncEnvelopes', {
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
					const mailbox = getters.getMailbox(UNIFIED_INBOX_ID)
					const list = mailbox.envelopeLists[normalizedEnvelopeListId(query)]
					if (list === undefined) {
						await dispatch('fetchEnvelopes', {
							mailboxId: UNIFIED_INBOX_ID,
							query,
						})
					}

					await dispatch('syncEnvelopes', {
						mailboxId: UNIFIED_INBOX_ID,
						query,
					})
				}
			} finally {
				showNewMessagesNotification(newMessages)
			}
		})
	},
	toggleEnvelopeFlagged({ commit, getters }, envelope) {
		return handleHttpAuthErrors(commit, async () => {
			// Change immediately and switch back on error
			const oldState = envelope.flags.flagged
			commit('flagEnvelope', {
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
				commit('flagEnvelope', {
					envelope,
					flag: 'flagged',
					value: oldState,
				})

				throw error
			}
		})
	},
	async toggleEnvelopeImportant({ commit, dispatch, getters }, envelope) {
		return handleHttpAuthErrors(commit, async () => {
			const importantLabel = '$label1'
			const hasTag = getters
				.getEnvelopeTags(envelope.databaseId)
				.some((tag) => tag.imapLabel === importantLabel)
			if (hasTag) {
				await dispatch('removeEnvelopeTag', {
					envelope,
					imapLabel: importantLabel,
				})
			} else {
				await dispatch('addEnvelopeTag', {
					envelope,
					imapLabel: importantLabel,
				})
			}
		})
	},
	async toggleEnvelopeSeen({ commit, getters }, { envelope, seen }) {
		return handleHttpAuthErrors(commit, async () => {
			// Change immediately and switch back on error
			const oldState = envelope.flags.seen
			const newState = seen === undefined ? !oldState : seen
			commit('flagEnvelope', {
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
				commit('flagEnvelope', {
					envelope,
					flag: 'seen',
					value: oldState,
				})

				throw error
			}
		})
	},
	async toggleEnvelopeJunk({ commit, getters }, { envelope, removeEnvelope }) {
		return handleHttpAuthErrors(commit, async () => {
			// Change immediately and switch back on error
			const oldState = envelope.flags.$junk
			commit('flagEnvelope', {
				envelope,
				flag: '$junk',
				value: !oldState,
			})
			commit('flagEnvelope', {
				envelope,
				flag: '$notjunk',
				value: oldState,
			})

			if (removeEnvelope) {
				commit('removeEnvelope', { id: envelope.databaseId })
			}

			try {
				await setEnvelopeFlags(envelope.databaseId, {
					$junk: !oldState,
					$notjunk: oldState,
				})
			} catch (error) {
				console.error('could not toggle message junk state', error)

				if (removeEnvelope) {
					commit('addEnvelopes', [envelope])
				}

				// Revert change
				commit('flagEnvelope', {
					envelope,
					flag: '$junk',
					value: oldState,
				})
				commit('flagEnvelope', {
					envelope,
					flag: '$notjunk',
					value: !oldState,
				})

				throw error
			}
		})
	},
	async markEnvelopeFavoriteOrUnfavorite({ commit, getters }, { envelope, favFlag }) {
		return handleHttpAuthErrors(commit, async () => {
			// Change immediately and switch back on error
			const oldState = envelope.flags.flagged
			commit('flagEnvelope', {
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
				commit('flagEnvelope', {
					envelope,
					flag: 'flagged',
					value: oldState,
				})

				throw error
			}
		})
	},
	async markEnvelopeImportantOrUnimportant({ commit, dispatch, getters }, { envelope, addTag }) {
		return handleHttpAuthErrors(commit, async () => {
			const importantLabel = '$label1'
			const hasTag = getters
				.getEnvelopeTags(envelope.databaseId)
				.some((tag) => tag.imapLabel === importantLabel)
			if (hasTag && !addTag) {
				await dispatch('removeEnvelopeTag', {
					envelope,
					imapLabel: importantLabel,
				})
			} else if (!hasTag && addTag) {
				await dispatch('addEnvelopeTag', {
					envelope,
					imapLabel: importantLabel,
				})
			}
		})
	},
	async fetchThread({ getters, commit }, id) {
		return handleHttpAuthErrors(commit, async () => {
			const thread = await fetchThread(id)
			commit('addEnvelopeThread', {
				id,
				thread,
			})
			return thread
		})
	},
	async fetchMessage({ getters, commit }, id) {
		return handleHttpAuthErrors(commit, async () => {
			const message = await fetchMessage(id)
			// Only commit if not undefined (not found)
			if (message) {
				commit('addMessage', {
					message,
				})
			}
			return message
		})
	},
	async fetchItineraries({ commit }, id) {
		return handleHttpAuthErrors(commit, async () => {
			const itineraries = await fetchMessageItineraries(id)
			commit('addMessageItineraries', {
				id,
				itineraries,
			})
			return itineraries
		})
	},
	async fetchDkim({ commit }, id) {
		return handleHttpAuthErrors(commit, async () => {
			const result = await fetchMessageDkim(id)
			commit('addMessageDkim', {
				id,
				result,
			})
			return result
		})
	},
	async addInternalAddress({ commit }, { address, type }) {
		return handleHttpAuthErrors(commit, async () => {
			const internalAddress = await addInternalAddress(address, type)
			commit('addInternalAddress', internalAddress)
			console.debug('internal address added')
		})
	},
	async removeInternalAddress({ commit }, { id, address, type }) {
		return handleHttpAuthErrors(commit, async () => {
			try {
				await removeInternalAddress(address, type)
				commit('removeInternalAddress', { addressId: id })
				console.debug('internal address removed')
			} catch (error) {
				console.error('could not delete internal address', error)
				throw error
			}
		})
	},
	async deleteMessage({ getters, commit }, { id }) {
		return handleHttpAuthErrors(commit, async () => {
			commit('removeEnvelope', { id })

			try {
				await deleteMessage(id)
				commit('removeMessage', { id })
				console.debug('message removed')
			} catch (err) {
				console.error('could not delete message', err)
				const envelope = getters.getEnvelope(id)
				if (envelope) {
					commit('addEnvelopes', { envelopes: [envelope] })
				} else {
					logger.error('could not find envelope', { id })
				}
				throw err
			}
		})
	},
	async createAlias({ commit }, { account, alias, name }) {
		return handleHttpAuthErrors(commit, async () => {
			const entity = await AliasService.createAlias(account.id, alias, name)
			commit('createAlias', {
				account,
				alias: entity,
			})
		})
	},
	async deleteAlias({ commit }, { account, aliasId }) {
		return handleHttpAuthErrors(commit, async () => {
			const entity = await AliasService.deleteAlias(account.id, aliasId)
			commit('deleteAlias', {
				account,
				aliasId: entity.id,
			})
		})
	},
	async updateAlias({ commit }, { account, aliasId, alias, name, smimeCertificateId }) {
		return handleHttpAuthErrors(commit, async () => {
			const entity = await AliasService.updateAlias(
				account.id,
				aliasId,
				alias,
				name,
				smimeCertificateId,
			)
			commit('patchAlias', {
				account,
				aliasId: entity.id,
				data: {
					alias: entity.alias,
					name: entity.name,
					smimeCertificateId: entity.smimeCertificateId,
				},
			})
			commit('editAccount', account)
		})
	},
	async updateAliasSignature({ commit }, { account, aliasId, signature }) {
		return handleHttpAuthErrors(commit, async () => {
			const entity = await AliasService.updateSignature(account.id, aliasId, signature)
			commit('patchAlias', {
				account,
				aliasId: entity.id,
				data: { signature: entity.signature },
			})
			commit('editAccount', account)
		})
	},
	async renameMailbox({ commit }, { account, mailbox, newName }) {
		return handleHttpAuthErrors(commit, async () => {
			const newMailbox = await patchMailbox(mailbox.databaseId, {
				name: newName,
			})

			console.debug(`mailbox ${mailbox.databaseId} renamed to ${newName}`, { mailbox })
			commit('removeMailbox', { id: mailbox.databaseId })
			commit('addMailbox', { account, mailbox: newMailbox })
		})
	},
	async moveMessage({ commit }, { id, destMailboxId }) {
		return handleHttpAuthErrors(commit, async () => {
			await moveMessage(id, destMailboxId)
			commit('removeEnvelope', { id })
			commit('removeMessage', { id })
		})
	},
	async snoozeMessage({ commit }, { id, unixTimestamp, destMailboxId }) {
		return handleHttpAuthErrors(commit, async () => {
			await snoozeMessage(id, unixTimestamp, destMailboxId)
			commit('removeEnvelope', { id })
			commit('removeMessage', { id })
		})
	},
	async unSnoozeMessage({ commit }, { id }) {
		return handleHttpAuthErrors(commit, async () => {
			await unSnoozeMessage(id)
			commit('removeEnvelope', { id })
			commit('removeMessage', { id })
		})
	},
	async fetchActiveSieveScript({ commit }, { accountId }) {
		return handleHttpAuthErrors(commit, async () => {
			const scriptData = await getActiveScript(accountId)
			commit('setActiveSieveScript', { accountId, scriptData })
		})
	},
	async updateActiveSieveScript({ commit }, { accountId, scriptData }) {
		return handleHttpAuthErrors(commit, async () => {
			await updateActiveScript(accountId, scriptData)
			commit('setActiveSieveScript', { accountId, scriptData })
		})
	},
	async updateSieveAccount({ commit }, { account, data }) {
		return handleHttpAuthErrors(commit, async () => {
			logger.debug(`update sieve settings for account ${account.id}`)
			try {
				await updateSieveAccount(account.id, data)
				commit('patchAccount', { account, data })
			} catch (error) {
				logger.error('failed to update sieve account: ', { error })
				throw error
			}
		})
	},
	async createTag({ commit }, { displayName, color }) {
		return handleHttpAuthErrors(commit, async () => {
			const tag = await createEnvelopeTag(displayName, color)
			commit('addTag', { tag })
		})

	},
	async addEnvelopeTag({ commit, getters }, { envelope, imapLabel }) {
		return handleHttpAuthErrors(commit, async () => {
			// TODO: fetch tags indepently of envelopes and only send tag id here
			const tag = await setEnvelopeTag(envelope.databaseId, imapLabel)
			if (!getters.getTag(tag.id)) {
				commit('addTag', { tag })
			}

			commit('addEnvelopeTag', {
				envelope,
				tagId: tag.id,
			})
		})
	},
	async removeEnvelopeTag({ commit }, { envelope, imapLabel }) {
		return handleHttpAuthErrors(commit, async () => {
			const tag = await removeEnvelopeTag(envelope.databaseId, imapLabel)
			commit('removeEnvelopeTag', {
				envelope,
				tagId: tag.id,
			})
		})
	},
	async updateTag({ commit }, { tag, displayName, color }) {
		return handleHttpAuthErrors(commit, async () => {
			await updateEnvelopeTag(tag.id, displayName, color)
			commit('updateTag', { tag, displayName, color })
			logger.debug('tag updated', { tag, displayName, color })
		})
	},
	async deleteTag({ commit }, { tag, accountId }) {
		return handleHttpAuthErrors(commit, async () => {
			await deleteTag(tag.id, accountId)
			commit('deleteTag', { tagId: tag.id })
			logger.debug('tag deleted', { tag })
		})
	},
	async deleteThread({ getters, commit }, { envelope }) {
		return handleHttpAuthErrors(commit, async () => {
			commit('removeEnvelope', { id: envelope.databaseId })

			try {
				await ThreadService.deleteThread(envelope.databaseId)
				console.debug('thread removed')
			} catch (e) {
				commit('addEnvelopes', { envelopes: [envelope] })
				console.error('could not delete thread', e)
				throw e
			}
		})
	},
	async moveThread({ getters, commit }, { envelope, destMailboxId }) {
		return handleHttpAuthErrors(commit, async () => {
			commit('removeEnvelope', { id: envelope.databaseId })

			try {
				await ThreadService.moveThread(envelope.databaseId, destMailboxId)
				console.debug('thread removed')
			} catch (e) {
				commit('addEnvelopes', { envelopes: [envelope] })
				console.error('could not move thread', e)
				throw e
			}
		})
	},
	async snoozeThread({ getters, commit }, { envelope, unixTimestamp, destMailboxId }) {
		return handleHttpAuthErrors(commit, async () => {
			try {
				await ThreadService.snoozeThread(envelope.databaseId, unixTimestamp, destMailboxId)
				console.debug('thread snoozed')
			} catch (e) {
				commit('addEnvelopes', { envelopes: [envelope] })
				console.error('could not snooze thread', e)
				throw e
			}
			commit('removeEnvelope', { id: envelope.databaseId })
		})
	},
	async unSnoozeThread({ getters, commit }, { envelope }) {
		return handleHttpAuthErrors(commit, async () => {
			try {
				await ThreadService.unSnoozeThread(envelope.databaseId)
				console.debug('thread unSnoozed')
			} catch (e) {
				console.error('could not unsnooze thread', e)
				throw e
			}
			commit('removeEnvelope', { id: envelope.databaseId })
		})
	},

	/**
	 * Retrieve and commit the principal of the current user.
	 *
	 * @param {object} context Vuex store context
	 * @param {Function} context.commit Vuex store mutations
	 */
	async fetchCurrentUserPrincipal({ commit }) {
		return handleHttpAuthErrors(commit, async () => {
			await initializeClientForUserView()
			commit('setCurrentUserPrincipal', { currentUserPrincipal: getCurrentUserPrincipal() })
		})
	},

	/**
	 * Retrieve and commit calendars.
	 *
	 * @param {object} context Vuex store context
	 * @param {Function} context.commit Vuex store mutations
	 * @return {Promise<void>}
	 */
	async loadCollections({ commit }) {
		await handleHttpAuthErrors(commit, async () => {
			const { calendarGroups: { calendars }, addressBooks } = await findAll()
			for (const calendar of calendars) {
				commit('addCalendar', { calendar })
			}
			for (const addressBook of addressBooks) {
				commit('addAddressBook', { addressBook })
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
	async fetchSmimeCertificates({ commit }) {
		return handleHttpAuthErrors(commit, async () => {
			const certificates = await SmimeCertificateService.fetchAll()
			commit('setSmimeCertificates', certificates)
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
	async deleteSmimeCertificate({ commit }, id) {
		return handleHttpAuthErrors(commit, async () => {
			await SmimeCertificateService.deleteCertificate(id)
			commit('deleteSmimeCertificate', { id })
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
	async createSmimeCertificate({ commit }, files) {
		return handleHttpAuthErrors(commit, async () => {
			const certificate = await SmimeCertificateService.createCertificate(files)
			commit('addSmimeCertificate', { certificate })
			return certificate
		})
	},

	/**
	 * Update the S/MIME certificate of an account.
	 *
	 * @param {object} context Vuex store context
	 * @param {Function} context.commit Vuex store mutations
	 * @param {Function} context.getters Vuex store getters
	 * @param {object} data
	 * @param {object} data.accountId
	 * @param {number=} data.smimeCertificateId
	 * @param data.account
	 * @return {Promise<void>}
	 */
	async updateAccountSmimeCertificate({ commit, getters }, { account, smimeCertificateId }) {
		return handleHttpAuthErrors(commit, async () => {
			await updateAccountSmimeCertificate(account.id, smimeCertificateId)
			commit('patchAccount', { account, data: { smimeCertificateId } })
		})
	},

	/**
	 * Should the envelope moved to the junk (or back to inbox)
	 *
	 * @param {object} context Vuex store context
	 * @param {object} context.getters Vuex store getters
	 * @param {object} envelope envelope object@
	 * @return {boolean}
	 */
	async moveEnvelopeToJunk({ getters }, envelope) {
		const account = getters.getAccount(envelope.accountId)
		if (account.junkMailboxId === null) {
			return false
		}

		if (!envelope.flags.$junk) {
			// move message to junk
			return envelope.mailboxId !== account.junkMailboxId
		}

		const inbox = getters.getInbox(account.id)
		if (inbox === undefined) {
			return false
		}

		// move message to inbox
		return envelope.mailboxId !== inbox.databaseId
	},
	async createAndSetSnoozeMailbox({ getters, dispatch }, account) {
		const name = 'Snoozed'
		let snoozeMailboxId

		try {
			const createMailboxResponse = await dispatch('createMailbox', { account, name })
			snoozeMailboxId = createMailboxResponse.databaseId
			logger.info(`mailbox ${name} created as ${snoozeMailboxId}`)
		} catch (e) {
			logger.error('could not create mailbox', { e })
		}

		if (snoozeMailboxId === undefined) {
			snoozeMailboxId = getters.findMailboxByName(account.id, name).databaseId
		}

		if (snoozeMailboxId === undefined) {
			logger.error('Could not create snooze mailbox')
			showError(t('mail', 'Could not create snooze mailbox'))
			return
		}

		await dispatch('patchAccount', {
			account,
			data: {
				snoozeMailboxId,
			},
		})
	},
	async setLayout({ commit }, { list }) {
		try {
			commit('setOneLineLayout', {
				list,
			})
		} catch (error) {
			logger.error('Could not set layouts', { error })
		}
	},
	async clearFollowUpReminder({ commit, dispatch }, { envelope }) {
		await dispatch('removeEnvelopeTag', {
			envelope,
			imapLabel: FOLLOW_UP_TAG_LABEL,
		})
		commit('removeEnvelopeFromFollowUpMailbox', {
			id: envelope.databaseId,
		})
	},
	async checkFollowUpReminders({ dispatch, getters }) {
		const envelopes = getters.getFollowUpReminderEnvelopes
		const messageIds = envelopes.map((envelope) => envelope.databaseId)
		if (messageIds.length === 0) {
			return
		}

		const data = await FollowUpService.checkMessageIds(messageIds)
		for (const messageId of data.wasFollowedUp) {
			const envelope = getters.getEnvelope(messageId)
			if (!envelope) {
				continue
			}

			await dispatch('clearFollowUpReminder', { envelope })
		}
	},
}
