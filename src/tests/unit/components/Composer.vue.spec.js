/*
 * @copyright 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author 2023 Richard Steinmetz <richard@steinmetz.cloud>
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

import {createLocalVue, shallowMount} from '@vue/test-utils'
import Vuex from 'vuex'

import Composer from '../../../components/Composer'
import Nextcloud from '../../../mixins/Nextcloud'

const localVue = createLocalVue()

localVue.use(Vuex)
localVue.mixin(Nextcloud)

describe('Composer', () => {

	let actions
	let getters
	let store

	beforeEach(() => {
		actions = {}
		getters = {
			accounts: () => [
				{
					id: 123,
					editorMode: 'plaintext',
					isUnified: false,
					aliases: [],
				},
			],
			getPreference: () => (key, fallback) => fallback,
			getAccount: () => ({}),
			isScheduledSendingDisabled: () => false,
			getSmimeCertificates: () => [],
		}
		store = new Vuex.Store({
			actions,
			getters,
		})
	})

	it('does not drop the reply message ID', () => {
		const view = shallowMount(Composer, {
			propsData: {
				inReplyToMessageId: 'abc123',
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
				]
			},
			store,
			localVue,
		})

		const canSend = view.vm.canSend

		expect(canSend).toEqual(true)
	})

	it('should not S/MIME sign messages if there are no certs', () => {
		const view = shallowMount(Composer, {
			computed: {
				smimeCertificateForCurrentAlias() {
					return undefined
				}
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
			computed: {
				smimeCertificateForCurrentAlias() {
					return { foo: 'bar' }
				}
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
			computed: {
				smimeCertificateForCurrentAlias() {
					return undefined
				}
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
			computed: {
				smimeCertificateForCurrentAlias() {
					return { foo: 'bar' }
				},
				missingSmimeCertificatesForRecipients() {
					return ['john@foo.bar']
				}
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
			computed: {
				smimeCertificateForCurrentAlias() {
					return { foo: 'bar' }
				},
				missingSmimeCertificatesForRecipients() {
					return []
				}
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
