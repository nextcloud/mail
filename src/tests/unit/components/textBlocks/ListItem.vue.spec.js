/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createTestingPinia } from '@pinia/testing'
import { shallowMount } from '@vue/test-utils'
import { setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import ListItem from '../../../../components/textBlocks/ListItem.vue'
import Nextcloud from '../../../../mixins/Nextcloud.js'
import useMainStore from '../../../../store/mainStore.js'

vi.mock('@nextcloud/auth', () => ({
	getCurrentUser: () => ({ uid: 'admin' }),
}))
vi.mock('@nextcloud/dialogs', async (importOriginal) => ({
	...await importOriginal(),
	showError: vi.fn(),
	showSuccess: vi.fn(),
}))
vi.mock('../../../../service/TextBlockService.js', () => ({
	getShares: vi.fn().mockResolvedValue([]),
	shareTextBlock: vi.fn(),
	unshareTextBlock: vi.fn(),
}))

describe('ListItem', () => {
	let store

	beforeEach(() => {
		setActivePinia(createTestingPinia())
		store = useMainStore()
	})

	const mountListItem = () => shallowMount(ListItem, {
		global: {
			mixins: [Nextcloud],
		},
		props: {
			textBlock: { id: 1, title: 'Greeting', content: 'Hello', preview: 'Hello' },
		},
	})

	it('closes the dialog after saving', async () => {
		store.patchTextBlock = vi.fn().mockResolvedValue()
		const view = mountListItem()
		view.vm.editModalOpen = true

		await view.vm.saveTextBlock()

		expect(view.vm.editModalOpen).toBe(false)
		expect(view.vm.saveLoading).toBe(false)
	})

	it('keeps the dialog usable when saving fails', async () => {
		store.patchTextBlock = vi.fn().mockRejectedValue(new Error('Server error'))
		const view = mountListItem()
		view.vm.editModalOpen = true

		await view.vm.saveTextBlock()

		expect(view.vm.editModalOpen).toBe(true)
		expect(view.vm.saveLoading).toBe(false)
	})
})
