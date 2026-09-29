/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createLocalVue, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import TagAllMatchingModal from '../../../components/TagAllMatchingModal.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'
import useMainStore from '../../../store/mainStore.js'

const localVue = createLocalVue()
localVue.mixin(Nextcloud)

describe('TagAllMatchingModal', () => {
	let wrapper

	beforeEach(() => {
		setActivePinia(createPinia())
		const store = useMainStore()
		for (const tag of [
			{ id: 1, imapLabel: '$label1', displayName: 'Important', color: '#ff0000' },
			{ id: 2, imapLabel: 'work', displayName: 'Work', color: '#00ff00' },
			{ id: 3, imapLabel: '$forwarded', displayName: 'forwarded', color: '#0000ff' },
		]) {
			store.addTagMutation({ tag })
		}
		wrapper = mount(TagAllMatchingModal, {
			stubs: {
				NcDialog: { template: '<div><slot /></div>' },
			},
			localVue,
		})
	})

	afterEach(() => {
		wrapper.destroy()
	})

	it('lists the user tags without important and hidden tags', () => {
		const names = wrapper.findAll('.tag-list__name').wrappers.map((name) => name.text())

		expect(names).toEqual(['Work'])
	})

	it('emits the tag to add or remove', async () => {
		const buttons = wrapper.findAll('.tag-list__item button')

		await buttons.at(0).trigger('click')
		await buttons.at(1).trigger('click')

		expect(wrapper.emitted('tag')).toEqual([
			[{ imapLabel: 'work', value: true }],
			[{ imapLabel: 'work', value: false }],
		])
	})
})
