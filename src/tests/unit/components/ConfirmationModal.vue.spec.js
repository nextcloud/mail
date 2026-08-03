/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { shallowMount } from '@vue/test-utils'
import ConfirmationModal from '../../../components/ConfirmationModal.vue'

describe('ConfirmationModal', () => {
	it('renders with default button text', () => {
		const view = shallowMount(ConfirmationModal, {
			props: {},
		})

		expect(view.vm.confirmText).toBe('Confirm')
	})

	it('renders with custom button text', () => {
		const view = shallowMount(ConfirmationModal, {
			props: {
				confirmText: 'Subscribe',
			},
		})

		expect(view.vm.confirmText).toBe('Subscribe')
	})
})
