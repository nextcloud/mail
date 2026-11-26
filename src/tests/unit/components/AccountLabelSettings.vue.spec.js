/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { showError } from '@nextcloud/dialogs'
import { createTestingPinia } from '@pinia/testing'
import { shallowMount } from '@vue/test-utils'
import { setActivePinia } from 'pinia'
import AccountLabelSettings from '../../../components/AccountLabelSettings.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'
import useMainStore from '../../../store/mainStore.js'

vi.mock('@nextcloud/dialogs', async (importOriginal) => ({
	...await importOriginal(),
	showError: vi.fn(),
}))

describe('AccountLabelSettings', () => {
	let store
	let account

	const mountSettings = () => shallowMount(AccountLabelSettings, {
		props: {
			account,
		},
		global: {
			mixins: [Nextcloud],
		},
	})

	beforeEach(() => {
		setActivePinia(createTestingPinia())
		store = useMainStore()
		account = { id: 13, unifiedLabel: '', unifiedLabelColor: '', unifiedLabelAsName: false }
		store.setAccountUnifiedLabel = vi.fn(async ({ label, color, asName = false }) => {
			account.unifiedLabel = label
			account.unifiedLabelColor = color
			account.unifiedLabelAsName = asName
		})
	})

	afterEach(() => {
		vi.clearAllMocks()
	})

	it('saves a trimmed label with its color', async () => {
		const view = mountSettings()
		view.vm.label = '  Work  '
		view.vm.color = '#0082c9'

		await view.vm.save()

		expect(store.setAccountUnifiedLabel).toHaveBeenCalledWith({ accountId: 13, label: 'Work', color: '#0082c9', asName: false })
		expect(view.vm.label).toBe('Work')
	})

	it('enables using the label as the account name', async () => {
		const view = mountSettings()
		view.vm.label = 'Work'

		await view.vm.onAsNameChange(true)

		expect(store.setAccountUnifiedLabel).toHaveBeenCalledWith({ accountId: 13, label: 'Work', color: '', asName: true })
		expect(view.vm.asName).toBe(true)
	})

	it('never turns on the account name switch without a label', async () => {
		account.unifiedLabel = 'Work'
		account.unifiedLabelAsName = false
		const view = mountSettings()
		view.vm.label = '  '

		await view.vm.onAsNameChange(true)

		expect(store.setAccountUnifiedLabel).toHaveBeenCalledWith({ accountId: 13, label: '', color: '', asName: false })
	})

	it('does not save when nothing changed', async () => {
		account.unifiedLabel = 'Work'
		const view = mountSettings()

		await view.vm.save()

		expect(store.setAccountUnifiedLabel).not.toHaveBeenCalled()
	})

	it('saves the picked color', async () => {
		const view = mountSettings()

		await view.vm.onColorSubmit('#ff0000')

		expect(store.setAccountUnifiedLabel).toHaveBeenCalledWith({ accountId: 13, label: '', color: '#ff0000', asName: false })
	})

	it('does not save a color that was picked but not confirmed', async () => {
		const view = mountSettings()
		view.vm.pickerColor = '#ff0000'
		view.vm.onColorPickerClosed()
		view.vm.label = 'Work'

		await view.vm.save()

		expect(view.vm.pickerColor).toBe('')
		expect(store.setAccountUnifiedLabel).toHaveBeenCalledWith({ accountId: 13, label: 'Work', color: '', asName: false })
	})

	it('removes the label, the color and the account name switch', async () => {
		account.unifiedLabel = 'Work'
		account.unifiedLabelColor = '#0082c9'
		account.unifiedLabelAsName = true
		const view = mountSettings()

		await view.vm.clear()

		expect(store.setAccountUnifiedLabel).toHaveBeenCalledWith({ accountId: 13, label: '', color: '', asName: false })
	})

	it('shows an error and restores the saved values when saving fails', async () => {
		account.unifiedLabel = 'Home'
		store.setAccountUnifiedLabel = vi.fn().mockRejectedValue(new Error('network down'))
		const view = mountSettings()
		view.vm.label = 'Work'

		await view.vm.save()

		expect(showError).toHaveBeenCalled()
		expect(view.vm.label).toBe('Home')
		expect(view.vm.saving).toBe(false)
	})
})
