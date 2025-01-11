/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import {createLocalVue, shallowMount} from '@vue/test-utils'

import Composer from '../../../components/Composer.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'
import { createPinia, PiniaVuePlugin } from 'pinia'

import useMainStore from '../../../store/mainStore.js'

const localVue = createLocalVue()

localVue.mixin(Nextcloud)
localVue.use(PiniaVuePlugin)
const pinia = createPinia()

describe('Composer', () => {

	let store

	beforeEach(() => {
		Object.defineProperty(window, 'firstDay', {
			value: 0,
		})

		shallowMount(Composer, {
			propsData: {
				isFirstOpen: true,
				accounts: [],
			},
			localVue,
			pinia,
			store,
		})
		store = useMainStore()
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


})
