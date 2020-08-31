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

import sinon from 'sinon'
import { curry, prop, range, reverse } from 'ramda'
import orderBy from 'lodash/fp/orderBy'

import actions from '../../../store/actions'
import * as MailboxService from '../../../service/MailboxService'
import * as MessageService from '../../../service/MessageService'
import * as NotificationService from '../../../service/NotificationService'
import { UNIFIED_ACCOUNT_ID, UNIFIED_INBOX_ID } from '../../../store/constants'

const mockEnvelope = curry((mailboxId, uid) => ({
	mailboxId,
	uid,
	dateInt: uid * 10000,
}))

describe('Vuex store actions', () => {
	let context

	beforeEach(() => {
		sinon.stub(MailboxService, 'create')

		context = {
			commit: sinon.stub(),
			dispatch: sinon.stub(),
			getters: {
				accounts: [],
				getMailbox: sinon.stub(),
				getMailboxes: sinon.stub(),
				getEnvelope: sinon.stub(),
				getEnvelopes: sinon.stub(),
			},
		}
	})

	afterEach(() => {
		sinon.restore()
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
		MailboxService.create.withArgs(13, 'Important').returns(mailbox)

		const result = await actions.createMailbox(context, {account, name})

		expect(result).to.deep.equal(mailbox)
		expect(context.commit).to.have.been.calledTwice
		expect(context.commit).to.have.been.calledWith('addMailbox', { account, mailbox})
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
		MailboxService.create.withArgs(13, 'Archive.2020').returns(mailbox)

		const result = await actions.createMailbox(context, {account, name})

		expect(result).to.deep.equal(mailbox)
		expect(context.commit).to.have.been.calledTwice
		expect(context.commit).to.have.been.calledWith('addMailbox', { account, mailbox})
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
		MailboxService.create.withArgs(13, 'INBOX.Important').returns(mailbox)

		const result = await actions.createMailbox(context, {account, name})

		expect(result).to.deep.equal(mailbox)
		expect(context.commit).to.have.been.calledTwice
		expect(context.commit).to.have.been.calledWith('addMailbox', { account, mailbox})
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
		MailboxService.create.withArgs(13, 'INBOX.Archive.2020').returns(mailbox)

		const result = await actions.createMailbox(context, {account, name})

		expect(result).to.deep.equal(mailbox)
		expect(context.commit).to.have.been.calledTwice
		expect(context.commit).to.have.been.calledWith('addMailbox', { account, mailbox})
	})

	it('combines unified inbox even if no inboxes are present', () => {
		context.getters.getMailbox.returns({
			isUnified: true,
		})

		const envelopes = actions.fetchEnvelopes(context, {
			mailboxId: UNIFIED_INBOX_ID,
		})

		expect(envelopes).to.be.empty
	})

	it('creates a unified page from one mailbox', async() => {
		context.getters.accounts.push({
			id: 13,
		})
		context.getters.getMailbox.withArgs(UNIFIED_INBOX_ID).returns({
			isUnified: true,
			specialRole: 'inbox',
			databaseId: UNIFIED_INBOX_ID,
		})
		context.getters.getMailboxes.withArgs(13).returns([
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
		context.dispatch
			.withArgs('fetchEnvelopes', {
				mailboxId: 21,
				query: undefined,
			})
			.returns([
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

		expect(envelopes).to.deep.equal([
			{
				databaseId: 123,
				mailboxId: 21,
				uid: 321,
				subject: 'msg1',
			},
		])
		expect(context.dispatch).to.have.been.calledOnce
		expect(context.commit).to.have.been.calledWith('addEnvelope', {
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
		context.getters.getMailbox.withArgs(13).returns({
			name: 'INBOX',
			databaseId: 11,
			accountId: 13,
			specialRole: 'inbox',
			envelopeLists: {
				'': reverse(range(21, 40)),
			},
		})
		context.getters.getEnvelope
			.withArgs(21)
			.returns(mockEnvelope(11, 1))
		sinon.stub(MessageService, 'fetchEnvelopes').returns(
			Promise.resolve(
				reverse(
					range(1, 21).map((n) => ({
						uid: n,
						dateInt: n * 10000,
					}))
				)
			)
		)

		const page = await actions.fetchNextEnvelopePage(context, {
			mailboxId: 13,
		})

		expect(page).to.deep.equal(
			reverse(
				range(1, 21).map((n) => ({
					uid: n,
					dateInt: n * 10000,
				}))
			)
		)
		expect(context.commit).to.have.callCount(20)
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
		context.getters.getMailbox.withArgs(UNIFIED_INBOX_ID).returns({
			isUnified: true,
			specialRole: 'inbox',
			accountId: UNIFIED_ACCOUNT_ID,
			databaseId: UNIFIED_INBOX_ID,
		})
		context.getters.getMailboxes.withArgs(13).returns([
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
		context.getters.getMailboxes.withArgs(26).returns([
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
		context.getters.getEnvelopes
			.withArgs(UNIFIED_INBOX_ID, undefined)
			.returns(
				orderBy(
					prop('dateInt'),
					'desc',
					page1.map(mockEnvelope(11)).concat(page2.map(mockEnvelope(21)))
				)
			)
		context.getters.getEnvelopes.withArgs(11, undefined).returns(msgs1.map(mockEnvelope(11)))
		context.getters.getEnvelopes.withArgs(21, undefined).returns(msgs2.map(mockEnvelope(21)))

		const page = await actions.fetchNextEnvelopePage(context, {
			mailboxId: UNIFIED_INBOX_ID,
		})

		expect(context.dispatch).not.have.been.called
		expect(page.length).to.equal(20)
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
		context.getters.getMailbox.withArgs(UNIFIED_INBOX_ID).returns({
			isUnified: true,
			databaseId: UNIFIED_INBOX_ID,
			specialRole: 'inbox',
			accountId: UNIFIED_ACCOUNT_ID,
			id: UNIFIED_INBOX_ID,
		})
		context.getters.getMailboxes.withArgs(13).returns([
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
		context.getters.getMailboxes.withArgs(26).returns([
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
		context.getters.getEnvelopes
			.withArgs(UNIFIED_INBOX_ID, undefined)
			.returns(
				orderBy(
					prop('dateInt'),
					'desc',
					page1.map(mockEnvelope(11)).concat(page2.map(mockEnvelope(12)))
				)
			)
		context.getters.getEnvelopes.withArgs(11, undefined).returns(msgs1.map(mockEnvelope(11)))
		context.getters.getEnvelopes.withArgs(21, undefined).returns(msgs2.map(mockEnvelope(21)))

		await actions.fetchNextEnvelopePage(context, {
			mailboxId: UNIFIED_INBOX_ID,
		})

		expect(context.dispatch).have.been.calledTwice
		expect(context.dispatch).have.been.calledWith('fetchNextEnvelopePage', {
			mailboxId: 21,
			query: undefined,
		})
		expect(context.dispatch).have.been.calledWith('fetchNextEnvelopePage', {
			mailboxId: UNIFIED_INBOX_ID,
			query: undefined,
			rec: false,
		})
	})

	describe('inbox sync', () => {
		beforeEach(() => {
			sinon.stub(NotificationService, 'showNewMessagesNotification')
		})

		afterEach(() => {
			sinon.restore()
		})

		it('fetches the inbox first', async() => {
			context.getters.accounts.push({
				id: 13,
			})
			context.getters.accounts.push({
				id: 26,
			})
			context.getters.getMailbox.withArgs(UNIFIED_INBOX_ID).returns({
				isUnified: true,
				specialRole: 'inbox',
				accountId: UNIFIED_ACCOUNT_ID,
				id: UNIFIED_INBOX_ID,
			})
			context.getters.getMailboxes.withArgs(13).returns([
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
			context.getters.getMailboxes.withArgs(26).returns([
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

			expect(context.dispatch).have.callCount(4) // 2 fetch + 2 sync
			expect(context.dispatch).have.been.calledWith('fetchEnvelopes', {
				mailboxId: 11,
			})
			expect(context.dispatch).have.been.calledWith('syncEnvelopes', {
				mailboxId: 11,
			})
			expect(context.dispatch).have.been.calledWith('fetchEnvelopes', {
				mailboxId: 21,
			})
			expect(context.dispatch).have.been.calledWith('syncEnvelopes', {
				mailboxId: 21,
			})
			// We can't detect new messages here
			expect(NotificationService.showNewMessagesNotification).not.have.been.called
		})

		it('syncs each individual mailbox', async() => {
			context.getters.accounts.push({
				id: 13,
			})
			context.getters.accounts.push({
				id: 26,
			})
			context.getters.getMailbox.withArgs(UNIFIED_INBOX_ID).returns({
				isUnified: true,
				specialRole: 'inbox',
				accountId: UNIFIED_ACCOUNT_ID,
				id: UNIFIED_INBOX_ID,
				envelopeLists: {
					'': [],
				},
			})
			context.getters.getMailboxes.withArgs(13).returns([
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
			context.getters.getMailboxes.withArgs(26).returns([
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
				.withArgs('syncEnvelopes', {
					mailboxId: 11,
				})
				.returns(Promise.resolve([{ id: 123 }, { id: 321 }]))

			await actions.syncInboxes(context)

			//expect(context.dispatch).have.been
			expect(context.dispatch).have.been.calledWith('syncEnvelopes', {
				mailboxId: 11,
			})
			expect(context.dispatch).have.been.calledWith('syncEnvelopes', {
				mailboxId: 21,
			})
			// Here we expect notifications
			expect(NotificationService.showNewMessagesNotification).have.been.called
		})
	})
})
