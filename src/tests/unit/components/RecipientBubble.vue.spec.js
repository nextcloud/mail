/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createLocalVue, mount, shallowMount } from '@vue/test-utils'
import RecipientBubble from '../../../components/RecipientBubble.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'
import { autoCompleteByName, findMatches } from '../../../service/ContactIntegrationService.js'

const localVue = createLocalVue()

localVue.mixin(Nextcloud)

vi.mock('../../../service/AvatarService.js', () => ({
	fetchAvatarUrlMemoized: vi.fn().mockResolvedValue(undefined),
}))
vi.mock('../../../service/ContactIntegrationService.js', () => ({
	addToContact: vi.fn(),
	autoCompleteByName: vi.fn(),
	findMatches: vi.fn(),
	newContact: vi.fn(),
}))

describe('RecipientBubble', () => {
	beforeEach(() => {
		vi.useFakeTimers()
	})

	afterEach(() => {
		vi.useRealTimers()
	})

	it('only offers contacts with an id and a name to add the address to', async () => {
		autoCompleteByName.mockResolvedValue([
			{ id: 'jane', label: 'Jane Doe', email: 'jane@doe.com' },
			{ id: null, label: 'John Doe', email: 'john@doe.com' },
			{ id: null, label: 'Johanna Doe', email: 'johanna@doe.com' },
			{ id: 'jd', label: null, email: 'jd@doe.com' },
			{ id: 'joe', label: 'Joe Doe', email: null },
		])
		const view = shallowMount(RecipientBubble, {
			propsData: {
				email: 'someone@example.com',
				label: 'Someone',
			},
			localVue,
		})

		view.vm.onAutocomplete('Do')
		await vi.runAllTimersAsync()

		expect(autoCompleteByName).toHaveBeenCalledWith('Do')
		expect(view.vm.selectableContacts).toEqual([
			{ id: 'jane', label: 'Jane Doe', email: 'jane@doe.com' },
			{ id: 'joe', label: 'Joe Doe', email: null },
		])
	})

	it('only lists contacts with a name as contacts with this address', async () => {
		findMatches.mockResolvedValue([
			{ id: 'jane', label: 'Jane Doe', email: 'someone@example.com' },
			{ id: null, label: null, email: 'someone@example.com' },
			{ id: 'jd', label: null, email: ['someone@example.com', 'jd@doe.com'] },
			{ id: null, label: 'John Doe', email: 'someone@example.com' },
		])
		const view = shallowMount(RecipientBubble, {
			propsData: {
				email: 'someone@example.com',
				label: 'Someone',
			},
			localVue,
		})

		view.vm.onClickOpenContactDialog()
		await vi.runAllTimersAsync()

		expect(findMatches).toHaveBeenCalledWith('someone@example.com')
		expect(view.vm.contactsWithEmailComputed).toBe('Jane Doe, John Doe')
	})

	it('does not accept free text as contact to add the address to', async () => {
		autoCompleteByName.mockResolvedValue([])
		const view = mount(RecipientBubble, {
			stubs: {
				NcPopover: true,
			},
			propsData: {
				email: 'someone@example.com',
				label: 'Someone',
			},
			localVue,
		})
		await view.setData({ selection: view.vm.ContactSelectionStateEnum.existing })
		const search = view.find('#contact-selection input[type="search"]')

		await search.setValue('Nobody Here')
		await search.trigger('keydown.enter')
		await vi.runAllTimersAsync()

		expect(view.vm.selectedContact).toBeNull()
		expect(view.vm.addButtonDisabled).toBe(true)
	})
})
