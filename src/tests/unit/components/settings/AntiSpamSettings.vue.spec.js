/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { shallowMount } from '@vue/test-utils'
import AntiSpamSettings from '../../../../components/settings/AntiSpamSettings.vue'
import Nextcloud from '../../../../mixins/Nextcloud.js'
import { deleteAntiSpamEmail, setAntiSpamEmail } from '../../../../service/SettingsService.js'

vi.mock('@nextcloud/initial-state', () => ({
	loadState: vi.fn(() => ({ spam: 'spam-old@example.com', ham: 'ham-old@example.com' })),
}))
vi.mock('@nextcloud/dialogs', () => ({
	showError: vi.fn(),
	showSuccess: vi.fn(),
}))
vi.mock('../../../../service/SettingsService.js', () => ({
	deleteAntiSpamEmail: vi.fn(),
	setAntiSpamEmail: vi.fn(),
}))

describe('AntiSpamSettings', () => {
	const mountSettings = () => shallowMount(AntiSpamSettings, {
		global: {
			mixins: [Nextcloud],
		},
	})

	afterEach(() => {
		vi.clearAllMocks()
	})

	it('saves the addresses entered after a reset', async () => {
		const wrapper = mountSettings()
		await wrapper.vm.resetForm()
		await wrapper.find('#mail-antispam-email-spam').setValue('spam-new@example.com')
		await wrapper.find('#mail-antispam-email-ham').setValue('ham-new@example.com')

		await wrapper.vm.submitForm()

		expect(deleteAntiSpamEmail).toHaveBeenCalled()
		expect(setAntiSpamEmail).toHaveBeenCalledWith({ spam: 'spam-new@example.com', ham: 'ham-new@example.com' })
	})

	it('keeps the addresses when the reset fails', async () => {
		deleteAntiSpamEmail.mockRejectedValue(new Error('Server error'))
		const wrapper = mountSettings()

		await wrapper.vm.resetForm()

		expect(wrapper.find('#mail-antispam-email-spam').element.value).toBe('spam-old@example.com')
		expect(wrapper.find('#mail-antispam-email-ham').element.value).toBe('ham-old@example.com')
	})
})
