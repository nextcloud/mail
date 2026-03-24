/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { DroppableMailboxDirective } from '../../../directives/drag-and-drop/droppable-mailbox/index.js'
import { DroppableMailbox } from '../../../directives/drag-and-drop/droppable-mailbox/droppable-mailbox.js'

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

	it('tracks multiple instances independently', () => {
		const el1 = createMockEl('el1')
		const el2 = createMockEl('el2')
		const el3 = createMockEl('el3')

		bind(el1, createBinding({ mailboxId: 1 }))
		bind(el2, createBinding({ mailboxId: 2 }))
		bind(el3, createBinding({ mailboxId: 3 }))

		// All elements should have listeners registered
		expect(el1.firstChild.addEventListener).toHaveBeenCalledTimes(3)
		expect(el2.firstChild.addEventListener).toHaveBeenCalledTimes(3)
		expect(el3.firstChild.addEventListener).toHaveBeenCalledTimes(3)
	})

	it('updates the correct instance on update', () => {
		const updateSpy = vi.spyOn(DroppableMailbox.prototype, 'update')

		const el1 = createMockEl('el1')
		const el2 = createMockEl('el2')

		bind(el1, createBinding({ mailboxId: 1, isValidDropTarget: true }))
		bind(el2, createBinding({ mailboxId: 2, isValidDropTarget: true }))

		componentUpdated(el1, createBinding({ mailboxId: 1, isValidDropTarget: false }))

		// update() should have been called on the instance bound to el1
		expect(updateSpy).toHaveBeenCalledTimes(1)
		expect(updateSpy.mock.instances[0].el).toBe(el1)

		updateSpy.mockRestore()
	})

	it('removes listeners and instance on unbind', () => {
		const el1 = createMockEl('el1')
		const el2 = createMockEl('el2')

		bind(el1, createBinding({ mailboxId: 1 }))
		bind(el2, createBinding({ mailboxId: 2 }))

		unbind(el1)

		// el1 should have had removeEventListener called
		expect(el1.firstChild.removeEventListener).toHaveBeenCalledTimes(3)

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
