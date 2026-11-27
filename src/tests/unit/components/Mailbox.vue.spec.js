/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { flushPromises, mount, shallowMount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import Envelope from '../../../components/Envelope.vue'
import EnvelopeBulkActions from '../../../components/EnvelopeBulkActions.vue'
import Mailbox from '../../../components/Mailbox.vue'
import dragEventBus from '../../../directives/drag-and-drop/util/dragEventBus.js'
import MailboxLockedError from '../../../errors/MailboxLockedError.js'
import MailboxNotCachedError from '../../../errors/MailboxNotCachedError.js'
import Nextcloud from '../../../mixins/Nextcloud.js'
import useMainStore from '../../../store/mainStore.js'

const envelope = (databaseId, dateInt) => ({ databaseId, dateInt, flags: {} })

describe('Mailbox', () => {
	let store

	beforeEach(() => {
		setActivePinia(createPinia())

		store = useMainStore()
		store.hasFetchedInitialEnvelopes = true
		store.getEnvelopes = vi.fn().mockReturnValue([])
		store.fetchEnvelopes = vi.fn()
			.mockRejectedValueOnce(new MailboxNotCachedError('22 is not cached'))
			.mockResolvedValue([])
		store.syncEnvelopes = vi.fn()
		store.updateSyncTimestamp = vi.fn()
	})

	afterEach(() => {
		vi.restoreAllMocks()
	})

	function mountMailbox() {
		return shallowMount(Mailbox, {
			props: {
				account: { id: 4 },
				mailbox: { databaseId: 22, accountId: 4 },
				bus: { on: vi.fn(), off: vi.fn() },
				searchQuery: 'is:pi-other',
			},
			global: {
				mixins: [Nextcloud],
			},
		})
	}

	it('initializes an uncached folder and then loads its messages', async () => {
		store.syncEnvelopes.mockResolvedValue([])
		const view = mountMailbox()

		await view.vm.loadEnvelopes()

		expect(store.syncEnvelopes).toHaveBeenCalledWith({ mailboxId: 22, query: 'is:pi-other', init: true })
		expect(store.fetchEnvelopes).toHaveBeenCalledTimes(2)
		expect(view.vm.syncedMailboxes.has('22is:pi-other')).toBe(true)
		expect(view.vm.loadingCacheInitialization).toBe(false)
		expect(view.vm.error).toBe(false)
	})

	it('loads the messages when another section is already initializing the folder', async () => {
		store.syncEnvelopes.mockRejectedValue(new MailboxLockedError('22 is already being synced'))
		const view = mountMailbox()

		await view.vm.loadEnvelopes()

		expect(store.syncEnvelopes).toHaveBeenCalledTimes(1)
		expect(store.fetchEnvelopes).toHaveBeenCalledTimes(2)
		expect(view.vm.loadingCacheInitialization).toBe(false)
		expect(view.vm.error).toBe(false)
	})

	it('shows an error when the initialization fails otherwise', async () => {
		const error = new Error('server error')
		store.syncEnvelopes.mockRejectedValue(error)
		const view = mountMailbox()

		await view.vm.loadEnvelopes()

		expect(store.fetchEnvelopes).toHaveBeenCalledTimes(1)
		expect(view.vm.error).toBe(error)
		expect(view.vm.loadingCacheInitialization).toBe(false)
	})
})

describe('Mailbox selection', () => {
	let store
	let view

	beforeEach(() => {
		setActivePinia(createPinia())

		store = useMainStore()
		store.hasFetchedInitialEnvelopes = true
	})

	afterEach(() => {
		view.unmount()
		vi.restoreAllMocks()
	})

	function mountMailbox({ envelopes = [], groupEnvelopes = [], threadId } = {}) {
		store.getEnvelopes = vi.fn().mockReturnValue(envelopes)
		view = shallowMount(Mailbox, {
			props: {
				account: { id: 4 },
				mailbox: { databaseId: 22, accountId: 4 },
				bus: { on: vi.fn(), off: vi.fn() },
				groupEnvelopes,
			},
			global: {
				mixins: [Nextcloud],
				mocks: {
					$route: { params: { threadId } },
				},
			},
		})
		return view.vm
	}

	it('selects all visible envelopes', () => {
		const vm = mountMailbox({ envelopes: [envelope(1, 3), envelope(2, 2), envelope(3, 1)] })

		vm.selectAll()

		expect(vm.selection).toEqual([1, 2, 3])
	})

	it('keeps the selection in display order', () => {
		const vm = mountMailbox({ envelopes: [envelope(1, 3), envelope(2, 2), envelope(3, 1)] })

		vm.onSelect(3, true)
		vm.onSelect(1, true)

		expect(vm.selection).toEqual([1, 3])
	})

	it('removes a single envelope from the selection', () => {
		const vm = mountMailbox({ envelopes: [envelope(1, 3), envelope(2, 2), envelope(3, 1)] })
		vm.selectAll()

		vm.onSelect(2, false)

		expect(vm.selection).toEqual([1, 3])
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
		store.savePreferenceMutation({ key: 'sort-order', value: 'oldest' })
		const vm = mountMailbox({
			groupEnvelopes: [
				['yesterday', [envelope(3, 2), envelope(4, 1)]],
				['today', [envelope(1, 4), envelope(2, 3)]],
			],
		})

		vm.onSelect(3, true)
		vm.onSelectRange(2, false)

		expect(vm.flatEnvelopeList.map((env) => env.databaseId)).toEqual([4, 3, 2, 1])
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

	it('falls back to the opened thread when the anchor is gone', () => {
		const vm = mountMailbox({
			envelopes: [envelope(1, 3), envelope(2, 2), envelope(3, 1)],
			threadId: '1',
		})
		vm.selectionAnchor = 42

		vm.onSelectRange(2, false)

		expect(vm.selection).toEqual([1, 2, 3])
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
	let store
	let view
	let envelopes
	let route
	let router

	beforeEach(() => {
		const pinia = createPinia()
		setActivePinia(pinia)

		store = useMainStore()
		store.hasFetchedInitialEnvelopes = true
		store.toggleEnvelopeSeen = vi.fn()
		store.deleteThread = vi.fn().mockResolvedValue()
		store.fetchNextEnvelopes = vi.fn().mockResolvedValue()
		for (const env of [envelope(1, 4), envelope(2, 3), envelope(3, 2), envelope(4, 1)]) {
			store.envelopes[env.databaseId] = env
		}
		envelopes = [1, 2, 3, 4].map((id) => store.getEnvelope(id))
		store.getEnvelopes = vi.fn().mockReturnValue(envelopes)
		route = { params: { mailboxId: 22 } }
		router = { push: vi.fn().mockResolvedValue() }

		view = mount(Mailbox, {
			props: {
				account: { id: 4 },
				mailbox: { databaseId: 22, accountId: 4 },
				bus: { on: vi.fn(), off: vi.fn() },
				groupEnvelopes: [
					['today', [envelopes[0], envelopes[1]]],
					['yesterday', [envelopes[2], envelopes[3]]],
				],
			},
			global: {
				plugins: [pinia],
				mixins: [Nextcloud],
				mocks: {
					$route: route,
					$router: router,
				},
				stubs: {
					Envelope: true,
					NcActions: true,
					SectionTitle: true,
				},
			},
		})
	})

	afterEach(() => {
		view.unmount()
		vi.restoreAllMocks()
	})

	const envelopeRows = () => view.findAllComponents(Envelope)

	it('selects a range across groups and shows one bulk action header', async () => {
		envelopeRows()[1].vm.$emit('update:selected', true)
		await view.vm.$nextTick()

		envelopeRows()[3].vm.$emit('selectMultiple')
		await view.vm.$nextTick()

		expect(view.vm.selection).toEqual([2, 3, 4])
		expect(view.find('.mailbox__select-all').exists()).toBe(false)
		expect(view.findAll('.multiselect-header')).toHaveLength(1)
		expect(envelopeRows()[0].props('selected')).toBe(false)
		expect(envelopeRows()[2].props('selected')).toBe(true)
	})

	it('applies bulk actions to the selection of all groups', async () => {
		view.vm.setSelection([2, 3])
		await view.vm.$nextTick()

		await view.findComponent(EnvelopeBulkActions).vm.markSelectedRead()
		await view.vm.$nextTick()

		expect(store.toggleEnvelopeSeen.mock.calls.map(([{ envelope }]) => envelope.databaseId)).toEqual([2, 3])
		expect(view.vm.selection).toEqual([])
	})

	it('passes the full selection to every group for dragging', async () => {
		view.vm.setSelection([2, 3])
		await view.vm.$nextTick()

		const selectedIds = envelopeRows()
			.map((row) => row.props('selectedEnvelopes').map((env) => env.databaseId))

		expect(selectedIds).toEqual([[2, 3], [2, 3], [2, 3], [2, 3]])
	})

	it('selects all through the checkbox', async () => {
		await view.findComponent(NcCheckboxRadioSwitch).find('input').setValue(true)

		expect(view.vm.selection).toEqual([1, 2, 3, 4])
	})

	it('opens the neighbour after deleting the opened thread of a later group', async () => {
		route.params.threadId = '3'
		view.vm.setSelection([3])
		await view.vm.$nextTick()

		await view.findComponent(EnvelopeBulkActions).vm.deleteAllSelected()

		expect(store.deleteThread.mock.calls.map(([{ envelope }]) => envelope.databaseId)).toEqual([3])
		expect(router.push).toHaveBeenCalledWith({ name: 'message', params: { mailboxId: 22, threadId: 2 } })
	})

	it('keeps the opened thread when deleting a selection of another group', async () => {
		route.params.threadId = '1'
		view.vm.setSelection([3, 4])
		await view.vm.$nextTick()

		await view.findComponent(EnvelopeBulkActions).vm.deleteAllSelected()

		expect(store.deleteThread.mock.calls.map(([{ envelope }]) => envelope.databaseId)).toEqual([3, 4])
		expect(router.push).not.toHaveBeenCalled()
	})

	it('leaves the thread view after deleting every shown thread', async () => {
		route.params.threadId = '1'
		view.vm.selectAll()
		await view.vm.$nextTick()

		await view.findComponent(EnvelopeBulkActions).vm.deleteAllSelected()

		expect(router.push).toHaveBeenCalledWith({ name: 'mailbox', params: { mailboxId: 22 } })
	})

	it('clears the selection when it is dropped on a folder', async () => {
		view.vm.setSelection([2, 3])

		dragEventBus.emit('envelopes-dropped')
		await view.vm.$nextTick()

		expect(view.vm.selection).toEqual([])
	})

	it('does not reload the list while messages are left', async () => {
		store.fetchEnvelopes = vi.fn().mockResolvedValue([])

		view.findComponent(EnvelopeBulkActions).vm.$emit('removed')
		await flushPromises()

		expect(store.fetchEnvelopes).not.toHaveBeenCalled()
	})

	it('drops envelopes that are no longer shown', async () => {
		view.vm.selectAll()

		await view.setProps({ groupEnvelopes: [['today', [envelopes[0]]], ['yesterday', [envelopes[3]]]] })

		expect(view.vm.selection).toEqual([1, 4])
	})
})

describe('Mailbox after a bulk action emptied the list', () => {
	let store
	let view

	beforeEach(async () => {
		const pinia = createPinia()
		setActivePinia(pinia)

		store = useMainStore()
		store.hasFetchedInitialEnvelopes = true
		store.getEnvelopes = vi.fn().mockReturnValue([])
		store.fetchEnvelopes = vi.fn().mockResolvedValue([])

		view = mount(Mailbox, {
			props: {
				account: { id: 4 },
				mailbox: { databaseId: 22, accountId: 4 },
				bus: { on: vi.fn(), off: vi.fn() },
				groupEnvelopes: [['today', [envelope(1, 1)]]],
			},
			global: {
				plugins: [pinia],
				mixins: [Nextcloud],
				mocks: {
					$route: { params: {} },
				},
				stubs: {
					Envelope: true,
					NcActions: true,
					SectionTitle: true,
				},
			},
		})
		await view.setProps({ groupEnvelopes: [] })
	})

	afterEach(() => {
		view.unmount()
		vi.restoreAllMocks()
	})

	it('keeps the bulk actions mounted', () => {
		expect(view.vm.hasMessages).toBe(false)
		expect(view.findComponent(EnvelopeBulkActions).exists()).toBe(true)
	})

	it('loads the first page again', async () => {
		view.findComponent(EnvelopeBulkActions).vm.$emit('removed')
		await flushPromises()

		expect(store.fetchEnvelopes).toHaveBeenCalledWith({ mailboxId: 22, query: undefined, limit: 20 })
	})
})

describe('Mailbox selection without date groups', () => {
	let store
	let view

	beforeEach(() => {
		const pinia = createPinia()
		setActivePinia(pinia)

		store = useMainStore()
		store.hasFetchedInitialEnvelopes = true
		for (const env of [envelope(1, 3), envelope(2, 2), envelope(3, 1)]) {
			store.envelopes[env.databaseId] = env
		}
		store.getEnvelopes = vi.fn().mockReturnValue([1, 2, 3].map((id) => store.getEnvelope(id)))

		view = mount(Mailbox, {
			props: {
				account: { id: 4 },
				mailbox: { databaseId: 22, accountId: 4 },
				bus: { on: vi.fn(), off: vi.fn() },
			},
			global: {
				plugins: [pinia],
				mixins: [Nextcloud],
				mocks: {
					$route: { params: {} },
				},
				stubs: {
					Envelope: true,
					NcActions: true,
				},
			},
		})
	})

	afterEach(() => {
		view.unmount()
		vi.restoreAllMocks()
	})

	it('selects a range and clears it through the bulk actions', async () => {
		const rows = view.findAllComponents(Envelope)
		rows[0].vm.$emit('update:selected', true)
		await view.vm.$nextTick()
		rows[2].vm.$emit('selectMultiple')
		await view.vm.$nextTick()

		expect(view.vm.selection).toEqual([1, 2, 3])

		view.findComponent(EnvelopeBulkActions).vm.unselectAll()
		await view.vm.$nextTick()

		expect(view.vm.selection).toEqual([])
	})
})
