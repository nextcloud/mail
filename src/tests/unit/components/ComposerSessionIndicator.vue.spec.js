/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { shallowMount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import ComposerSessionIndicator from '../../../components/ComposerSessionIndicator.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'
import useMainStore from '../../../store/mainStore.js'

describe('ComposerSessionIndicator', () => {
	let store

	beforeEach(() => {
		setActivePinia(createPinia())
		store = useMainStore()
		store.newMessage = { data: { subject: 'Hello' } }
		vi.spyOn(store, 'showMessageComposerMutation')
	})

	it('shows the subject and expands the composer on click', async () => {
		const view = shallowMount(ComposerSessionIndicator, {
			global: { mixins: [Nextcloud] },
		})

		await view.trigger('click')

		expect(view.text()).toContain('Hello')
		expect(store.showMessageComposerMutation).toHaveBeenCalled()
	})

	it('shows a saving hint and ignores clicks while the draft is saved', async () => {
		store.newMessage = { type: 'imap', data: { subject: 'Hello' }, indicatorDisabled: true }
		const view = shallowMount(ComposerSessionIndicator, {
			global: { mixins: [Nextcloud] },
		})

		await view.trigger('click')

		expect(view.text()).toContain('Saving draft')
		expect(view.classes()).toContain('composer-session--disabled')
		expect(store.showMessageComposerMutation).not.toHaveBeenCalled()
	})

	it('shows a saving hint for outbox messages', () => {
		store.newMessage = { type: 'outbox', data: { subject: 'Hello' }, indicatorDisabled: true }
		const view = shallowMount(ComposerSessionIndicator, {
			global: { mixins: [Nextcloud] },
		})

		expect(view.text()).toContain('Saving message')
	})
})
