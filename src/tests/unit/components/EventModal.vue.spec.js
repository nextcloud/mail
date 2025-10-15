/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createLocalVue, shallowMount } from '@vue/test-utils'
import { PiniaVuePlugin, setActivePinia } from 'pinia'
import { createTestingPinia } from '@pinia/testing'

import Nextcloud from '../../../mixins/Nextcloud.js'
import EventModal from '../../../components/EventModal.vue'

const localVue = createLocalVue()
localVue.use(PiniaVuePlugin)
localVue.mixin(Nextcloud)

describe('EventModal', () => {
	beforeEach(() => {
		setActivePinia(createTestingPinia())
	})

	it('renders default values', () => {
		const view = shallowMount(EventModal, {
			localVue,
			propsData: {
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
