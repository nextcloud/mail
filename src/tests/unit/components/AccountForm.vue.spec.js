/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { shallowMount } from '@vue/test-utils'
import { createPinia } from 'pinia'
import AccountForm from '../../../components/AccountForm.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'
import { discover, DiscoveryError } from '../../../service/DiscoveryService.ts'
import useMainStore from '../../../store/mainStore.js'

const pinia = createPinia()

vi.mock('../../../service/DiscoveryService.ts', async (importOriginal) => ({
	...await importOriginal(),
	discover: vi.fn(),
}))

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

describe('AccountForm auto discovery', () => {
	let store

	const endpoint = (host, port) => ({ host, port, security: 'ssl', username: 'tom@tom.turbo' })
	const jmapEvent = {
		type: 'candidate',
		protocol: 'jmap',
		source: 'well-known',
		config: { jmap: { endpoint: endpoint('jmap.tom.turbo', 443), path: '/.well-known/jmap', authentication: ['password'] } },
	}
	const imapEvent = {
		type: 'candidate',
		protocol: 'imap',
		source: 'autoconfig',
		config: { imap: { incoming: endpoint('imap.tom.turbo', 993), outgoing: endpoint('smtp.tom.turbo', 465) } },
	}
	const connectionFailure = (service, error = 'AUTHENTICATION_WRONG_PASSWORD') => ({ data: { error, service } })

	const mount = () => {
		const view = shallowMount(AccountForm, {
			props: {
				displayName: 'Tom Turbo',
				email: 'tom@tom.turbo',
			},
			global: {
				mixins: [Nextcloud],
				plugins: [pinia],
			},
		})
		view.vm.autoConfig.password = 'secret'
		return view
	}

	const discovers = (...events) => discover.mockImplementation(async (email, onEvent) => {
		for (const event of events) {
			onEvent(event)
		}
	})

	beforeEach(() => {
		store = useMainStore()
		store.googleOauthUrl = undefined
		store.microsoftOauthUrl = undefined
		discover.mockReset()
	})

	it('creates the account with the only usable candidate without asking', async () => {
		const account = { id: 7, emailAddress: 'tom@tom.turbo' }
		discovers(jmapEvent)
		vi.spyOn(store, 'startAccountSetup').mockResolvedValue(account)
		vi.spyOn(store, 'finishAccountSetup').mockResolvedValue(account)
		const view = mount()

		await view.vm.onSubmit()

		expect(discover).toHaveBeenCalledWith('tom@tom.turbo', expect.any(Function), expect.any(AbortSignal))
		expect(view.vm.discoveryChoices).toEqual([])
		expect(store.startAccountSetup).toHaveBeenCalledTimes(1)
		expect(store.startAccountSetup.mock.calls[0][0]).toMatchObject({ protocol: 'jmap', imapHost: 'jmap.tom.turbo', imapSslMode: 'yes', imapPassword: 'secret' })
		expect(view.emitted('accountCreated')).toEqual([[account]])
		expect(view.vm.discoveryProgress).toBe(null)
	})

	it('lets the user choose when both protocols are found', async () => {
		const account = { id: 7, emailAddress: 'tom@tom.turbo' }
		discovers(imapEvent, jmapEvent)
		vi.spyOn(store, 'startAccountSetup').mockResolvedValue(account)
		vi.spyOn(store, 'finishAccountSetup').mockResolvedValue(account)
		const view = mount()

		const submit = view.vm.onSubmit()
		await vi.waitFor(() => expect(view.vm.discoveryChoices).toHaveLength(2))
		await view.vm.$nextTick()

		const progress = view.findComponent({ name: 'AccountFormDiscoveryProgress' })
		expect(progress.props('choices')).toEqual(['jmap', 'imap'])
		expect(store.startAccountSetup).not.toHaveBeenCalled()

		progress.vm.$emit('choose', 'imap')
		await submit

		expect(store.startAccountSetup).toHaveBeenCalledTimes(1)
		expect(store.startAccountSetup.mock.calls[0][0]).toMatchObject({ imapHost: 'imap.tom.turbo', smtpHost: 'smtp.tom.turbo', imapPassword: 'secret', smtpPassword: 'secret' })
		expect(store.startAccountSetup.mock.calls[0][0].protocol).toBeUndefined()
		expect(view.emitted('accountCreated')).toEqual([[account]])
		expect(view.vm.discoveryChoices).toEqual([])
	})

	it('hides the inputs and shows the progress while discovering', async () => {
		let failSetup
		discovers(jmapEvent)
		vi.spyOn(store, 'startAccountSetup').mockReturnValueOnce(new Promise((resolve, reject) => {
			failSetup = reject
		}))
		const view = mount()

		const submit = view.vm.onSubmit()
		await vi.waitFor(() => expect(store.startAccountSetup).toHaveBeenCalled())
		await view.vm.$nextTick()

		expect(view.find('.account-form__fields').attributes('style')).toContain('display: none')
		const progress = view.findComponent({ name: 'AccountFormDiscoveryProgress' })
		expect(progress.props('email')).toBe('tom@tom.turbo')
		expect(progress.props('message')).toBe('Testing authentication')

		failSetup(connectionFailure('JMAP'))
		await submit
		await view.vm.$nextTick()

		expect(view.find('.account-form__fields').attributes('style')).not.toContain('display: none')
		expect(view.findComponent({ name: 'AccountFormDiscoveryProgress' }).exists()).toBe(false)
	})

	it('switches to manual mode with the candidate when it fails to connect', async () => {
		discovers(jmapEvent)
		vi.spyOn(store, 'startAccountSetup').mockRejectedValueOnce(connectionFailure('JMAP'))
		const view = mount()

		await view.vm.onSubmit()

		expect(store.startAccountSetup).toHaveBeenCalledTimes(1)
		expect(view.vm.mode).toBe('manual')
		expect(view.vm.protocol).toBe('jmap')
		expect(view.vm.jmapConfig.host).toBe('jmap.tom.turbo')
		expect(view.vm.feedback).toBe('JMAP username or password is wrong')
	})

	it('stays in auto mode after an unrelated error', async () => {
		discovers(imapEvent)
		vi.spyOn(store, 'startAccountSetup').mockRejectedValueOnce(new Error('server error'))
		const view = mount()

		await view.vm.onSubmit()

		expect(store.startAccountSetup).toHaveBeenCalledTimes(1)
		expect(view.vm.mode).toBe('auto')
		expect(view.vm.feedback).toBe('There was an error while setting up your account')
	})

	it('reports when nothing was discovered', async () => {
		discovers({ type: 'complete', protocol: 'jmap', found: false }, { type: 'complete', protocol: 'imap', found: false })
		vi.spyOn(store, 'startAccountSetup')
		const view = mount()

		await view.vm.onSubmit()

		expect(store.startAccountSetup).not.toHaveBeenCalled()
		expect(view.vm.mode).toBe('auto')
		expect(view.vm.feedback).toBe('Configuration discovery failed. Please use the manual settings')
	})

	it('fills the manual form with a candidate that needs more input', async () => {
		discovers({ ...imapEvent, config: { imap: { incoming: endpoint('imap.tom.turbo', 993), outgoing: null } } })
		vi.spyOn(store, 'startAccountSetup')
		const view = mount()

		await view.vm.onSubmit()

		expect(store.startAccountSetup).not.toHaveBeenCalled()
		expect(view.vm.mode).toBe('manual')
		expect(view.vm.imapConfig.imapHost).toBe('imap.tom.turbo')
		expect(view.vm.imapConfig.smtpHost).toBe('')
	})

	it('reports a rate limited discovery', async () => {
		discover.mockRejectedValue(new DiscoveryError('Discovery failed with status 429', 429))
		const view = mount()

		await view.vm.onSubmit()

		expect(view.vm.feedback).toBe('Configuration discovery temporarily not available. Please try again later.')
	})
})
