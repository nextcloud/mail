/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { DroppableMailboxDirective } from '../../../directives/drag-and-drop/droppable-mailbox/index.js'
import dragEventBus from '../../../directives/drag-and-drop/util/dragEventBus.js'

/**
 * Creates a mock DOM element with a firstChild for the directive.
 *
 * @param {string} id - Identifier for the element
 * @return {object} Mock element
 */
function createMockEl(id) {
	const firstChild = {
		addEventListener: vi.fn(),
		removeEventListener: vi.fn(),
	}
	return { id, firstChild, setAttribute: vi.fn() }
}

/**
 * Creates a mock binding value for the directive.
 *
 * @param {object} overrides - Override default options
 * @return {object} Mock binding
 */
function createBinding(overrides = {}) {
	return {
		value: {
			mainStore: {},
			mailboxId: 1,
			accountId: 1,
			isValidDropTarget: true,
			...overrides,
		},
	}
}

describe('DroppableMailboxDirective', () => {
	// Use the Vue 2 hooks since the project is on Vue 2.7
	const { bind, componentUpdated, unbind } = DroppableMailboxDirective

	let boundEls = []

	afterEach(() => {
		boundEls.forEach((el) => unbind(el))
		boundEls = []
	})

	it('tracks multiple instances independently', () => {
		const el1 = createMockEl('el1')
		const el2 = createMockEl('el2')
		const el3 = createMockEl('el3')

		bind(el1, createBinding({ mailboxId: 1 }))
		bind(el2, createBinding({ mailboxId: 2 }))
		bind(el3, createBinding({ mailboxId: 3 }))
		boundEls.push(el1, el2, el3)

		// All elements should have the expected event listeners registered
		for (const el of [el1, el2, el3]) {
			expect(el.firstChild.addEventListener).toHaveBeenCalledWith(
				'dragover',
				expect.any(Function),
			)
			expect(el.firstChild.addEventListener).toHaveBeenCalledWith(
				'dragleave',
				expect.any(Function),
			)
			expect(el.firstChild.addEventListener).toHaveBeenCalledWith(
				'drop',
				expect.any(Function),
			)
		}
	})

	it('updates the correct instance on componentUpdated', () => {
		const el1 = createMockEl('el1')
		const el2 = createMockEl('el2')

		bind(el1, createBinding({ mailboxId: 1, isValidDropTarget: true }))
		bind(el2, createBinding({ mailboxId: 2, isValidDropTarget: true }))
		boundEls.push(el1, el2)

		// Update el1 to disable drop target
		componentUpdated(
			el1,
			createBinding({ mailboxId: 1, isValidDropTarget: false }),
		)

		// Clear setAttribute calls from bind so we only see drag-start effects
		el1.setAttribute.mockClear()
		el2.setAttribute.mockClear()

		// Trigger drag-start on all instances via the event bus
		dragEventBus.emit('drag-start', { accountId: 1, mailboxId: 99 })

		// el1 should be disabled because its isValidDropTarget was set to false
		expect(el1.setAttribute).toHaveBeenCalledWith(
			'droppable-mailbox',
			'disabled',
		)

		// el2 should NOT be disabled because its options were not changed
		expect(el2.setAttribute).not.toHaveBeenCalledWith(
			'droppable-mailbox',
			'disabled',
		)
	})

	it('removes listeners and instance on unbind', () => {
		const el1 = createMockEl('el1')
		const el2 = createMockEl('el2')

		bind(el1, createBinding({ mailboxId: 1 }))
		bind(el2, createBinding({ mailboxId: 2 }))
		boundEls.push(el2)

		unbind(el1)

		// el1 should have had removeEventListener called for each event
		expect(el1.firstChild.removeEventListener).toHaveBeenCalledWith(
			'dragover',
			expect.any(Function),
		)
		expect(el1.firstChild.removeEventListener).toHaveBeenCalledWith(
			'dragleave',
			expect.any(Function),
		)
		expect(el1.firstChild.removeEventListener).toHaveBeenCalledWith(
			'drop',
			expect.any(Function),
		)

		// el2 should NOT have had removeEventListener called
		expect(el2.firstChild.removeEventListener).not.toHaveBeenCalled()
	})

	it('removes the same listener references that were added', () => {
		const el = createMockEl('el1')

		bind(el, createBinding({ mailboxId: 1 }))
		unbind(el)

		// Each function passed to removeEventListener should be the same
		// reference that was passed to addEventListener. If .bind() is called
		// twice instead of storing the reference, these will be different
		// functions and the listener will leak.
		const added = el.firstChild.addEventListener.mock.calls
		const removed = el.firstChild.removeEventListener.mock.calls

		for (let i = 0; i < 3; i++) {
			const [addEvent, addFn] = added[i]
			const [removeEvent, removeFn] = removed[i]
			expect(removeEvent).toBe(addEvent)
			expect(removeFn).toBe(addFn)
		}
	})

	it('handles unbind of non-existent element gracefully', () => {
		const el = createMockEl('unknown')

		// Should not throw
		expect(() => unbind(el)).not.toThrow()
	})
})
