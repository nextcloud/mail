/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { shallowMount } from '@vue/test-utils'
import { createPinia } from 'pinia'
import AccountForm from '../../../components/AccountForm.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'
import { queryIspdb, queryMx, testConnectivity } from '../../../service/AutoConfigService.js'
import useMainStore from '../../../store/mainStore.js'

const pinia = createPinia()

vi.mock('../../../service/AutoConfigService.js')

describe('AccountForm', () => {
	let save
	let store
	let view

	beforeEach(() => {
		save = vi.fn()

		view = shallowMount(AccountForm, {
			props: {
				displayName: 'Tom Turbo',
				email: 'tom@tom.turbo',
				save,
			},
			global: {
				mixins: [Nextcloud],
				plugins: [pinia],
			},
		})

		store = useMainStore()
		store.googleOauthUrl = 'https://google.oauth'
	})

	it('uses name and email from nextcloud account', () => {
		expect(view.vm.accountName).toBe('Tom Turbo')
		expect(view.vm.emailAddress).toBe('tom@tom.turbo')
	})

	it('applies server ISP DB config', async () => {
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
		expect(view.vm.imapConfig.imapUser).toBe('tom')
		expect(view.vm.imapConfig.imapHost).toBe('imap.tom.turbo')
		expect(view.vm.imapConfig.imapPort).toBe(993)
		expect(view.vm.imapConfig.imapSslMode).toBe('ssl')
		expect(view.vm.imapConfig.imapPassword).toBe('secret')
		expect(view.vm.imapConfig.smtpUser).toBe('tom')
		expect(view.vm.imapConfig.smtpHost).toBe('smtp.tom.turbo')
		expect(view.vm.imapConfig.smtpPort).toBe(465)
		expect(view.vm.imapConfig.smtpSslMode).toBe('ssl')
		expect(view.vm.imapConfig.smtpPassword).toBe('secret')
	})

	it('fails to find auto config', async () => {
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

	it('applies server MX config', async () => {
		queryIspdb.mockImplementation(() => Promise.resolve(undefined))
		queryMx.mockImplementation(() => Promise.resolve(['mx.tom.turbo']))
		testConnectivity.mockImplementation((host, port) => Promise.resolve(host === 'mx.tom.turbo' && [993, 465].includes(port)))

		view.vm.autoConfig.password = 'secret'
		const detected = await view.vm.detectConfig()

		expect(queryIspdb).toHaveBeenCalled()
		expect(queryMx).toHaveBeenCalled()
		expect(testConnectivity).toHaveBeenCalledTimes(4)
		expect(detected).toBe(true)
		expect(view.vm.imapConfig.imapUser).toBe('tom@tom.turbo')
		expect(view.vm.imapConfig.imapHost).toBe('mx.tom.turbo')
		expect(view.vm.imapConfig.imapPort).toBe(993)
		expect(view.vm.imapConfig.imapSslMode).toBe('ssl')
		expect(view.vm.imapConfig.imapPassword).toBe('secret')
		expect(view.vm.imapConfig.smtpUser).toBe('tom@tom.turbo')
		expect(view.vm.imapConfig.smtpHost).toBe('mx.tom.turbo')
		expect(view.vm.imapConfig.smtpPort).toBe(465)
		expect(view.vm.imapConfig.smtpSslMode).toBe('ssl')
		expect(view.vm.imapConfig.smtpPassword).toBe('secret')
	})

	describe('SMTP user of an OAuth account', () => {
		beforeEach(() => {
			view.vm.imapConfig.imapHost = 'imap.gmail.com'
			view.vm.imapConfig.smtpUser = 'tom@tom.turbo'
		})

		it('follows a changed email address', async () => {
			view.vm.emailAddress = 'tom@turbo.example'
			await view.vm.$nextTick()

			expect(view.vm.imapConfig.smtpUser).toBe('tom@turbo.example')
		})

		it('keeps an explicitly set value when the email address changes', async () => {
			view.vm.imapConfig.smtpUser = 'delegate@tom.turbo'

			view.vm.emailAddress = 'tom@turbo.example'
			await view.vm.$nextTick()

			expect(view.vm.imapConfig.smtpUser).toBe('delegate@tom.turbo')
		})
	})

	it('prefills a missing SMTP user with the email address', async () => {
		view.vm.imapConfig.smtpUser = null

		view.vm.mode = 'manual'
		await view.vm.$nextTick()

		expect(view.vm.imapConfig.smtpUser).toBe('tom@tom.turbo')
	})
})

describe('AccountForm with JMAP', () => {
	let store

	const mount = (props = {}) => shallowMount(AccountForm, {
		props: {
			displayName: 'Tom Turbo',
			email: 'tom@tom.turbo',
			...props,
		},
		global: {
			mixins: [Nextcloud],
			plugins: [pinia],
		},
	})

	beforeEach(() => {
		store = useMainStore()
		store.googleOauthUrl = undefined
		store.microsoftOauthUrl = undefined
	})

	it('creates a JMAP account without SMTP settings', async () => {
		const account = { id: 7, emailAddress: 'tom@tom.turbo' }
		vi.spyOn(store, 'startAccountSetup').mockResolvedValue(account)
		vi.spyOn(store, 'finishAccountSetup').mockResolvedValue(account)
		const view = mount()
		view.vm.mode = 'manual'
		view.vm.protocol = 'jmap'
		await view.vm.$nextTick()
		view.vm.jmapConfig.host = ' jmap.tom.turbo '
		view.vm.jmapConfig.password = 'secret'

		await view.vm.onSubmit()

		expect(store.startAccountSetup).toHaveBeenCalledWith({
			protocol: 'jmap',
			accountName: 'Tom Turbo',
			emailAddress: 'tom@tom.turbo',
			imapHost: 'jmap.tom.turbo',
			imapPort: 443,
			imapSslMode: 'yes',
			imapUser: 'tom@tom.turbo',
			imapPassword: 'secret',
			path: null,
			authMethod: 'password',
			classificationEnabled: true,
		})
		expect(view.emitted('accountCreated')).toEqual([[account]])
	})

	it('requires a password to create a JMAP account', async () => {
		const view = mount()
		view.vm.mode = 'manual'
		view.vm.protocol = 'jmap'
		view.vm.jmapConfig.host = 'jmap.tom.turbo'
		view.vm.jmapConfig.user = 'tom'

		expect(view.vm.isDisabledManual).toBe(true)

		view.vm.jmapConfig.password = 'secret'

		expect(view.vm.isDisabledManual).toBe(false)
	})

	it('never uses OAuth for JMAP', () => {
		store.googleOauthUrl = 'https://google.oauth'
		const view = mount()
		view.vm.imapConfig.imapHost = 'imap.gmail.com'
		view.vm.mode = 'manual'
		view.vm.protocol = 'jmap'

		expect(view.vm.isGoogleAccount).toBe(false)
		expect(view.vm.useOauth).toBe(false)
	})

	it('updates a JMAP account and keeps the stored password', async () => {
		const account = {
			id: 7,
			protocol: 'jmap',
			imapHost: 'jmap.tom.turbo',
			imapPort: 8443,
			imapSslMode: 'yes',
			imapUser: 'tom',
			path: '/jmap/session',
			classificationEnabled: false,
		}
		vi.spyOn(store, 'updateAccount').mockResolvedValue(account)
		const view = mount({ account })

		expect(view.vm.isJmap).toBe(true)
		expect(view.vm.jmapConfig.port).toBe(8443)
		expect(view.vm.jmapConfig.path).toBe('/jmap/session')

		await view.vm.onSubmit()

		expect(store.updateAccount).toHaveBeenCalledWith({
			protocol: 'jmap',
			accountId: 7,
			accountName: 'Tom Turbo',
			emailAddress: 'tom@tom.turbo',
			imapHost: 'jmap.tom.turbo',
			imapPort: 8443,
			imapSslMode: 'yes',
			imapUser: 'tom',
			path: '/jmap/session',
			authMethod: 'password',
			classificationEnabled: false,
		})
	})
})
