/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createTestingPinia } from '@pinia/testing'
import { shallowMount } from '@vue/test-utils'
import { setActivePinia } from 'pinia'
import EventModal from '../../../components/EventModal.vue'

describe('EventModal', () => {
	beforeEach(() => {
		setActivePinia(createTestingPinia())
	})

	it('renders default values', () => {
		const view = shallowMount(EventModal, {
			props: {
				envelope: {
					subject: 'Sub?',
					previewText: 'prev',
				},
			},
		})

		expect(view.vm.llmProcessingEnabled).toBe(false)
		expect(view.vm.eventTitle).toBe('Sub?')
		expect(view.vm.description).toBe('prev')
	})
})
