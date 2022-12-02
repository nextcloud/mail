/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
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
 *
 */

import flatMapDeep from 'lodash/fp/flatMapDeep'
import orderBy from 'lodash/fp/orderBy'
import {
	andThen,
	complement,
	curry,
	filter,
	flatten,
	gt,
	head,
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

import { savePreference } from '../service/PreferenceService'
import {
	create as createAccount,
	deleteAccount,
	fetch as fetchAccount,
	fetchAll as fetchAllAccounts,
	patch as patchAccount,
	update as updateAccount,
	updateSignature,
} from '../service/AccountService'
import {
	create as createMailbox,
	clearMailbox,
	deleteMailbox,
	fetchAll as fetchAllMailboxes,
	markMailboxRead,
	patchMailbox,
} from '../service/MailboxService'
import {
	createEnvelopeTag,
	deleteMessage,
	fetchEnvelope,
	fetchEnvelopes,
	fetchMessage,
	fetchMessageItineraries,
	fetchThread,
	moveMessage,
	removeEnvelopeTag,
	setEnvelopeFlag,
	setEnvelopeTag,
	syncEnvelopes,
	updateEnvelopeTag,
} from '../service/MessageService'
import * as AliasService from '../service/AliasService'
import logger from '../logger'
import { normalizedEnvelopeListId } from './normalization'
import { showNewMessagesNotification } from '../service/NotificationService'
import { matchError } from '../errors/match'
import SyncIncompleteError from '../errors/SyncIncompleteError'
import MailboxLockedError from '../errors/MailboxLockedError'
import { wait } from '../util/wait'
import {
	getActiveScript,
	updateAccount as updateSieveAccount,
	updateActiveScript,
} from '../service/SieveService'
import { PAGE_SIZE, UNIFIED_INBOX_ID } from './constants'
import * as ThreadService from '../service/ThreadService'
import {
	getPrioritySearchQueries,
	priorityImportantQuery,
	priorityOtherQuery,
} from '../util/priorityInbox'
import { html, plain, toPlain } from '../util/text'
import Axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { handleHttpAuthErrors } from '../http/sessionExpiryHandler'
import { showWarning } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import {
	buildForwardSubject,
	buildRecipients as buildReplyRecipients,
	buildReplySubject,
} from '../ReplyBuilder'
import DOMPurify from 'dompurify'
import {
	getCurrentUserPrincipal,
	initializeClientForUserView,
	findAll,
} from '../service/caldavService'

const sliceToPage = slice(0, PAGE_SIZE)

const findIndividualMailboxes = curry((getMailboxes, specialRole) =>
	pipe(
		filter(complement(prop('isUnified'))),
		map(prop('id')),
		map(getMailboxes),
		flatten,
		filter(propEq('specialRole', specialRole))
	)
)

const combineEnvelopeLists = pipe(flatten, orderBy(prop('dateInt'), 'desc'))

export default {
	savePreference({ commit, getters }, { key, value }) {
		return handleHttpAuthErrors(commit, async () => {
			const newValue = await savePreference(key, value)
			commit('savePreference', {
				key,
				value: newValue,
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
				})
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
						})
					)
				)
			}

			await markMailboxRead(mailboxId)
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
	async showMessageComposer({ commit, dispatch, getters }, { type = 'imap', data = {}, reply, forwardedMessages = [], templateMessageId }) {
		return handleHttpAuthErrors(commit, async () => {
			if (reply) {
				const original = await dispatch('fetchMessage', reply.data.databaseId)

				// Fetch and transform the body into a rich text object
				if (original.hasHtmlBody) {
					const resp = await Axios.get(
						generateUrl('/apps/mail/api/messages/{id}/html?plain=true', {
							id: original.databaseId,
						})
					)

					resp.data = DOMPurify.sanitize(resp.data, {
						FORBID_TAGS: ['style'],
					})

					data.body = html(resp.data)
				} else {
					data.body = plain(original.body)
				}

				if (reply.mode === 'reply') {
					logger.debug('Show simple reply composer', { reply })
					commit('showMessageComposer', {
						data: {
							accountId: reply.data.accountId,
							to: reply.data.from,
							cc: [],
							subject: buildReplySubject(reply.data.subject),
							body: data.body,
							originalBody: data.body,
							replyTo: reply.data,
						},
					})
					return
				} else if (reply.mode === 'replyAll') {
					logger.debug('Show reply all reply composer', { reply })
					const account = getters.getAccount(reply.data.accountId)
					const recipients = buildReplyRecipients(reply.data, {
						email: account.emailAddress,
						label: account.name,
					})
					commit('showMessageComposer', {
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
					commit('showMessageComposer', {
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
						})
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
				await dispatch('outbox/stopMessage', { message })
			}

			commit('showMessageComposer', {
				type,
				data,
				forwardedMessages,
				templateMessageId,
				originalSendAt,
			})
		})
	},
	async closeMessageComposer({ commit, dispatch, getters }, { restoreOriginalSendAt }) {
		return handleHttpAuthErrors(commit, async () => {
			// Restore original sendAt timestamp when requested
			const message = getters.composerMessage
			if (restoreOriginalSendAt && message.type === 'outbox' && message.options?.originalSendAt) {
				const body = message.data.body
				await dispatch('outbox/updateMessage', {
					id: message.data.id,
					message: {
						...message.data,
						body: message.data.isHtml ? body.value : toPlain(body).value,
						sendAt: message.options.originalSendAt,
					},
				})
			}

			commit('hideMessageComposer')
		})
	},
	async fetchEnvelope({ commit, getters }, id) {
		return handleHttpAuthErrors(commit, async () => {
			const cached = getters.getEnvelope(id)
			if (cached) {
				logger.debug(`using cached value for envelope ${id}`)
				return cached
			}

			const envelope = await fetchEnvelope(id)
			// Only commit if not undefined (not found)
			if (envelope) {
				commit('addEnvelope', {
					envelope,
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
						})
					),
					Promise.all.bind(Promise),
					andThen(map(sliceToPage))
				)
				const fetchUnifiedEnvelopes = pipe(
					findIndividualMailboxes(getters.getMailboxes, mailbox.specialRole),
					fetchIndividualLists,
					andThen(combineEnvelopeLists),
					andThen(sliceToPage),
					andThen(
						tap(
							map((envelope) =>
								commit('addEnvelope', {
									envelope,
									query,
								})
							)
						)
					)
				)

				return fetchUnifiedEnvelopes(getters.accounts)
			}

			return pipe(
				fetchEnvelopes,
				andThen(
					tap(
						map((envelope) =>
							commit('addEnvelope', {
								query,
								envelope,
								addToUnifiedMailboxes,
							})
						)
					)
				)
			)(mailbox.accountId, mailboxId, query, undefined, PAGE_SIZE)
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
					prop('dateInt', last(getters.getEnvelopes(m.databaseId, query)))
				)
				const cursor = individualCursor(query, mailbox)

				if (cursor === undefined) {
					throw new Error('Unified list has no tail')
				}
				const nextLocalUnifiedEnvelopes = pipe(
					findIndividualMailboxes(getters.getMailboxes, mailbox.specialRole),
					map(getIndivisualLists(query)),
					combineEnvelopeLists,
					filter(
						where({
							dateInt: gt(cursor),
						})
					),
					slice(0, quantity)
				)
				// We know the next envelopes based on local data
				// We have to fetch individual envelopes only if it ends in the known
				// next fetch. If it ended before, there is no data to fetch anyway. If
				// it ends after, we have all the relevant data already
				const needsFetch = curry((query, nextEnvelopes, mb) => {
					const c = individualCursor(query, mb)
					return nextEnvelopes.length < quantity || c >= head(nextEnvelopes).dateInt || c <= last(nextEnvelopes).dateInt
				})

				const mailboxesToFetch = (accounts) =>
					pipe(
						findIndividualMailboxes(getters.getMailboxes, mailbox.specialRole),
						filter(needsFetch(query, nextLocalUnifiedEnvelopes(accounts)))
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
							})
						),
						Promise.all.bind(Promise),
						andThen(() =>
							dispatch('fetchNextEnvelopes', {
								mailboxId,
								query,
								quantity,
								rec: false,
								addToUnifiedMailboxes: false,
							})
						)
					)(mbs)
				}

				const envelopes = nextLocalUnifiedEnvelopes(getters.accounts)
				logger.debug('next unified page can be built locally and consists of ' + envelopes.length + ' envelopes', { addToUnifiedMailboxes })
				envelopes.map((envelope) =>
					commit('addEnvelope', {
						query,
						envelope,
					})
				)
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

			return fetchEnvelopes(mailbox.accountId, mailboxId, query, lastEnvelope.dateInt, quantity).then((envelopes) => {
				logger.debug(`fetched ${envelopes.length} messages for mailbox ${mailboxId}`, {
					envelopes,
					addToUnifiedMailboxes,
				})
				envelopes.forEach((envelope) =>
					commit('addEnvelope', {
						query,
						envelope,
						addToUnifiedMailboxes,
					})
				)
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
										})
									)
							)
						)
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
												})
											)
									)
								)
						)
					})
				)
			}

			const ids = getters.getEnvelopes(mailboxId, query).map((env) => env.databaseId)
			logger.debug(`mailbox sync of ${mailboxId} (${query}) has ${ids.length} known IDs`)
			return syncEnvelopes(mailbox.accountId, mailboxId, ids, query, init)
				.then((syncData) => {
					logger.debug(`mailbox ${mailboxId} (${query}) synchronized, ${syncData.newMessages.length} new, ${syncData.changedMessages.length} changed and ${syncData.vanishedMessages.length} vanished messages`)

					const unifiedMailbox = getters.getUnifiedMailbox(mailbox.specialRole)

					syncData.newMessages.forEach((envelope) => {
						commit('addEnvelope', {
							envelope,
							query,
						})
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
							})
						)
					})
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
				await setEnvelopeFlag(envelope.databaseId, 'flagged', !oldState)
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
				await setEnvelopeFlag(envelope.databaseId, 'seen', newState)
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
	async toggleEnvelopeJunk({ commit, getters }, envelope) {
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

			try {
				await setEnvelopeFlag(envelope.databaseId, '$junk', !oldState)
				await setEnvelopeFlag(envelope.databaseId, '$notjunk', oldState)
			} catch (error) {
				console.error('could not toggle message junk state', error)

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
				await setEnvelopeFlag(envelope.databaseId, 'flagged', favFlag)
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
					commit('addEnvelope', { envelope })
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
	async updateAlias({ commit }, { account, aliasId, alias, name }) {
		return handleHttpAuthErrors(commit, async () => {
			const entity = await AliasService.updateAlias(account.id, aliasId, alias, name)
			commit('patchAlias', {
				account,
				aliasId: entity.id,
				data: { alias: entity.alias, name: entity.name },
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
	async deleteThread({ getters, commit }, { envelope }) {
		return handleHttpAuthErrors(commit, async () => {
			commit('removeEnvelope', { id: envelope.databaseId })

			try {
				await ThreadService.deleteThread(envelope.databaseId)
				console.debug('thread removed')
			} catch (e) {
				commit('addEnvelope', envelope)
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
				commit('addEnvelope', envelope)
				console.error('could not move thread', e)
				throw e
			}
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
			const { calendars } = await findAll()
			for (const calendar of calendars) {
				commit('addCalendar', { calendar })
			}
		})
	},
}
