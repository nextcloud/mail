/*
 * @copyright 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
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

import { testConnectivity, queryIspdb, queryMx } from '../../../service/AutoConfigService'
import {createLocalVue, shallowMount} from '@vue/test-utils'
import Vuex from 'vuex'

import AccountForm from '../../../components/AccountForm'
import Nextcloud from '../../../mixins/Nextcloud'

const localVue = createLocalVue()

localVue.use(Vuex)
localVue.mixin(Nextcloud)

jest.mock('../../../service/AutoConfigService')

describe('AccountForm', () => {

	let save
	let getters
	let store
	let view

	beforeEach(() => {
		save = jest.fn()
		getters = {
			googleOauthUrl: () => () => 'https://google.oauth',
		}
		store = new Vuex.Store({
			getters,
		})
		view = shallowMount(AccountForm, {
			propsData: {
				displayName: 'Tom Turbo',
				email: 'tom@tom.turbo',
				save,
			},
			localVue,
			store,
		})
	})

	it('uses name and email from nextcloud account', () => {
		expect(view.vm.accountName).toBe('Tom Turbo')
		expect(view.vm.emailAddress).toBe('tom@tom.turbo')
	})

	it('applies server ISP DB config', async() => {
		queryIspdb.mockImplementation(() => Promise.resolve({
			imapConfig: {
				host: 'imap.tom.turbo',
				port: 993,
				security: 'ssl',
				username: 'tom',
			},
			smtpConfig: {
				host: 'smtp.tom.turbo',
				port: 465,
				security: 'ssl',
				username: 'tom',
			},
		}))

		view.vm.autoConfig.password = 'secret'
		const detected = await view.vm.detectConfig()

		expect(queryIspdb).toHaveBeenCalled()
		expect(queryMx).not.toHaveBeenCalled()
		expect(detected).toBe(true)
		expect(view.vm.manualConfig.imapUser).toBe('tom')
		expect(view.vm.manualConfig.imapHost).toBe('imap.tom.turbo')
		expect(view.vm.manualConfig.imapPort).toBe(993)
		expect(view.vm.manualConfig.imapSslMode).toBe('ssl')
		expect(view.vm.manualConfig.imapPassword).toBe('secret')
		expect(view.vm.manualConfig.smtpUser).toBe('tom')
		expect(view.vm.manualConfig.smtpHost).toBe('smtp.tom.turbo')
		expect(view.vm.manualConfig.smtpPort).toBe(465)
		expect(view.vm.manualConfig.smtpSslMode).toBe('ssl')
		expect(view.vm.manualConfig.smtpPassword).toBe('secret')
	})

	it('fails to find auto config', async() => {
		queryIspdb.mockImplementation(() => Promise.resolve(undefined))
		queryMx.mockImplementation(() => Promise.resolve(['mx.tom.turbo']))
		testConnectivity.mockImplementation(() => Promise.resolve(false))

		view.vm.autoConfig.password = 'secret'
		const detected = await view.vm.detectConfig()

		expect(queryIspdb).toHaveBeenCalled()
		expect(queryMx).toHaveBeenCalled()
		expect(testConnectivity).toHaveBeenCalledTimes(4)
		expect(detected).toBe(false)
		expect(view.vm.error).not.toBe(null)
	})

	it('applies server MX config', async() => {
		queryIspdb.mockImplementation(() => Promise.resolve(undefined))
		queryMx.mockImplementation(() => Promise.resolve(['mx.tom.turbo']))
		testConnectivity.mockImplementation((host, port) => Promise.resolve(host === 'mx.tom.turbo' && [993, 465].includes(port)))

		view.vm.autoConfig.password = 'secret'
		const detected = await view.vm.detectConfig()

		expect(queryIspdb).toHaveBeenCalled()
		expect(queryMx).toHaveBeenCalled()
		expect(testConnectivity).toHaveBeenCalledTimes(8)
		expect(detected).toBe(true)
		expect(view.vm.manualConfig.imapUser).toBe('tom@tom.turbo')
		expect(view.vm.manualConfig.imapHost).toBe('mx.tom.turbo')
		expect(view.vm.manualConfig.imapPort).toBe(993)
		expect(view.vm.manualConfig.imapSslMode).toBe('ssl')
		expect(view.vm.manualConfig.imapPassword).toBe('secret')
		expect(view.vm.manualConfig.smtpUser).toBe('tom@tom.turbo')
		expect(view.vm.manualConfig.smtpHost).toBe('mx.tom.turbo')
		expect(view.vm.manualConfig.smtpPort).toBe(465)
		expect(view.vm.manualConfig.smtpSslMode).toBe('ssl')
		expect(view.vm.manualConfig.smtpPassword).toBe('secret')
	})

})
