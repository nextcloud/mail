/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createTestingPinia } from '@pinia/testing'
import { shallowMount } from '@vue/test-utils'
import { setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import NcPopover from '@nextcloud/vue/components/NcPopover'
import MailboxThread from '../../../components/MailboxThread.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'
import useMainStore from '../../../store/mainStore.js'
import eventBus from '../../../util/eventBus.js'

describe('MailboxThread', () => {
	let store

	beforeEach(() => {
		setActivePinia(createTestingPinia())
		store = useMainStore()
		store.getPreference = vi.fn().mockImplementation((key, fallback) => (key === 'sort-favorites' ? 'true' : fallback))
		store.getMailbox = vi.fn().mockReturnValue({ databaseId: 'unified' })
		store.getEnvelopes = vi.fn().mockReturnValue([])
	})

	it('opens the section info popovers on hover and keyboard focus', () => {
		const view = shallowMount(MailboxThread, {
			global: {
				mixins: [Nextcloud],
				mocks: {
					$route: { name: 'mailbox', params: {}, query: {} },
					$router: { replace: vi.fn() },
				},
				stubs: {
					NcAppContent: { template: '<div><slot name="list" /></div>' },
				},
				directives: {
					infiniteScroll: {},
					shortkey: {},
				},
			},
			props: {
				account: { accountId: 1 },
				mailbox: { databaseId: 'priority', isPriorityInbox: true },
			},
		})

		const popovers = view.findAllComponents(NcPopover)

		expect(popovers.length).toBeGreaterThan(0)
		popovers.forEach((popover) => {
			expect(popover.props('triggers')).toEqual(['hover', 'focus'])
		})
		view.unmount()
	})

	describe('onShortcut', () => {
		const createContext = () => ({
			mainStore: { startComposerSession: vi.fn() },
			bus: { emit: vi.fn() },
		})

		it('starts a blank message on compose', () => {
			const context = createContext()

			MailboxThread.methods.onShortcut.call(context, { srcKey: 'compose' })

			expect(context.mainStore.startComposerSession).toHaveBeenCalledWith({ isBlankMessage: true })
			expect(context.bus.emit).not.toHaveBeenCalled()
		})

		it('requests a refresh through the event bus on refresh', () => {
			const context = createContext()
			const onRefresh = vi.fn()
			eventBus.on('mail:refresh', onRefresh)

			MailboxThread.methods.onShortcut.call(context, { srcKey: 'refresh' })
			eventBus.off('mail:refresh', onRefresh)

			expect(onRefresh).toHaveBeenCalledOnce()
			expect(context.bus.emit).not.toHaveBeenCalled()
		})

		it('forwards other shortcuts to the mailbox', () => {
			const context = createContext()
			const event = { srcKey: 'backspace' }

			MailboxThread.methods.onShortcut.call(context, event)

			expect(context.bus.emit).toHaveBeenCalledWith('shortcut', event)
		})
	})

	describe('handleSearchShortcut', () => {
		const createContext = () => ({
			$refs: { searchMessages: { focusInput: vi.fn() } },
		})
		const createEvent = (init, target = document.body) => {
			const event = new KeyboardEvent('keydown', init)
			Object.defineProperty(event, 'target', { value: target })
			return event
		}

		it.each([
			['Ctrl', { ctrlKey: true }],
			['Cmd', { metaKey: true }],
		])('focuses the search input on %s+F and keeps it from the unified search', (_, modifier) => {
			const context = createContext()
			const handler = (event) => MailboxThread.methods.handleSearchShortcut.call(context, event)
			const unifiedSearch = vi.fn()
			document.addEventListener('keydown', handler, true)
			window.addEventListener('keydown', unifiedSearch)
			const event = new KeyboardEvent('keydown', { key: 'f', bubbles: true, cancelable: true, ...modifier })

			document.body.dispatchEvent(event)
			document.removeEventListener('keydown', handler, true)
			window.removeEventListener('keydown', unifiedSearch)

			expect(context.$refs.searchMessages.focusInput).toHaveBeenCalledOnce()
			expect(event.defaultPrevented).toBe(true)
			expect(unifiedSearch).not.toHaveBeenCalled()
		})

		it('ignores F without a modifier', () => {
			const context = createContext()
			const event = createEvent({ key: 'f', cancelable: true })

			MailboxThread.methods.handleSearchShortcut.call(context, event)

			expect(context.$refs.searchMessages.focusInput).not.toHaveBeenCalled()
			expect(event.defaultPrevented).toBe(false)
		})

		it('keeps the native shortcut while typing in a field', () => {
			const context = createContext()
			const event = createEvent({ key: 'f', ctrlKey: true, cancelable: true }, document.createElement('textarea'))

			MailboxThread.methods.handleSearchShortcut.call(context, event)

			expect(context.$refs.searchMessages.focusInput).not.toHaveBeenCalled()
			expect(event.defaultPrevented).toBe(false)
		})
	})
})
