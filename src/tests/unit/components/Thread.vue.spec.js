/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import {createLocalVue, shallowMount} from '@vue/test-utils'

import Nextcloud from '../../../mixins/Nextcloud.js'
import Thread from '../../../components/Thread.vue'
import { createPinia, setActivePinia } from 'pinia'

import useMainStore from '../../../store/mainStore.js'

const localVue = createLocalVue()

localVue.mixin(Nextcloud)

describe('Thread', () => {
	let store

	beforeEach(() => {
		setActivePinia(createPinia())

		store = useMainStore()
		store.accountsUnmapped[100] = {
			mailboxes: [
				{
					databaseId: 10,
					name: 'INBOX',
					specialRole: 'inbox',
				},
				{
					databaseId: 11,
					name: 'Test',
					specialRole: '',
				},
			],
		}

		store.accountsUnmapped[200] = {
			mailboxes: [
				{
					databaseId: 20,
					name: 'INBOX',
					specialRole: 'inbox',
				},
				{
					databaseId: 21,
					name: 'Test',
					specialRole: '',
				},
				{
					databaseId: 22,
					name: 'Trash',
					specialRole: 'trash',
				},
				{
					databaseId: 23,
					name: 'Junk',
					specialRole: 'junk',
				},
			],
		}

		store.envelopes[200] = {
			accountId: 100,
			threadRootId: '123-456-789',
			mailboxId: 10,
			mailboxes: [
				{
					databaseId: 10,
					name: 'INBOX',
					specialRole: 'inbox',
				},
				{
					databaseId: 11,
					name: 'Test',
					specialRole: '',
				},
			],
		}
		store.envelopes[300] = {
			accountId: 200,
			threadRootId: '456-789-123',
			mailboxId: 20,
			mailboxes: [
				{
					databaseId: 20,
					name: 'INBOX',
					specialRole: 'inbox',
				},
				{
					databaseId: 21,
					name: 'Test',
					specialRole: '',
				},
				{
					databaseId: 22,
					name: 'Trash',
					specialRole: 'trash',
				},
				{
					databaseId: 23,
					name: 'Junk',
					specialRole: 'junk',
				},
			],
		}
		store.envelopes[301] = {
			accountId: 200,
			threadRootId: '456-789-123',
			mailboxId: 22,
		}
		store.envelopes[302] = {
			accountId: 200,
			threadRootId: '456-789-123',
			mailboxId: 23,
		}

		store.mailboxes[10] = {
			databaseId: 10,
			name: 'INBOX',
			accountId: 100,
			specialRole: 'inbox',
		}
		store.mailboxes[20] = {
			databaseId: 20,
			name: 'INBOX',
			accountId: 200,
			specialRole: 'inbox',
		}
		store.mailboxes[22] = {
			databaseId: 22,
			name: 'Trash',
			accountId: 200,
			specialRole: 'trash',
		}
		store.mailboxes[23] = {
			databaseId: 23,
			name: 'Junk',
			accountId: 200,
			specialRole: 'junk',
		}
	})

	it('empty list when envelope not found', () => {
		const view = shallowMount(Thread, {
			mocks: {
				$route: {
					params: {
						threadId: 100,
					},
				},
			},
			store,
			localVue,
		})

		expect(view.vm.thread).toHaveLength(0)
	})

	it('show messages for thread root from inbox and test folder', () => {
		const view = shallowMount(Thread, {
			mocks: {
				$route: {
					params: {
						threadId: 200,
					},
				},
			},
			store,
			localVue,
		})

		expect(view.vm.thread).toHaveLength(3)
	})

	it('show messages for thread root from inbox and test folder, ignore trash', () => {
		const view = shallowMount(Thread, {
			mocks: {
				$route: {
					params: {
						threadId: 300,
					},
				},
			},
			store,
			localVue,
		})

		expect(view.vm.thread).toHaveLength(3)
	})

	it('show messages for thread root only from trash', () => {
		const view = shallowMount(Thread, {
			mocks: {
				$route: {
					params: {
						threadId: 301,
					},
				},
			},
			store,
			localVue,
		})

		const envelopes = view.vm.thread;
		expect(envelopes).toHaveLength(1)
		expect(envelopes[0].mailboxId).toBe(22)
	})

	it('show messages for thread root only from junk', () => {
		const view = shallowMount(Thread, {
			mocks: {
				$route: {
					params: {
						threadId: 302,
					},
				},
			},
			store,
			localVue,
		})

		const envelopes = view.vm.thread;
		expect(envelopes).toHaveLength(1)
		expect(envelopes[0].mailboxId).toBe(23)
	})
})
