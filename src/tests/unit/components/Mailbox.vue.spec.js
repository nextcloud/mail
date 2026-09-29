/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { showError } from '@nextcloud/dialogs'
import { createLocalVue, mount, shallowMount } from '@vue/test-utils'
import mitt from 'mitt'
import { createPinia, setActivePinia } from 'pinia'
import EnvelopeList from '../../../components/EnvelopeList.vue'
import Mailbox from '../../../components/Mailbox.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'
import useMainStore from '../../../store/mainStore.js'

vi.mock('@nextcloud/dialogs', async (importOriginal) => ({
	...await importOriginal(),
	showError: vi.fn(),
}))

const localVue = createLocalVue()

localVue.mixin(Nextcloud)

const flushPromises = () => new Promise((resolve) => setTimeout(resolve))

const envelope = (databaseId, dateInt) => ({ databaseId, dateInt, flags: {} })

describe('Mailbox selection', () => {
	let wrapper

	const mountMailbox = ({ envelopes = [], groupEnvelopes = [], threadId } = {}) => {
		wrapper = shallowMount(Mailbox, {
			propsData: {
				account: {},
				mailbox: { databaseId: 1 },
				bus: mitt(),
				groupEnvelopes,
			},
			computed: {
				envelopes: () => envelopes,
			},
			mocks: {
				$route: { params: { threadId } },
			},
			localVue,
		})
		return wrapper.vm
	}

	beforeEach(() => {
		setActivePinia(createPinia())
		useMainStore().setHasFetchedInitialEnvelopesMutation(true)
	})

	afterEach(() => {
		wrapper.destroy()
	})

	it('selects all visible envelopes', () => {
		const vm = mountMailbox({ envelopes: [envelope(1, 3), envelope(2, 2), envelope(3, 1)] })

		vm.selectAll()

		expect(vm.selection).toEqual([1, 2, 3])
	})

	it('toggles single envelopes and keeps display order', () => {
		const vm = mountMailbox({ envelopes: [envelope(1, 3), envelope(2, 2), envelope(3, 1)] })

		vm.onSelect(3, true)
		vm.onSelect(1, true)
		vm.onSelect(3, false)

		expect(vm.selection).toEqual([1])
	})

	it('selects a range across date groups', () => {
		const vm = mountMailbox({
			groupEnvelopes: [
				['today', [envelope(1, 4), envelope(2, 3)]],
				['yesterday', [envelope(3, 2), envelope(4, 1)]],
			],
		})

		vm.onSelect(2, true)
		vm.onSelectRange(3, false)

		expect(vm.groupFlatIndices).toEqual([0, 2])
		expect(vm.selection).toEqual([2, 3, 4])
	})

	it('selects a range across date groups in oldest first order', () => {
		useMainStore().savePreferenceMutation({ key: 'sort-order', value: 'oldest' })
		const vm = mountMailbox({
			groupEnvelopes: [
				['yesterday', [envelope(4, 1), envelope(3, 2)]],
				['today', [envelope(2, 3), envelope(1, 4)]],
			],
		})

		vm.onSelect(3, true)
		vm.onSelectRange(2, false)

		expect(vm.flatEnvelopeList.map((e) => e.databaseId)).toEqual([4, 3, 2, 1])
		expect(vm.selection).toEqual([3, 2])
	})

	it('ignores a range without anchor', () => {
		const vm = mountMailbox({ envelopes: [envelope(1, 3), envelope(2, 2), envelope(3, 1)] })

		vm.onSelectRange(2, false)

		expect(vm.selection).toEqual([])
	})

	it('deselects a range', () => {
		const vm = mountMailbox({ envelopes: [envelope(1, 3), envelope(2, 2), envelope(3, 1)] })
		vm.selectAll()

		vm.onSelect(1, true)
		vm.onSelectRange(1, true)

		expect(vm.selection).toEqual([3])
	})

	it('uses the opened thread as range anchor', () => {
		const vm = mountMailbox({
			envelopes: [envelope(1, 3), envelope(2, 2), envelope(3, 1)],
			threadId: '3',
		})

		vm.onSelectRange(1, false)

		expect(vm.selection).toEqual([2, 3])
	})

	it('ignores envelopes that are not shown', () => {
		const vm = mountMailbox({ envelopes: [envelope(1, 2), envelope(2, 1)] })

		vm.setSelection([2, 42])

		expect(vm.selection).toEqual([2])
	})

	it('clears the selection', () => {
		const vm = mountMailbox({ envelopes: [envelope(1, 2), envelope(2, 1)] })
		vm.selectAll()

		vm.unselectAll()

		expect(vm.selection).toEqual([])
		expect(vm.selectionAnchor).toBeUndefined()
	})
})

