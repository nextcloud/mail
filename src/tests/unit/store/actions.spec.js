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
import {curry, prop, range, reverse} from 'ramda'
import orderBy from 'lodash/fp/orderBy'

import actions from '../../../store/actions'
import * as MessageService from '../../../service/MessageService'
import * as NotificationService from '../../../service/NotificationService'
import {normalizedMessageId} from '../../../store/normalization'
import {UNIFIED_ACCOUNT_ID, UNIFIED_INBOX_ID} from '../../../store/constants'

const mockEnvelope = curry((accountId, folderId, id) => ({
	accountId,
	folderId,
	id,
	uid: normalizedMessageId(accountId, folderId, id),
	dateInt: id * 10000,
}))

describe('Vuex store actions', () => {
	let context

	beforeEach(() => {
		context = {
			commit: sinon.stub(),
			dispatch: sinon.stub(),
			getters: {
				accounts: [],
				getFolder: sinon.stub(),
				getFolders: sinon.stub(),
				getEnvelopeById: sinon.stub(),
				getEnvelopes: sinon.stub(),
			},
		}
	})

	afterEach(() => {
		sinon.restore()
	})

	it('combines unified inbox even if no inboxes are present', () => {
		context.getters.getFolder.returns({
			isUnified: true,
		})

		const envelopes = actions.fetchEnvelopes(context, {
			accountId: UNIFIED_ACCOUNT_ID,
			folderId: UNIFIED_INBOX_ID,
		})

		expect(envelopes).to.be.empty
	})

	it('creates a unified page from one mailbox', async () => {
		context.getters.accounts.push({
			id: 13,
			accountId: 13,
		})
		context.getters.getFolder.withArgs(UNIFIED_ACCOUNT_ID, UNIFIED_INBOX_ID).returns({
			isUnified: true,
			specialRole: 'inbox',
		})
		context.getters.getFolders.withArgs(13).returns([
			{
				id: 'INBOX',
				accountId: 13,
				specialRole: 'inbox',
			},
			{
				id: 'Drafts',
				accountId: 13,
				specialRole: 'draft',
			},
		])
		context.dispatch
			.withArgs('fetchEnvelopes', {
				accountId: 13,
				folderId: 'INBOX',
				query: undefined,
			})
			.returns([
				{
					accountId: 13,
					folderId: 'INBOX',
					uid: '13-INBOX-123',
					subject: 'msg1',
				},
			])

		const envelopes = await actions.fetchEnvelopes(context, {
			accountId: UNIFIED_ACCOUNT_ID,
			folderId: UNIFIED_INBOX_ID,
		})

		expect(envelopes).to.deep.equal([
			{
				accountId: 13,
				folderId: 'INBOX',
				uid: '13-INBOX-123',
				subject: 'msg1',
			},
		])
		expect(context.dispatch).to.have.been.calledOnce
		expect(context.commit).to.have.been.calledWith('addEnvelope', {
			accountId: UNIFIED_ACCOUNT_ID,
			folderId: UNIFIED_INBOX_ID,
			envelope: {
				accountId: 13,
				folderId: 'INBOX',
				uid: '13-INBOX-123',
				subject: 'msg1',
			},
			query: undefined,
		})
	})

	it('fetches the next individual page', async () => {
		context.getters.accounts.push({
			id: 13,
			accountId: 13,
		})
		context.getters.getFolder.withArgs(13, 'INBOX').returns({
			id: 'INBOX',
			accountId: 13,
			specialRole: 'inbox',
			envelopeLists: {
				'': reverse(range(21, 40).map(normalizedMessageId(13, 'INBOX'))),
			},
		})
		context.getters.getEnvelopeById
			.withArgs(normalizedMessageId(13, 'INBOX', 21))
			.returns(mockEnvelope(13, 'INBOX', 1))
		sinon.stub(MessageService, 'fetchEnvelopes').returns(
			Promise.resolve(
				reverse(
					range(1, 21).map((n) => ({
						id: n,
						uid: normalizedMessageId(13, 'INBOX', n),
						dateInt: n * 10000,
					}))
				)
			)
		)

		const page = await actions.fetchNextEnvelopePage(context, {
			accountId: 13,
			folderId: 'INBOX',
		})

		expect(page).to.deep.equal(
			reverse(
				range(1, 21).map((n) => ({
					id: n,
					uid: normalizedMessageId(13, 'INBOX', n),
					dateInt: n * 10000,
				}))
			)
		)
		expect(context.commit).to.have.callCount(20)
	})

	it('builds the next unified page with local data', async () => {
		const page1 = reverse(range(25, 30))
		const page2 = reverse(range(30, 35))
		const msgs1 = reverse(range(10, 30))
		const msgs2 = reverse(range(5, 35))
		context.getters.accounts.push({
			id: 13,
			accountId: 13,
		})
		context.getters.accounts.push({
			id: 26,
			accountId: 26,
		})
		context.getters.getFolder.withArgs(UNIFIED_ACCOUNT_ID, UNIFIED_INBOX_ID).returns({
			isUnified: true,
			specialRole: 'inbox',
			accountId: UNIFIED_ACCOUNT_ID,
			id: UNIFIED_INBOX_ID,
		})
		context.getters.getFolders.withArgs(13).returns([
			{
				id: 'INBOX',
				accountId: 13,
				specialRole: 'inbox',
			},
			{
				id: 'Drafts',
				accountId: 13,
				specialRole: 'draft',
			},
		])
		context.getters.getFolders.withArgs(26).returns([
			{
				id: 'INBOX',
				accountId: 26,
				specialRole: 'inbox',
			},
			{
				id: 'Drafts',
				accountId: 26,
				specialRole: 'draft',
			},
		])
		context.getters.getEnvelopes
			.withArgs(UNIFIED_ACCOUNT_ID, UNIFIED_INBOX_ID, undefined)
			.returns(
				orderBy(
					prop('dateInt'),
					'desc',
					page1.map(mockEnvelope(13, 'INBOX')).concat(page2.map(mockEnvelope(26, 'INBOX')))
				)
			)
		context.getters.getEnvelopes.withArgs(13, 'INBOX', undefined).returns(msgs1.map(mockEnvelope(13, 'INBOX')))
		context.getters.getEnvelopes.withArgs(26, 'INBOX', undefined).returns(msgs2.map(mockEnvelope(26, 'INBOX')))

		const page = await actions.fetchNextEnvelopePage(context, {
			accountId: UNIFIED_ACCOUNT_ID,
			folderId: UNIFIED_INBOX_ID,
		})

		expect(context.dispatch).not.have.been.called
		expect(page.length).to.equal(20)
	})

	it('builds the next unified page with partial fetch', async () => {
		const page1 = reverse(range(25, 30))
		const page2 = reverse(range(30, 35))
		const msgs1 = reverse(range(25, 30))
		const msgs2 = reverse(range(5, 35))
		context.getters.accounts.push({
			id: 13,
			accountId: 13,
		})
		context.getters.accounts.push({
			id: 26,
			accountId: 26,
		})
		context.getters.getFolder.withArgs(UNIFIED_ACCOUNT_ID, UNIFIED_INBOX_ID).returns({
			isUnified: true,
			specialRole: 'inbox',
			accountId: UNIFIED_ACCOUNT_ID,
			id: UNIFIED_INBOX_ID,
		})
		context.getters.getFolders.withArgs(13).returns([
			{
				id: 'INBOX',
				accountId: 13,
				specialRole: 'inbox',
			},
			{
				id: 'Drafts',
				accountId: 13,
				specialRole: 'draft',
			},
		])
		context.getters.getFolders.withArgs(26).returns([
			{
				id: 'INBOX',
				accountId: 26,
				specialRole: 'inbox',
			},
			{
				id: 'Drafts',
				accountId: 26,
				specialRole: 'draft',
			},
		])
		context.getters.getEnvelopes
			.withArgs(UNIFIED_ACCOUNT_ID, UNIFIED_INBOX_ID, undefined)
			.returns(
				orderBy(
					prop('dateInt'),
					'desc',
					page1.map(mockEnvelope(13, 'INBOX')).concat(page2.map(mockEnvelope(26, 'INBOX')))
				)
			)
		context.getters.getEnvelopes.withArgs(13, 'INBOX', undefined).returns(msgs1.map(mockEnvelope(13, 'INBOX')))
		context.getters.getEnvelopes.withArgs(26, 'INBOX', undefined).returns(msgs2.map(mockEnvelope(26, 'INBOX')))

		await actions.fetchNextEnvelopePage(context, {
			accountId: UNIFIED_ACCOUNT_ID,
			folderId: UNIFIED_INBOX_ID,
		})

		expect(context.dispatch).have.been.calledTwice
		expect(context.dispatch).have.been.calledWith('fetchNextEnvelopePage', {
			accountId: 26,
			folderId: 'INBOX',
			query: undefined,
		})
		expect(context.dispatch).have.been.calledWith('fetchNextEnvelopePage', {
			accountId: UNIFIED_ACCOUNT_ID,
			folderId: UNIFIED_INBOX_ID,
			query: undefined,
		})
	})

	describe('inbox sync', () => {
		beforeEach(() => {
			sinon.stub(NotificationService, 'showNewMessagesNotification')
		})

		afterEach(() => {
			sinon.restore()
		})

		it('fetches the inbox first', async () => {
			context.getters.accounts.push({
				id: 13,
				accountId: 13,
			})
			context.getters.accounts.push({
				id: 26,
				accountId: 26,
			})
			context.getters.getFolder.withArgs(UNIFIED_ACCOUNT_ID, UNIFIED_INBOX_ID).returns({
				isUnified: true,
				specialRole: 'inbox',
				accountId: UNIFIED_ACCOUNT_ID,
				id: UNIFIED_INBOX_ID,
			})
			context.getters.getFolders.withArgs(13).returns([
				{
					id: 'INBOX',
					accountId: 13,
					specialRole: 'inbox',
					envelopeLists: {},
				},
				{
					id: 'Drafts',
					accountId: 13,
					specialRole: 'draft',
					envelopeLists: {},
				},
			])
			context.getters.getFolders.withArgs(26).returns([
				{
					id: 'INBOX',
					accountId: 26,
					specialRole: 'inbox',
					envelopeLists: {},
				},
				{
					id: 'Drafts',
					accountId: 26,
					specialRole: 'draft',
					envelopeLists: {},
				},
			])

			await actions.syncInboxes(context)

			expect(context.dispatch).have.callCount(4) // 2 fetch + 2 sync
			expect(context.dispatch).have.been.calledWith('fetchEnvelopes', {
				accountId: 13,
				folderId: 'INBOX',
			})
			expect(context.dispatch).have.been.calledWith('syncEnvelopes', {
				accountId: 13,
				folderId: 'INBOX',
			})
			expect(context.dispatch).have.been.calledWith('fetchEnvelopes', {
				accountId: 26,
				folderId: 'INBOX',
			})
			expect(context.dispatch).have.been.calledWith('syncEnvelopes', {
				accountId: 26,
				folderId: 'INBOX',
			})
			// We can't detect new messages here
			expect(NotificationService.showNewMessagesNotification).not.have.been.called
		})

		it('syncs each individual mailbox', async () => {
			context.getters.accounts.push({
				id: 13,
				accountId: 13,
			})
			context.getters.accounts.push({
				id: 26,
				accountId: 26,
			})
			context.getters.getFolder.withArgs(UNIFIED_ACCOUNT_ID, UNIFIED_INBOX_ID).returns({
				isUnified: true,
				specialRole: 'inbox',
				accountId: UNIFIED_ACCOUNT_ID,
				id: UNIFIED_INBOX_ID,
			})
			context.getters.getFolders.withArgs(13).returns([
				{
					id: 'INBOX',
					accountId: 13,
					specialRole: 'inbox',
					envelopeLists: {
						'': [],
					},
				},
				{
					id: 'Drafts',
					accountId: 13,
					specialRole: 'draft',
					envelopeLists: {
						'': [],
					},
				},
			])
			context.getters.getFolders.withArgs(26).returns([
				{
					id: 'INBOX',
					accountId: 26,
					specialRole: 'inbox',
					envelopeLists: {
						'': [],
					},
				},
				{
					id: 'Drafts',
					accountId: 26,
					specialRole: 'draft',
					envelopeLists: {
						'': [],
					},
				},
			])
			context.dispatch
				.withArgs('syncEnvelopes', {
					accountId: 13,
					folderId: 'INBOX',
				})
				.returns(Promise.resolve([{id: 123}, {id: 321}]))

			await actions.syncInboxes(context)

			expect(context.dispatch).have.been.calledTwice
			expect(context.dispatch).have.been.calledWith('syncEnvelopes', {
				accountId: 13,
				folderId: 'INBOX',
			})
			expect(context.dispatch).have.been.calledWith('syncEnvelopes', {
				accountId: 26,
				folderId: 'INBOX',
			})
			// Here we expect notifications
			expect(NotificationService.showNewMessagesNotification).have.been.called
		})
	})
})
