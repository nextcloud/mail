/**
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
	deleteMailbox,
	fetchAll as fetchAllMailboxes,
	markMailboxRead,
	patchMailbox,
} from '../service/MailboxService'
import {
	deleteMessage,
	fetchEnvelope,
	fetchEnvelopes,
	fetchMessage,
	setEnvelopeFlag,
	syncEnvelopes,
	fetchThread,
	moveMessage,
} from '../service/MessageService'
import { createAlias, deleteAlias } from '../service/AliasService'
import logger from '../logger'
import { normalizedEnvelopeListId } from './normalization'
import { showNewMessagesNotification } from '../service/NotificationService'
import { matchError } from '../errors/match'
import SyncIncompleteError from '../errors/SyncIncompleteError'
import MailboxLockedError from '../errors/MailboxLockedError'
import { wait } from '../util/wait'
import { UNIFIED_INBOX_ID } from './constants'

const PAGE_SIZE = 20

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
		return savePreference(key, value).then(({ value }) => {
			commit('savePreference', {
				key,
				value,
			})
		})
	},
	fetchAccounts({ commit, getters }) {
		return fetchAllAccounts().then((accounts) => {
			accounts.forEach((account) => commit('addAccount', account))
			return getters.accounts
		})
	},
	fetchAccount({ commit }, id) {
		return fetchAccount(id).then((account) => {
			commit('addAccount', account)
			return account
		})
	},
	createAccount({ commit }, config) {
		return createAccount(config).then((account) => {
			logger.debug(`account ${account.id} created, fetching mailboxes …`, account)
			return fetchAllMailboxes(account.id)
				.then((mailboxes) => {
					account.mailboxes = mailboxes
					commit('addAccount', account)
				})
				.then(() => console.info("new account's mailboxes fetched"))
				.then(() => account)
		})
	},
	updateAccount({ commit }, config) {
		return updateAccount(config).then((account) => {
			console.debug('account updated', account)
			commit('editAccount', account)
			return account
		})
	},
	patchAccount({ commit }, { account, data }) {
		return patchAccount(account, data).then((account) => {
			console.debug('account patched', account, data)
			commit('patchAccount', { account, data })
			return account
		})
	},
	updateAccountSignature({ commit }, { account, signature }) {
		return updateSignature(account, signature).then(() => {
			console.debug('account signature updated')
			const updated = Object.assign({}, account, { signature })
			commit('editAccount', updated)
			return account
		})
	},
	deleteAccount({ commit }, account) {
		return deleteAccount(account.id).catch((err) => {
			console.error('could not delete account', err)
			throw err
		})
	},
	async deleteMailbox({ commit }, { mailbox }) {
		await deleteMailbox(mailbox.databaseId)
		commit('removeMailbox', { id: mailbox.databaseId })
	},
	async createMailbox({ commit }, { account, name }) {
		const prefixed = (account.personalNamespace && !name.startsWith(account.personalNamespace))
			? account.personalNamespace + name
			: name
		const mailbox = await createMailbox(account.id, prefixed)
		console.debug(`mailbox ${prefixed} created for account ${account.id}`, { mailbox })
		commit('addMailbox', { account, mailbox })
		commit('expandAccount', account.id)
		return mailbox
	},
	moveAccount({ commit, getters }, { account, up }) {
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
		return Promise.all(
			accounts.map((account, idx) => {
				if (account.id === 0) {
					return
				}
				commit('saveAccountsOrder', { account, order: idx })
				return patchAccount(account, { order: idx })
			})
		)
	},
	markMailboxRead({ getters, dispatch }, { accountId, mailboxId }) {
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

		return markMailboxRead(mailboxId).then(
			dispatch('syncEnvelopes', {
				accountId,
				mailboxId,
			})
		)
	},
	async changeMailboxSubscription({ commit }, { mailbox, subscribed }) {
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
	},
	async patchMailbox({ commit }, { mailbox, attributes }) {
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
	},
	async fetchEnvelope({ commit, getters }, id) {
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
	},
	fetchEnvelopes({ state, commit, getters, dispatch }, { mailboxId, query }) {
		const mailbox = getters.getMailbox(mailboxId)

		if (mailbox.isUnified) {
			const fetchIndividualLists = pipe(
				map((mb) =>
					dispatch('fetchEnvelopes', {
						mailboxId: mb.databaseId,
						query,
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
						})
					)
				)
			)
		)(mailboxId, query, undefined, PAGE_SIZE)
	},
	fetchNextEnvelopePage({ commit, getters, dispatch }, { mailboxId, query, rec = true }) {
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
			const nextLocalUnifiedEnvelopePage = pipe(
				findIndividualMailboxes(getters.getMailboxes, mailbox.specialRole),
				map(getIndivisualLists(query)),
				combineEnvelopeLists,
				filter(
					where({
						dateInt: gt(cursor),
					})
				),
				sliceToPage
			)
			// We know the next page based on local data
			// We have to fetch individual pages only if the page ends in the known
			// next page. If it ended before, there is no data to fetch anyway. If
			// it ends after, we have all the relevant data already
			const needsFetch = curry((query, nextPage, f) => {
				const c = individualCursor(query, f)
				return nextPage.length < PAGE_SIZE || (c <= head(nextPage).dateInt && c >= last(nextPage).dateInt)
			})

			const mailboxesToFetch = (accounts) =>
				pipe(
					findIndividualMailboxes(getters.getMailboxes, mailbox.specialRole),
					filter(needsFetch(query, nextLocalUnifiedEnvelopePage(accounts)))
				)(accounts)
			const mbs = mailboxesToFetch(getters.accounts)

			if (rec && mbs.length) {
				return pipe(
					map((mb) =>
						dispatch('fetchNextEnvelopePage', {
							mailboxId: mb.databaseId,
							query,
						})
					),
					Promise.all.bind(Promise),
					andThen(() =>
						dispatch('fetchNextEnvelopePage', {
							mailboxId,
							query,
							rec: false,
						})
					)
				)(mbs)
			}

			const page = nextLocalUnifiedEnvelopePage(getters.accounts)
			page.map((envelope) =>
				commit('addEnvelope', {
					query,
					envelope,
				})
			)
			return page
		}

		const list = mailbox.envelopeLists[normalizedEnvelopeListId(query)]
		if (list === undefined) {
			console.warn("envelope list is not defined, can't fetch next page", mailboxId, query)
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

		return fetchEnvelopes(mailboxId, query, lastEnvelope.dateInt, PAGE_SIZE).then((envelopes) => {
			logger.debug(`fetched ${envelopes.length} messages for the next page of mailbox ${mailboxId}`, {
				envelopes,
			})
			envelopes.forEach((envelope) =>
				commit('addEnvelope', {
					query,
					envelope,
				})
			)
			return envelopes
		})
	},
	syncEnvelopes({ commit, getters, dispatch }, { mailboxId, query, init = false }) {
		const mailbox = getters.getMailbox(mailboxId)

		if (mailbox.isUnified) {
			return Promise.all(
				getters.accounts
					.filter((account) => !account.isUnified)
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
				getters.accounts
					.filter((account) => !account.isUnified)
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
		}

		const ids = getters.getEnvelopes(mailboxId, query).map((env) => env.databaseId)
		return syncEnvelopes(mailbox.accountId, mailboxId, ids, query, init)
			.then((syncData) => {
				logger.info(`mailbox ${mailboxId} synchronized, ${syncData.newMessages.length} new, ${syncData.changedMessages.length} changed and ${syncData.vanishedMessages.length} vanished messages`)

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

				return syncData.newMessages
			})
			.catch((error) => {
				return matchError(error, {
					[SyncIncompleteError.getName()]() {
						console.warn('(initial) sync is incomplete, retriggering')
						return dispatch('syncEnvelopes', { mailboxId, query, init })
					},
					[MailboxLockedError.getName()](error) {
						logger.info('Sync failed because the mailbox is locked, retriggering', { error })
						return wait(1500).then(() => dispatch('syncEnvelopes', { mailboxId, query, init }))
					},
					default(error) {
						console.error('Could not sync envelopes: ' + error.message, error)
					},
				})
			})
	},
	async syncInboxes({ getters, dispatch }) {
		const results = await Promise.all(
			getters.accounts
				.filter((a) => !a.isUnified)
				.map((account) => {
					return Promise.all(
						getters.getMailboxes(account.id).map(async(mailbox) => {
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
			for (const query of ['is:important not:starred', 'is:starred not:important', 'not:starred not:important']) {
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
	},
	toggleEnvelopeFlagged({ commit, getters }, envelope) {
		// Change immediately and switch back on error
		const oldState = envelope.flags.flagged
		commit('flagEnvelope', {
			envelope,
			flag: 'flagged',
			value: !oldState,
		})

		setEnvelopeFlag(envelope.databaseId, 'flagged', !oldState).catch((e) => {
			console.error('could not toggle message flagged state', e)

			// Revert change
			commit('flagEnvelope', {
				envelope,
				flag: 'flagged',
				value: oldState,
			})
		})
	},
	toggleEnvelopeImportant({ commit, getters }, envelope) {
		// Change immediately and switch back on error
		const oldState = envelope.flags.important
		commit('flagEnvelope', {
			envelope,
			flag: 'important',
			value: !oldState,
		})

		setEnvelopeFlag(envelope.databaseId, 'important', !oldState).catch((e) => {
			console.error('could not toggle message important state', e)

			// Revert change
			commit('flagEnvelope', {
				envelope,
				flag: 'important',
				value: oldState,
			})
		})
	},
	toggleEnvelopeSeen({ commit, getters }, { envelope, seen }) {
		// Change immediately and switch back on error
		const oldState = envelope.flags.seen
		const newState = seen === undefined ? !oldState : seen
		commit('flagEnvelope', {
			envelope,
			flag: 'seen',
			value: newState,
		})

		setEnvelopeFlag(envelope.databaseId, 'seen', newState).catch((e) => {
			console.error('could not toggle message seen state', e)

			// Revert change
			commit('flagEnvelope', {
				envelope,
				flag: 'seen',
				value: oldState,
			})
		})
	},
	toggleEnvelopeJunk({ commit, getters }, envelope) {
		// Change immediately and switch back on error
		const oldState = envelope.flags.junk
		commit('flagEnvelope', {
			envelope,
			flag: 'junk',
			value: !oldState,
		})

		setEnvelopeFlag(envelope.databaseId, 'junk', !oldState).catch((e) => {
			console.error('could not toggle message junk state', e)

			// Revert change
			commit('flagEnvelope', {
				envelope,
				flag: 'junk',
				value: oldState,
			})
		})
	},
	markEnvelopeFavoriteOrUnfavorite({ commit, getters }, { envelope, favFlag }) {
		// Change immediately and switch back on error
		const oldState = envelope.flags.flagged
		commit('flagEnvelope', {
			envelope,
			flag: 'flagged',
			value: favFlag,
		})

		setEnvelopeFlag(envelope.databaseId, 'flagged', favFlag).catch((e) => {
			console.error('could not favorite/unfavorite message ' + envelope.uid, e)

			// Revert change
			commit('flagEnvelope', {
				envelope,
				flag: 'flagged',
				value: oldState,
			})
		})
	},
	async fetchThread({ getters, commit }, id) {
		const thread = await fetchThread(id)
		commit('addEnvelopeThread', {
			id,
			thread,
		})
		return thread
	},
	async fetchMessage({ getters, commit }, id) {
		const message = await fetchMessage(id)
		// Only commit if not undefined (not found)
		if (message) {
			commit('addMessage', {
				message,
			})
		}
		return message
	},
	async deleteMessage({ getters, commit }, { id }) {
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
	},
	async createAlias({ commit }, { account, aliasToAdd }) {
		const alias = await createAlias(account, aliasToAdd)
		commit('createAlias', { account, alias })
	},
	async deleteAlias({ commit }, { account, aliasToDelete }) {
		await deleteAlias(account, aliasToDelete)
		commit('deleteAlias', { account, alias: aliasToDelete })
	},
	async renameMailbox({ commit }, { account, mailbox, newName }) {
		const newMailbox = await patchMailbox(mailbox.databaseId, {
			name: newName,
		})

		console.debug(`mailbox ${mailbox.databaseId} renamed to ${newName}`, { mailbox })
		commit('removeMailbox', { id: mailbox.databaseId })
		commit('addMailbox', { account, mailbox: newMailbox })
	},
	async moveMessage({ commit }, { id, destMailboxId }) {
		await moveMessage(id, destMailboxId)
		commit('removeEnvelope', { id })
		commit('removeMessage', { id })
	},
}
