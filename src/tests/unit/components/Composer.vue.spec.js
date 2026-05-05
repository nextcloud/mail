/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createLocalVue, shallowMount } from '@vue/test-utils'
import { createPinia, PiniaVuePlugin } from 'pinia'
import Composer from '../../../components/Composer.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'
import useMainStore from '../../../store/mainStore.js'

const localVue = createLocalVue()

localVue.mixin(Nextcloud)
localVue.use(PiniaVuePlugin)
const pinia = createPinia()

const $route = {
	params: {
		mailboxId: '123', // String because it comes from URL params
	},
}

describe('Composer', () => {
	let store

	beforeEach(() => {
		Object.defineProperty(window, 'firstDay', {
			value: 0,
		})

		const defaultAccount = {
			id: 123,
			editorMode: 'plaintext',
			isUnified: false,
			aliases: [],
			connectionStatus: true,
			emailAddress: 'test@example.com',
			name: 'Test Account',
		}

		shallowMount(Composer, {
			propsData: {
				isFirstOpen: true,
				accounts: [defaultAccount],
			},
			mocks: {
				$route,
			},
			localVue,
			pinia,
			store,
		})
		store = useMainStore()

		// Add a mailbox to the store for the route param to reference
		store.mailboxes[123] = {
			id: 123,
			databaseId: 123,
			accountId: 123,
			name: 'INBOX',
			attributes: [],
			specialUse: ['inbox'],
			envelopeLists: {},
			mailboxes: [],
		}
	})

	it('does not drop the reply message ID', () => {
		const view = shallowMount(Composer, {
			propsData: {
				inReplyToMessageId: 'abc123',
				isFirstOpen: true,
				accounts: [
					{
						id: 123,
						editorMode: 'plaintext',
						isUnified: false,
						aliases: [],
					},
				],
			},
			mocks: {
				$route,
			},
			store,
			localVue,
		})

		const composerData = view.vm.getMessageData()

		expect(composerData.inReplyToMessageId).toEqual('abc123')
	})

	it('disabled the send button', () => {
		const view = shallowMount(Composer, {
			propsData: {
				inReplyToMessageId: 'abc123',
				isFirstOpen: true,
				accounts: [
					{
						id: 123,
						editorMode: 'plaintext',
						isUnified: false,
						aliases: [],
					},
				],
			},
			mocks: {
				$route,
			},
			store,
			localVue,
		})

		const canSend = view.vm.canSend

		expect(canSend).toEqual(false)
	})

	it('enables the send button if data is entered', () => {
		const view = shallowMount(Composer, {
			propsData: {
				inReplyToMessageId: 'abc123',
				to: [
					{ label: 'test', email: 'test@domain.tld' },
				],
				isFirstOpen: true,
				accounts: [
					{
						id: 123,
						editorMode: 'plaintext',
						isUnified: false,
						aliases: [],
					},
				],
			},
			mocks: {
				$route,
			},
			store,
			localVue,
		})

		const canSend = view.vm.canSend

		expect(canSend).toEqual(true)
	})

	it('should not S/MIME sign messages if there are no certs', () => {
		const view = shallowMount(Composer, {
			propsData: {
				isFirstOpen: true,
				accounts: [
					{
						id: 123,
						editorMode: 'plaintext',
						isUnified: false,
						aliases: [],
					},
				],
			},
			mocks: {
				$route,
			},
			computed: {
				smimeCertificateForCurrentAlias() {
					return undefined
				},
			},
			store,
			localVue,
		})

		view.vm.wantsSmimeSign = false
		expect(view.vm.shouldSmimeSign).toEqual(false)

		view.vm.wantsSmimeSign = true
		expect(view.vm.shouldSmimeSign).toEqual(false)
	})

	it('should S/MIME sign messages if there are certs', () => {
		const view = shallowMount(Composer, {
			propsData: {
				isFirstOpen: true,
				accounts: [
					{
						id: 123,
						editorMode: 'plaintext',
						isUnified: false,
						aliases: [],
					},
				],
			},
			mocks: {
				$route,
			},
			computed: {
				smimeCertificateForCurrentAlias() {
					return { foo: 'bar' }
				},
			},
			store,
			localVue,
		})

		view.vm.wantsSmimeSign = true
		expect(view.vm.shouldSmimeSign).toEqual(true)

		view.vm.wantsSmimeSign = false
		expect(view.vm.shouldSmimeSign).toEqual(false)
	})

	it('should not S/MIME encrypt messages if there are no certs', () => {
		const view = shallowMount(Composer, {
			propsData: {
				isFirstOpen: true,
				accounts: [
					{
						id: 123,
						editorMode: 'plaintext',
						isUnified: false,
						aliases: [],
					},
				],
			},
			mocks: {
				$route,
			},
			computed: {
				smimeCertificateForCurrentAlias() {
					return undefined
				},
			},
			store,
			localVue,
		})

		view.vm.wantsSmimeEncrypt = false
		expect(view.vm.shouldSmimeEncrypt).toEqual(false)

		view.vm.wantsSmimeEncrypt = true
		expect(view.vm.shouldSmimeEncrypt).toEqual(false)
	})

	it('should not S/MIME encrypt messages if there are missing recipient certs', () => {
		const view = shallowMount(Composer, {
			propsData: {
				isFirstOpen: true,
				accounts: [
					{
						id: 123,
						editorMode: 'plaintext',
						isUnified: false,
						aliases: [],
					},
				],
			},
			mocks: {
				$route,
			},
			computed: {
				smimeCertificateForCurrentAlias() {
					return { foo: 'bar' }
				},
				missingSmimeCertificatesForRecipients() {
					return ['john@foo.bar']
				},
			},
			store,
			localVue,
		})

		view.vm.wantsSmimeEncrypt = false
		expect(view.vm.shouldSmimeEncrypt).toEqual(false)

		view.vm.wantsSmimeEncrypt = true
		expect(view.vm.shouldSmimeEncrypt).toEqual(false)
	})

	it('should S/MIME sign messages if there are certs', () => {
		const view = shallowMount(Composer, {
			propsData: {
				isFirstOpen: true,
				accounts: [
					{
						id: 123,
						editorMode: 'plaintext',
						isUnified: false,
						aliases: [],
					},
				],
			},
			mocks: {
				$route,
			},
			computed: {
				smimeCertificateForCurrentAlias() {
					return { foo: 'bar' }
				},
				missingSmimeCertificatesForRecipients() {
					return []
				},
			},
			store,
			localVue,
		})

		view.vm.wantsSmimeEncrypt = true
		expect(view.vm.shouldSmimeEncrypt).toEqual(true)

		view.vm.wantsSmimeEncrypt = false
		expect(view.vm.shouldSmimeEncrypt).toEqual(false)
	})

	it('generate title for submit button', () => {
		const view = shallowMount(Composer, {
			propsData: {
				isFirstOpen: true,
				accounts: [
					{
						id: 123,
						editorMode: 'plaintext',
						isUnified: false,
						aliases: [],
					},
				],
			},
			mocks: {
				$route,
			},
			store,
			localVue,
		})

		expect(view.vm.submitButtonTitle).toEqual('Send')

		view.vm.wantsSmimeEncrypt = true
		expect(view.vm.submitButtonTitle).toEqual('Encrypt with S/MIME and send')

		view.vm.wantsSmimeEncrypt = false
		view.vm.mailvelope.available = true
		view.vm.encrypt = true

		expect(view.vm.submitButtonTitle).toEqual('Encrypt with Mailvelope and send')
	})

	it('generate title for submit button (send later)', () => {
		const view = shallowMount(Composer, {
			propsData: {
				isFirstOpen: true,
				accounts: [
					{
						id: 123,
						editorMode: 'plaintext',
						isUnified: false,
						aliases: [],
					},
				],
			},
			mocks: {
				$route,
			},
			store,
			localVue,
		})

		view.vm.sendAtVal = '2023-01-01 14:00'

		expect(view.vm.submitButtonTitle).toEqual('Send later Jan 1, 02:00 PM')

		view.vm.wantsSmimeEncrypt = true
		expect(view.vm.submitButtonTitle).toEqual('Encrypt with S/MIME and send later Jan 1, 02:00 PM')

		view.vm.wantsSmimeEncrypt = false
		view.vm.mailvelope.available = true
		view.vm.encrypt = true

		expect(view.vm.submitButtonTitle).toEqual('Encrypt with Mailvelope and send later Jan 1, 02:00 PM')
	})

	it('does not open the recipient dropdown on focus without a search term', () => {
		const view = shallowMount(Composer, {
			propsData: {
				isFirstOpen: true,
				accounts: [
					{
						id: 123,
						editorMode: 'plaintext',
						isUnified: false,
						aliases: [],
					},
				],
			},
			mocks: {
				$route,
			},
			store,
			localVue,
		})

		expect(view.vm.shouldOpenRecipientDropdown({
			noDrop: false,
			open: true,
			search: '',
		})).toEqual(false)
	})

	it('opens the recipient dropdown once a search term is entered', () => {
		const view = shallowMount(Composer, {
			propsData: {
				isFirstOpen: true,
				accounts: [
					{
						id: 123,
						editorMode: 'plaintext',
						isUnified: false,
						aliases: [],
					},
				],
			},
			mocks: {
				$route,
			},
			store,
			localVue,
		})

		expect(view.vm.shouldOpenRecipientDropdown({
			noDrop: false,
			open: true,
			search: 'alice',
		})).toEqual(true)
	})
})