describe('Mailbox selection with date groups', () => {
	let wrapper
	let store
	let envelopes

	beforeEach(() => {
		setActivePinia(createPinia())
		store = useMainStore()
		store.setHasFetchedInitialEnvelopesMutation(true)
		store.toggleEnvelopeSeen = vi.fn()
		envelopes = [envelope(1, 4), envelope(2, 3), envelope(3, 2), envelope(4, 1)]
		for (const env of envelopes) {
			store.envelopes[env.databaseId] = env
		}

		wrapper = mount(Mailbox, {
			propsData: {
				account: {},
				mailbox: { databaseId: 1 },
				bus: mitt(),
				groupEnvelopes: [
					['today', [envelopes[0], envelopes[1]]],
					['yesterday', [envelopes[2], envelopes[3]]],
				],
			},
			computed: {
				envelopes: () => envelopes,
			},
			mocks: {
				$route: { params: {} },
			},
			stubs: {
				Envelope: true,
				SectionTitle: true,
			},
			localVue,
		})
	})

	afterEach(() => {
		wrapper.destroy()
	})

	const envelopeRows = () => wrapper.findAll('envelope-stub')

	it('selects a range across groups and shows one bulk action header', async () => {
		envelopeRows().at(1).vm.$emit('update:selected', true)
		await wrapper.vm.$nextTick()

		envelopeRows().at(3).vm.$emit('select-multiple')
		await wrapper.vm.$nextTick()

		expect(wrapper.vm.selection).toEqual([2, 3, 4])
		expect(wrapper.find('.select-all-bar').exists()).toBe(false)
		expect(wrapper.findAll('.multiselect-header').length).toBe(1)
		expect(envelopeRows().at(0).attributes('selected')).toBeUndefined()
		expect(envelopeRows().at(2).attributes('selected')).toBe('true')
	})

	it('applies bulk actions to the selection of all groups', async () => {
		wrapper.vm.setSelection([2, 3])
		await wrapper.vm.$nextTick()

		wrapper.findAllComponents(EnvelopeList).at(0).vm.markSelectedRead()
		await wrapper.vm.$nextTick()

		expect(store.toggleEnvelopeSeen.mock.calls.map(([{ envelope }]) => envelope.databaseId)).toEqual([2, 3])
		expect(wrapper.vm.selection).toEqual([])
	})

	it('passes the full selection to every group for dragging', async () => {
		wrapper.vm.setSelection([2, 3])
		await wrapper.vm.$nextTick()

		const selectedIds = wrapper.findAllComponents(EnvelopeList).wrappers
			.map((list) => list.vm.selectedEnvelopes.map((env) => env.databaseId))

		expect(selectedIds).toEqual([[2, 3], [2, 3]])
	})

	it('selects all through the checkbox', async () => {
		wrapper.findComponent({ name: 'NcCheckboxRadioSwitch' }).vm.$emit('update:checked', true)
		await wrapper.vm.$nextTick()

		expect(wrapper.vm.selection).toEqual([1, 2, 3, 4])
	})

	it('drops envelopes that are no longer shown', async () => {
		wrapper.vm.selectAll()

		await wrapper.setProps({ groupEnvelopes: [['today', [envelopes[0]]], ['yesterday', [envelopes[3]]]] })

		expect(wrapper.vm.selection).toEqual([1, 4])
	})
})

