/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { showError } from '@nextcloud/dialogs'
import { NcFormBoxSwitch } from '@nextcloud/vue'
import { createLocalVue, shallowMount } from '@vue/test-utils'
import { createPinia, PiniaVuePlugin, setActivePinia } from 'pinia'
import AppSettingsMenu from '../../../components/AppSettingsMenu.vue'
import Logger from '../../../logger.js'
import Nextcloud from '../../../mixins/Nextcloud.js'
import * as PreferenceService from '../../../service/PreferenceService.js'
import useMainStore from '../../../store/mainStore.js'

vi.mock('../../../service/PreferenceService.js')
vi.mock('@nextcloud/dialogs', async (importOriginal) => ({
	...await importOriginal(),
	showError: vi.fn(),
}))

const localVue = createLocalVue()
localVue.mixin(Nextcloud)
localVue.use(PiniaVuePlugin)

describe('reply sender setting', () => {
	const key = 'reply-from-matching-address'
	let store
	let pinia
	let wrapper

	beforeEach(() => {
		vi.clearAllMocks()
		vi.stubGlobal('t', vi.fn((_, message) => message))
		pinia = createPinia()
		setActivePinia(pinia)
		store = useMainStore()
		vi.spyOn(store, 'areTextBlocksFetched').mockReturnValue(true)
		vi.spyOn(Logger, 'error').mockImplementation(() => {})
		PreferenceService.savePreference.mockImplementation(async (_, value) => ({ value }))
	})

	afterEach(() => {
		wrapper?.destroy()
		vi.restoreAllMocks()
		vi.unstubAllGlobals()
	})

	const mountSettings = (stubs = {}) => {
		wrapper = shallowMount(AppSettingsMenu, { localVue, pinia, propsData: { open: false }, mocks: { $id: 'mail-settings-test' }, stubs })
		return wrapper.findAllComponents(NcFormBoxSwitch).wrappers.find((switchView) => switchView.props('label') === 'Automatically select the sender address for replies')
	}

	it('defaults to enabled', () => {
		const switchView = mountSettings()

		expect(switchView.props('modelValue')).toBe(true)
	})

	it.each(['true', 'false'])('loads the persisted value %s', (value) => {
		store.savePreferenceMutation({ key, value })

		const switchView = mountSettings()

		expect(switchView.props('modelValue')).toBe(value === 'true')
	})

	it.each([true, false])('persists a switch change to %s', async (enabled) => {
		store.savePreferenceMutation({ key, value: enabled ? 'false' : 'true' })
		const switchView = mountSettings()

		switchView.vm.$emit('update:modelValue', enabled)
		await vi.waitFor(() => expect(store.getPreference(key)).toBe(String(enabled)))

		expect(PreferenceService.savePreference).toHaveBeenCalledWith(key, String(enabled))
		expect(switchView.props('modelValue')).toBe(enabled)
	})

	it('disables the switch and ignores repeated changes while saving', async () => {
		let finishSaving
		PreferenceService.savePreference.mockReturnValue(new Promise((resolve) => {
			finishSaving = resolve
		}))
		const switchView = mountSettings()

		const saving = wrapper.vm.onToggleReplyFromMatchingAddress(false)
		await wrapper.vm.$nextTick()
		await wrapper.vm.onToggleReplyFromMatchingAddress(false)

		expect(switchView.props('disabled')).toBe(true)
		expect(PreferenceService.savePreference).toHaveBeenCalledTimes(1)
		expect(switchView.props('modelValue')).toBe(false)

		finishSaving({ value: 'false' })
		await saving
		await wrapper.vm.$nextTick()

		expect(switchView.props('disabled')).toBe(false)
		expect(switchView.props('modelValue')).toBe(false)
	})

	it('restores the saved checkbox value and reports a failed save', async () => {
		const error = new Error('Preference save failed')
		let failSaving
		PreferenceService.savePreference.mockReturnValue(new Promise((resolve, reject) => {
			failSaving = reject
		}))
		const switchView = mountSettings({
			NcFormBoxSwitch,
			NcFormBoxItem: { template: '<label><slot name="icon" description-id="description" /></label>' },
		})
		const checkbox = switchView.find('input')
		expect(checkbox.element.checked).toBe(true)

		await checkbox.setChecked(false)
		await vi.waitFor(() => expect(PreferenceService.savePreference).toHaveBeenCalled())
		expect(checkbox.element.checked).toBe(false)

		failSaving(error)
		await vi.waitFor(() => expect(checkbox.element.checked).toBe(true))

		expect(store.getPreference(key, 'true')).toBe('true')
		expect(switchView.props('disabled')).toBe(false)
		expect(showError).toHaveBeenCalledWith('Could not update preference')
		expect(Logger.error).toHaveBeenCalledWith('Could not save reply sender preference', { error })
	})
})
