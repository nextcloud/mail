/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import {createLocalVue, shallowMount} from '@vue/test-utils'

import ConfirmationModal from '../../../components/ConfirmationModal.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'

const localVue = createLocalVue()

localVue.mixin(Nextcloud)

describe('ConfirmationModal', () => {

	it('renders with default button text', () => {
		const view = shallowMount(ConfirmationModal, {
			propsData: {},
			localVue,
		})

		expect(view.vm.confirmText).toBe('Confirm')
	})

	it('renders with custom button text', () => {
		const view = shallowMount(ConfirmationModal, {
			propsData: {
				confirmText: 'Subscribe',
			},
			localVue,
		})

		expect(view.vm.confirmText).toBe('Subscribe')
	})
})