describe('Mailbox all matching selection', () => {
	let wrapper
	let store
	let envelopes

	const mountMailbox = ({ account = {}, isPriorityInbox = false } = {}) => {
		wrapper = mount(Mailbox, {
			propsData: {
				account,
				mailbox: { databaseId: 1 },
				bus: mitt(),
				isPriorityInbox,
				searchQuery: 'is:unread',
			},
			data: () => ({
				testEnvelopes: envelopes,
			}),
			computed: {
				envelopes() {
					return this.testEnvelopes
				},
			},
			mocks: {
				$route: { params: {} },
			},
			stubs: {
				Envelope: true,
			},
			localVue,
		})
		return wrapper.vm
	}

	const banner = () => wrapper.find('.select-all-matching')

	beforeEach(() => {
		setActivePinia(createPinia())
		store = useMainStore()
		store.setHasFetchedInitialEnvelopesMutation(true)
		store.flagMatchingEnvelopes = vi.fn().mockResolvedValue()
		envelopes = [envelope(1, 2), envelope(2, 1)]
		for (const env of envelopes) {
			store.envelopes[env.databaseId] = env
		}
		showError.mockClear()
	})

	afterEach(() => {
		wrapper.destroy()
	})

	it('offers to select all matching messages once the loaded ones are selected', async () => {
		const vm = mountMailbox()

		vm.selectAll()
		await vm.$nextTick()

		expect(banner().text()).toContain('Select all matching messages')
	})

	it('does not offer it when every message is loaded', async () => {
		const vm = mountMailbox()
		vm.endReached = true

		vm.selectAll()
		await vm.$nextTick()

		expect(banner().exists()).toBe(false)
	})

	it('does not offer it in unified mailboxes and the priority inbox', () => {
		const unified = mountMailbox({ account: { isUnified: true } })
		unified.selectAll()
		expect(unified.canSelectAllMatching).toBe(false)
		wrapper.destroy()

		const priority = mountMailbox({ isPriorityInbox: true })
		priority.selectAll()

		expect(priority.canSelectAllMatching).toBe(false)
	})

	it('flags all matching messages on the server with one request', async () => {
		const vm = mountMailbox()
		vm.selectAll()
		await vm.$nextTick()
		await banner().findComponent({ name: 'NcButton' }).trigger('click')
		await vm.$nextTick()

		wrapper.findAll('.multiselect-header button').at(0).trigger('click')
		await flushPromises()

		expect(store.flagMatchingEnvelopes).toHaveBeenCalledWith({ mailboxId: 1, query: 'is:unread', flags: { seen: true } })
		expect(vm.allMatchingSelected).toBe(false)
		expect(vm.selection).toEqual([])
	})

	it('keeps the selection and reports an error when flagging fails', async () => {
		store.flagMatchingEnvelopes.mockRejectedValue(new Error('500'))
		const vm = mountMailbox()
		vm.selectAll()
		vm.allMatchingSelected = true

		await vm.flagAllMatching({ flagged: true })

		expect(showError).toHaveBeenCalled()
		expect(vm.allMatchingSelected).toBe(true)
		expect(vm.allMatchingBusy).toBe(false)
	})

	it('selects messages loaded later while all matching messages are selected', async () => {
		const vm = mountMailbox()
		vm.selectAll()
		vm.allMatchingSelected = true

		vm.testEnvelopes = [...envelopes, envelope(3, 0)]
		await vm.$nextTick()

		expect(vm.selection).toEqual([1, 2, 3])
	})

	it('asks for confirmation before deleting all matching messages', async () => {
		store.deleteMatchingEnvelopes = vi.fn().mockResolvedValue()
		const vm = mountMailbox()
		vm.selectAll()
		vm.allMatchingSelected = true
		await vm.$nextTick()

		await wrapper.find('.multiselect-header [title="Delete all"]').trigger('click')

		expect(store.deleteMatchingEnvelopes).not.toHaveBeenCalled()

		wrapper.findComponent({ name: 'ConfirmationModal' }).vm.$emit('confirm')
		await flushPromises()

		expect(store.deleteMatchingEnvelopes).toHaveBeenCalledWith({ mailboxId: 1, query: 'is:unread' })
		expect(vm.selection).toEqual([])
	})

	it('moves all matching messages to the picked mailbox', async () => {
		store.moveMatchingEnvelopes = vi.fn().mockResolvedValue()
		const vm = mountMailbox()
		vm.selectAll()
		vm.allMatchingSelected = true
		await vm.$nextTick()

		wrapper.findComponent(EnvelopeList).vm.onMoveAllMatching(14)
		await flushPromises()

		expect(store.moveMatchingEnvelopes).toHaveBeenCalledWith({ mailboxId: 1, query: 'is:unread', destMailboxId: 14 })
		expect(vm.allMatchingSelected).toBe(false)
	})

	it('reports an error when moving all matching messages fails', async () => {
		store.moveMatchingEnvelopes = vi.fn().mockRejectedValue(new Error('500'))
		const vm = mountMailbox()
		vm.selectAll()
		vm.allMatchingSelected = true

		await vm.moveAllMatching(14)

		expect(showError).toHaveBeenCalled()
		expect(vm.allMatchingSelected).toBe(true)
		expect(vm.allMatchingBusy).toBe(false)
	})

	it('marks dragged messages as all matching', async () => {
		const vm = mountMailbox()
		vm.selectAll()
		vm.allMatchingSelected = true
		await vm.$nextTick()

		const rows = wrapper.findAllComponents({ name: 'Envelope' })

		expect(rows.at(0).vm.$attrs['all-matching']).toEqual({ mailboxId: 1, query: 'is:unread' })
	})

	it('marks all matching messages as important with one request', async () => {
		store.tagMatchingEnvelopes = vi.fn().mockResolvedValue()
		const vm = mountMailbox()
		vm.selectAll()
		vm.allMatchingSelected = true
		await vm.$nextTick()

		await wrapper.find('.multiselect-header [title="Mark all as important"]').trigger('click')
		await flushPromises()

		expect(store.tagMatchingEnvelopes).toHaveBeenCalledWith({ mailboxId: 1, query: 'is:unread', imapLabel: '$label1', value: true })
		expect(vm.allMatchingSelected).toBe(false)
	})

	it('applies a tag picked in the tag dialog to all matching messages', async () => {
		store.tagMatchingEnvelopes = vi.fn().mockResolvedValue()
		const vm = mountMailbox()
		vm.selectAll()
		vm.allMatchingSelected = true
		await vm.$nextTick()

		wrapper.findComponent(EnvelopeList).vm.onTagAllMatching({ imapLabel: 'work', value: false })
		await flushPromises()

		expect(store.tagMatchingEnvelopes).toHaveBeenCalledWith({ mailboxId: 1, query: 'is:unread', imapLabel: 'work', value: false })
	})

	it('marks all matching messages as spam', async () => {
		store.junkMatchingEnvelopes = vi.fn().mockResolvedValue()
		const vm = mountMailbox()
		vm.selectAll()
		vm.allMatchingSelected = true
		await vm.$nextTick()

		wrapper.findComponent(EnvelopeList).vm.$emit('junk-all-matching', true)
		await flushPromises()

		expect(store.junkMatchingEnvelopes).toHaveBeenCalledWith({ mailboxId: 1, query: 'is:unread', junk: true })
		expect(vm.selection).toEqual([])
	})

	it('reports an error when marking all matching messages as spam fails', async () => {
		store.junkMatchingEnvelopes = vi.fn().mockRejectedValue(new Error('500'))
		const vm = mountMailbox()
		vm.selectAll()
		vm.allMatchingSelected = true

		await vm.junkAllMatching(false)

		expect(showError).toHaveBeenCalled()
		expect(vm.allMatchingSelected).toBe(true)
	})

	it('leaves the all matching mode when the selection is changed by hand', () => {
		const vm = mountMailbox()
		vm.selectAll()
		vm.allMatchingSelected = true

		vm.onSelect(1, false)

		expect(vm.allMatchingSelected).toBe(false)
		expect(vm.selection).toEqual([2])
	})
})
