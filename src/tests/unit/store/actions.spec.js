/*
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
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

import { curry, prop, range, reverse } from 'ramda'
import orderBy from 'lodash/fp/orderBy'

import actions from '../../../store/actions'
import * as MailboxService from '../../../service/MailboxService'
import * as MessageService from '../../../service/MessageService'
import * as NotificationService from '../../../service/NotificationService'
import { UNIFIED_ACCOUNT_ID, UNIFIED_INBOX_ID, PAGE_SIZE } from '../../../store/constants'

jest.mock('../../../service/MailboxService')
jest.mock('../../../service/MessageService')
jest.mock('../../../service/NotificationService')

const mockEnvelope = curry((mailboxId, uid) => ({
	mailboxId,
	uid,
	dateInt: uid * 10000,
}))

describe('Vuex store actions', () => {
	let context

	beforeEach(() => {
		context = {
			commit: jest.fn(),
			dispatch: jest.fn(),
			getters: {
				accounts: [],
				getMailbox: jest.fn(),
				getMailboxes: jest.fn(),
				getEnvelope: jest.fn(),
				getEnvelopes: jest.fn(),
				getPreference: jest.fn(),
			},
		}
	})

	it('creates a mailbox', async () => {
		const account = {
			id: 13,
			personalNamespace: '',
		}
		const name = 'Important'
		const mailbox = {
			'name': 'Important',
		}
		MailboxService.create.mockResolvedValue(mailbox)

		const result = await actions.createMailbox(context, {account, name})

		expect(result).toEqual(mailbox)
		expect(context.commit).toHaveBeenCalledTimes(3)
		expect(context.commit).toBeCalledWith('addMailbox', { account, mailbox})
		expect(MailboxService.create).toHaveBeenCalledWith(13, 'Important')
	})

	it('creates a sub-mailbox', async () => {
		const account = {
			id: 13,
			personalNamespace: '',
		}
		const name = 'Archive.2020'
		const mailbox = {
			'name': 'Archive.2020',
		}
		MailboxService.create.mockResolvedValue(mailbox)

		const result = await actions.createMailbox(context, {account, name})

		expect(result).toEqual(mailbox)
		expect(context.commit).toHaveBeenCalledTimes(3)
		expect(context.commit).toBeCalledWith('addMailbox', { account, mailbox})
		expect(MailboxService.create).toHaveBeenCalledWith(13, 'Archive.2020')
	})

	it('adds a prefix to new mailboxes if the account has a personal namespace', async () => {
		const account = {
			id: 13,
			personalNamespace: 'INBOX.',
		}
		const name = 'Important'
		const mailbox = {
			'name': 'INBOX.Important',
		}
		MailboxService.create.mockResolvedValue(mailbox)

		const result = await actions.createMailbox(context, {account, name})

		expect(result).toEqual(mailbox)
		expect(context.commit).toHaveBeenCalledTimes(3)
		expect(context.commit).toBeCalledWith('addMailbox', { account, mailbox})
		expect(MailboxService.create).toHaveBeenCalledWith(13, 'INBOX.Important')
	})

	it('adds no prefix to new sub-mailboxes if the account has a personal namespace', async () => {
		const account = {
			id: 13,
			personalNamespace: 'INBOX.',
		}
		const name = 'INBOX.Archive.2020'
		const mailbox = {
			'name': 'INBOX.Archive.2020',
		}
		MailboxService.create.mockResolvedValue(mailbox)

		const result = await actions.createMailbox(context, {account, name})

		expect(result).toEqual(mailbox)
		expect(context.commit).toHaveBeenCalledTimes(3)
		expect(context.commit).toBeCalledWith('addMailbox', { account, mailbox})
		expect(MailboxService.create).toHaveBeenCalledWith(13, 'INBOX.Archive.2020')
	})

	it('combines unified inbox even if no inboxes are present', async() => {
		context.getters.getMailbox.mockReturnValueOnce({
			isUnified: true,
		})

		const envelopes = await actions.fetchEnvelopes(context, {
			mailboxId: UNIFIED_INBOX_ID,
		})

		expect(envelopes).toEqual([])
	})

	it('creates a unified page from one mailbox', async() => {
		context.getters.accounts.push({
			id: 13,
		})
		context.getters.getMailbox.mockReturnValueOnce({
			isUnified: true,
			specialRole: 'inbox',
			databaseId: UNIFIED_INBOX_ID,
		})
		context.getters.getMailboxes.mockReturnValueOnce([
			{
				id: 'INBOX',
				databaseId: 21,
				accountId: 13,
				specialRole: 'inbox',
			},
			{
				id: 'Drafts',
				databaseId: 22,
				accountId: 13,
				specialRole: 'draft',
			},
		])
		context.dispatch.mockReturnValueOnce([
				{
					databaseId: 123,
					mailboxId: 21,
					uid: 321,
					subject: 'msg1',
				},
			])

		const envelopes = await actions.fetchEnvelopes(context, {
			mailboxId: UNIFIED_INBOX_ID,
		})

		expect(context.getters.getMailbox).toHaveBeenCalledWith(UNIFIED_INBOX_ID)
		expect(context.getters.getMailboxes).toHaveBeenCalledWith(13)
		expect(envelopes).toEqual([
			{
				databaseId: 123,
				mailboxId: 21,
				uid: 321,
				subject: 'msg1',
			},
		])
		expect(context.dispatch).toBeCalledWith('fetchEnvelopes', {
			mailboxId: 21,
			query: undefined,
			addToUnifiedMailboxes: false,
		})
		expect(context.commit).toBeCalledWith('addEnvelope', {
			envelope: {
				databaseId: 123,
				mailboxId: 21,
				uid: 321,
				subject: 'msg1',
			},
			query: undefined,
		})
	})

	it('fetches the next individual page', async() => {
		context.getters.accounts.push({
			accountId: 13,
		})
		context.getters.getMailbox.mockReturnValueOnce({
			name: 'INBOX',
			databaseId: 11,
			accountId: 13,
			specialRole: 'inbox',
			envelopeLists: {
				'': reverse(range(21, 40)),
			},
		})
		context.getters.getEnvelope.mockReturnValueOnce(mockEnvelope(11, 1))
		MessageService.fetchEnvelopes.mockResolvedValue(Promise.resolve(
			reverse(
				range(1, 21).map((n) => ({
					uid: n,
					dateInt: n * 10000,
				}))
			)
		))

		await actions.fetchNextEnvelopes(context, {
			mailboxId: 13,
			quantity: PAGE_SIZE,
		})

		expect(MessageService.fetchEnvelopes).toHaveBeenCalled()
	})

	it('builds the next unified page with local data', async() => {
		const page1 = reverse(range(25, 30))
		const page2 = reverse(range(30, 35))
		const msgs1 = reverse(range(10, 30))
		const msgs2 = reverse(range(5, 35))
		context.getters.accounts.push({
			id: 13,
		})
		context.getters.accounts.push({
			id: 26,
		})
		context.getters.getMailbox.mockReturnValueOnce({
			isUnified: true,
			specialRole: 'inbox',
			accountId: UNIFIED_ACCOUNT_ID,
			databaseId: UNIFIED_INBOX_ID,
		})
		context.getters.getMailboxes.mockReturnValueOnce([
			{
				name: 'INBOX',
				databaseId: 11,
				specialRole: 'inbox',
			},
			{
				name: 'Drafts',
				databaseId: 12,
				specialRole: 'draft',
			},
		])
		context.getters.getMailboxes.mockReturnValueOnce([
			{
				name: 'INBOX',
				databaseId: 21,
				accountId: 26,
				specialRole: 'inbox',
			},
			{
				name: 'Drafts',
				databaseId: 22,
				accountId: 26,
				specialRole: 'draft',
			},
		])
		context.getters.getEnvelopes.mockReturnValueOnce(
				orderBy(
					prop('dateInt'),
					'desc',
					page1.map(mockEnvelope(11)).concat(page2.map(mockEnvelope(21)))
				)
			)
		context.getters.getEnvelopes.mockReturnValueOnce(msgs1.map(mockEnvelope(11)))
		context.getters.getEnvelopes.mockReturnValueOnce(msgs2.map(mockEnvelope(21)))

		await actions.fetchNextEnvelopes(context, {
			mailboxId: UNIFIED_INBOX_ID,
			quantity: PAGE_SIZE,
		})

		expect(context.getters.getMailbox).toHaveBeenCalledWith(UNIFIED_INBOX_ID)
		expect(context.getters.getMailboxes).toHaveBeenCalledWith(13)
		expect(context.getters.getMailboxes).toHaveBeenCalledWith(26)
		expect(context.getters.getEnvelopes).toHaveBeenCalledTimes(3)
		expect(context.getters.getEnvelopes).toHaveBeenCalledWith(UNIFIED_INBOX_ID, undefined)
		expect(context.getters.getEnvelopes).toHaveBeenCalledWith(11, undefined)
		expect(context.getters.getEnvelopes).toHaveBeenCalledWith(21, undefined)
	})

	it('builds the next unified page with partial fetch', async() => {
		const page1 = reverse(range(25, 30))
		const page2 = reverse(range(30, 35))
		const msgs1 = reverse(range(25, 30))
		const msgs2 = reverse(range(5, 35))
		context.getters.accounts.push({
			id: 13,
		})
		context.getters.accounts.push({
			id: 26,
		})
		context.getters.getMailbox.mockReturnValueOnce({
			isUnified: true,
			databaseId: UNIFIED_INBOX_ID,
			specialRole: 'inbox',
			accountId: UNIFIED_ACCOUNT_ID,
			id: UNIFIED_INBOX_ID,
		})
		context.getters.getMailboxes.mockReturnValueOnce([
			{
				name: 'INBOX',
				databaseId: 11,
				specialRole: 'inbox',
			},
			{
				name: 'Drafts',
				databaseId: 12,
				specialRole: 'draft',
			},
		])
		context.getters.getMailboxes.mockReturnValueOnce([
			{
				name: 'INBOX',
				databaseId: 21,
				accountId: 26,
				specialRole: 'inbox',
			},
			{
				name: 'Drafts',
				databaseId: 22,
				accountId: 26,
				specialRole: 'draft',
			},
		])
		context.getters.getEnvelopes.mockReturnValueOnce(
				orderBy(
					prop('dateInt'),
					'desc',
					page1.map(mockEnvelope(11)).concat(page2.map(mockEnvelope(12)))
				)
			)
		context.getters.getEnvelopes.mockReturnValueOnce(msgs1.map(mockEnvelope(11)))
		context.getters.getEnvelopes.mockReturnValueOnce(msgs2.map(mockEnvelope(21)))

		await actions.fetchNextEnvelopes(context, {
			mailboxId: UNIFIED_INBOX_ID,
			quantity: PAGE_SIZE,
		})

		expect(context.getters.getMailbox).toHaveBeenCalledWith(UNIFIED_INBOX_ID)
		expect(context.getters.getEnvelopes).toHaveBeenCalledWith(UNIFIED_INBOX_ID, undefined)
		expect(context.getters.getEnvelopes).toHaveBeenCalledWith(11, undefined)
		expect(context.getters.getEnvelopes).toHaveBeenCalledWith(21, undefined)
	})

	describe('inbox sync', () => {
		beforeEach(() => {

		})

		it('fetches the inbox first', async() => {
			context.getters.accounts.push({
				id: 13,
			})
			context.getters.accounts.push({
				id: 26,
			})
			context.getters.getMailbox.mockReturnValueOnce({
				isUnified: true,
				specialRole: 'inbox',
				accountId: UNIFIED_ACCOUNT_ID,
				id: UNIFIED_INBOX_ID,
			})
			context.getters.getMailboxes.mockReturnValueOnce([
				{
					name: 'INBOX',
					databaseId: 11,
					specialRole: 'inbox',
					envelopeLists: {},
				},
				{
					name: 'Drafts',
					databaseId: 12,
					specialRole: 'draft',
					envelopeLists: {},
				},
			])
			context.getters.getMailboxes.mockReturnValueOnce([
				{
					name: 'INBOX',
					databaseId: 21,
					accountId: 26,
					specialRole: 'inbox',
					envelopeLists: {},
				},
				{
					name: 'Drafts',
					databaseId: 22,
					accountId: 26,
					specialRole: 'draft',
					envelopeLists: {},
				},
			])

			await actions.syncInboxes(context)

			expect(context.getters.getMailboxes).toHaveBeenCalledTimes(2)
			expect(context.dispatch).toHaveBeenCalledTimes(4) // 2 fetch + 2 sync
			expect(context.dispatch).toBeCalledWith('fetchEnvelopes', {
				mailboxId: 11,
			})
			expect(context.dispatch).toBeCalledWith('syncEnvelopes', {
				mailboxId: 11,
			})
			expect(context.dispatch).toBeCalledWith('fetchEnvelopes', {
				mailboxId: 21,
			})
			expect(context.dispatch).toBeCalledWith('syncEnvelopes', {
				mailboxId: 21,
			})
			// We can't detect new messages here
			expect(NotificationService.showNewMessagesNotification).not.toHaveBeenCalled
		})

		it('syncs each individual mailbox', async() => {
			context.getters.accounts.push({
				id: 13,
			})
			context.getters.accounts.push({
				id: 26,
			})
			context.getters.getMailbox.mockReturnValue({
				isUnified: true,
				specialRole: 'inbox',
				accountId: UNIFIED_ACCOUNT_ID,
				id: UNIFIED_INBOX_ID,
				envelopeLists: {
					'': [],
				},
			})
			context.getters.getMailboxes.mockReturnValueOnce([
				{
					name: 'INBOX',
					databaseId: 11,
					specialRole: 'inbox',
					envelopeLists: {
						'': [],
					},
				},
				{
					name: 'Drafts',
					databaseId: 12,
					specialRole: 'draft',
					envelopeLists: {
						'': [],
					},
				},
			])
			context.getters.getMailboxes.mockReturnValueOnce([
				{
					name: 'INBOX',
					databaseId: 21,
					accountId: 26,
					specialRole: 'inbox',
					envelopeLists: {
						'': [],
					},
				},
				{
					name: 'Drafts',
					databaseId: 22,
					accountId: 26,
					specialRole: 'draft',
					envelopeLists: {
						'': [],
					},
				},
			])
			context.dispatch
				.mockReturnValueOnce(Promise.resolve([{ id: 123 }, { id: 321 }]))

			await actions.syncInboxes(context)

			expect(context.getters.getMailbox).toHaveBeenCalledWith(UNIFIED_INBOX_ID)
			expect(context.getters.getMailboxes).toHaveBeenCalledTimes(2)
			expect(context.dispatch).toHaveBeenCalledWith('syncEnvelopes', {
				mailboxId: 11,
			})
			//expect(context.dispatch).have.been
			expect(context.dispatch).toBeCalledWith('syncEnvelopes', {
				mailboxId: 11,
			})
			expect(context.dispatch).toBeCalledWith('syncEnvelopes', {
				mailboxId: 21,
			})
			// Here we expect notifications
			expect(NotificationService.showNewMessagesNotification).toHaveBeenCalled
		})
	})
})
