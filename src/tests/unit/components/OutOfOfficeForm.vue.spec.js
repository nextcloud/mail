/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { flushPromises, shallowMount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import OutOfOfficeForm from '../../../components/OutOfOfficeForm.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'
import * as OutOfOfficeService from '../../../service/OutOfOfficeService.js'

vi.mock('../../../service/OutOfOfficeService.js', () => ({
	fetch: vi.fn(),
	update: vi.fn(),
	followSystem: vi.fn(),
}))

/**
 * @param {boolean} enabled whether the stored autoresponder is on
 * @return {Promise<object>} the mounted wrapper
 */
async function mountForm(enabled) {
	OutOfOfficeService.fetch.mockResolvedValue({
		state: {
			enabled,
			start: '2026-11-01T00:00:00+00:00',
			end: null,
			subject: 'Away',
			message: 'Back soon',
		},
	})

	const view = shallowMount(OutOfOfficeForm, {
		global: { mixins: [Nextcloud] },
		props: {
			account: {
				id: 1,
				name: 'Jane',
				emailAddress: 'jane@example.com',
				aliases: [],
				outOfOfficeFollowsSystem: false,
			},
		},
	})
	await flushPromises()

	return view
}

describe('OutOfOfficeForm', () => {
	beforeEach(() => {
		setActivePinia(createPinia())
	})

	it('disables the date fields while the autoresponder is off', async () => {
		const view = await mountForm(false)

		expect(view.find('#ooo-enable-last-day').attributes('disabled')).toBeDefined()
		expect(view.find('#ooo-first-day').classes()).toContain('form__date-picker--disabled')
	})

	it('enables the date fields while the autoresponder is on', async () => {
		const view = await mountForm(true)

		expect(view.find('#ooo-enable-last-day').attributes('disabled')).toBeUndefined()
		expect(view.find('#ooo-first-day').classes()).not.toContain('form__date-picker--disabled')
	})
})
