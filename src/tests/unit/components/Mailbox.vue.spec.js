/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createLocalVue, shallowMount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import mitt from 'mitt'
import Mailbox from '../../../components/Mailbox.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'
import useMainStore from '../../../store/mainStore.js'

vi.mock('../../../directives/drag-and-drop/util/dragEventBus.js', () => ({
	default: { on: vi.fn(), off: vi.fn(), emit: vi.fn() },
}))

const localVue = createLocalVue()
localVue.mixin(Nextcloud)

const envelopes = [
	{ databaseId: 'A', mailboxId: 1 },
	{ databaseId: 'B', mailboxId: 1 },
	{ databaseId: 'C', mailboxId: 1 },
	{ databaseId: 'D', mailboxId: 1 },
	{ databaseId: 'E', mailboxId: 1 },
]

function mountMailbox({ threadId, envelopeList } = {}) {
	const store = useMainStore()
	store.getEnvelopes = vi.fn().mockReturnValue(envelopeList || envelopes)
	store.fetchNextEnvelopes = vi.fn()

	const $router = { push: vi.fn() }
	const $route = {
		params: {
			mailboxId: 1,
			threadId,
		},
	}

	const wrapper = shallowMount(Mailbox, {
		localVue,
		mocks: { $route, $router },
		propsData: {
			account: { id: 1 },
			mailbox: { databaseId: 1, accountId: 1 },
			bus: mitt(),
		},
	})

	return { wrapper, store, $router }
}

describe('Mailbox', () => {
	beforeEach(() => {
		setActivePinia(createPinia())
	})

	describe('navigateToAdjacentEnvelope', () => {
		it('navigates to the envelope above when in the middle', () => {
			// User is viewing envelope C in a list of [A, B, C, D, E].
			// C is being removed, so the method should navigate to B (the one above).
			const { wrapper, $router } = mountMailbox({ threadId: 'C' })
			wrapper.vm.navigateToAdjacentEnvelope('C', ['C'])
			expect($router.push).toHaveBeenCalledWith(expect.objectContaining({
				params: expect.objectContaining({ threadId: 'B' }),
			}))
		})

		it('navigates to the envelope below when at the top', () => {
			const { wrapper, $router } = mountMailbox({ threadId: 'A' })
			wrapper.vm.navigateToAdjacentEnvelope('A', ['A'])
			expect($router.push).toHaveBeenCalledWith(expect.objectContaining({
				params: expect.objectContaining({ threadId: 'B' }),
			}))
		})

		it('navigates to the envelope above when at the bottom', () => {
			const { wrapper, $router } = mountMailbox({ threadId: 'E' })
			wrapper.vm.navigateToAdjacentEnvelope('E', ['E'])
			expect($router.push).toHaveBeenCalledWith(expect.objectContaining({
				params: expect.objectContaining({ threadId: 'D' }),
			}))
		})

		it('does not navigate when it is the only envelope', () => {
			const { wrapper, $router } = mountMailbox({
				threadId: 'A',
				envelopeList: [{ databaseId: 'A', mailboxId: 1 }],
			})
			wrapper.vm.navigateToAdjacentEnvelope('A', ['A'])
			expect($router.push).not.toHaveBeenCalled()
		})

		it('does not navigate when envelope is not in list', () => {
			const { wrapper, $router } = mountMailbox({ threadId: 'Z' })
			wrapper.vm.navigateToAdjacentEnvelope('Z', ['Z'])
			expect($router.push).not.toHaveBeenCalled()
		})

		it('does not navigate when a different message is open', () => {
			const { wrapper, $router } = mountMailbox({ threadId: 'D' })
			wrapper.vm.navigateToAdjacentEnvelope('C', ['C'])
			expect($router.push).not.toHaveBeenCalled()
		})

		it('skips excluded envelopes when bulk moving', () => {
			const { wrapper, $router } = mountMailbox({ threadId: 'C' })
			wrapper.vm.navigateToAdjacentEnvelope('C', ['B', 'C', 'D'])
			expect($router.push).toHaveBeenCalledWith(expect.objectContaining({
				params: expect.objectContaining({ threadId: 'A' }),
			}))
		})

		it('skips excluded envelopes at the top of the list', () => {
			const { wrapper, $router } = mountMailbox({ threadId: 'A' })
			wrapper.vm.navigateToAdjacentEnvelope('A', ['A', 'B'])
			expect($router.push).toHaveBeenCalledWith(expect.objectContaining({
				params: expect.objectContaining({ threadId: 'C' }),
			}))
		})
	})

	describe('onDelete', () => {
		it('fetches next envelopes and navigates', () => {
			const { wrapper, store, $router } = mountMailbox({ threadId: 'C' })
			wrapper.vm.onDelete('C')
			expect(store.fetchNextEnvelopes).toHaveBeenCalledWith(expect.objectContaining({
				mailboxId: 1,
				quantity: 1,
			}))
			expect($router.push).toHaveBeenCalledWith(expect.objectContaining({
				params: expect.objectContaining({ threadId: 'B' }),
			}))
		})
	})

	describe('onMove', () => {
		it('navigates when moving a single envelope', () => {
			const { wrapper, $router } = mountMailbox({ threadId: 'C' })
			wrapper.vm.onMove(['C'])
			expect($router.push).toHaveBeenCalledWith(expect.objectContaining({
				params: expect.objectContaining({ threadId: 'B' }),
			}))
		})

		it('navigates when bulk moving envelopes', () => {
			const { wrapper, $router } = mountMailbox({ threadId: 'C' })
			wrapper.vm.onMove(['B', 'C', 'D'])
			expect($router.push).toHaveBeenCalledWith(expect.objectContaining({
				params: expect.objectContaining({ threadId: 'A' }),
			}))
		})

		it('does not navigate when current thread is not among moved ids', () => {
			const { wrapper, $router } = mountMailbox({ threadId: 'C' })
			wrapper.vm.onMove(['A', 'B'])
			expect($router.push).not.toHaveBeenCalled()
		})
	})

	describe('onEnvelopesDropped', () => {
		it('navigates when the dropped envelope is the current thread', () => {
			const { wrapper, $router } = mountMailbox({ threadId: 'C' })
			wrapper.vm.onEnvelopesDropped({ envelopes: [{ databaseId: 'C' }] })
			expect($router.push).toHaveBeenCalledWith(expect.objectContaining({
				params: expect.objectContaining({ threadId: 'B' }),
			}))
		})

		it('does not navigate when dropped envelope is not the current thread', () => {
			const { wrapper, $router } = mountMailbox({ threadId: 'C' })
			wrapper.vm.onEnvelopesDropped({ envelopes: [{ databaseId: 'A' }] })
			expect($router.push).not.toHaveBeenCalled()
		})

		it('does not navigate when no message is open', () => {
			const { wrapper, $router } = mountMailbox({ threadId: undefined })
			wrapper.vm.onEnvelopesDropped({ envelopes: [{ databaseId: 'A' }] })
			expect($router.push).not.toHaveBeenCalled()
		})
	})
})
