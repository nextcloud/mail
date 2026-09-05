/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createLocalVue, shallowMount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import Thread from '../../../components/Thread.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'
import useMainStore from '../../../store/mainStore.js'

vi.mock('@nextcloud/dialogs', async (importOriginal) => ({
	...await importOriginal(),
	showError: vi.fn(),
}))

const localVue = createLocalVue()

localVue.mixin(Nextcloud)

describe('Thread', () => {
	let store

	beforeEach(() => {
		setActivePinia(createPinia())

		store = useMainStore()
		store.getEnvelope = vi.fn().mockImplementation((id) => {
			if (id === 200) {
				return {
					accountId: 100,
					threadRootId: '123-456-789',
					mailboxId: 10,
				}
			}
			if (id === 300) {
				return {
					accountId: 200,
					threadRootId: '456-789-123',
					mailboxId: 20,
				}
			}
			if (id === 301) {
				return {
					accountId: 200,
					threadRootId: '456-789-123',
					mailboxId: 22,
				}
			}
			if (id === 302) {
				return {
					accountId: 200,
					threadRootId: '456-789-123',
					mailboxId: 23,
				}
			}
			return undefined
		})

		store.getEnvelopesByThreadRootId = vi.fn().mockImplementation((accountId, threadRootId) => {
			if (threadRootId === '123-456-789') {
				return [
					{
						accountId: 100,
						threadRootId: '123-456-789',
						mailboxId: 10,
						databaseId: 1001,
						from: [],
						to: [],
						cc: [],
					},
					{
						accountId: 100,
						threadRootId: '123-456-789',
						mailboxId: 11,
						databaseId: 1002,
						from: [],
						to: [],
						cc: [],
					},
					{
						accountId: 100,
						threadRootId: '123-456-789',
						mailboxId: 10,
						databaseId: 1003,
						from: [],
						to: [],
						cc: [],
					},
				]
			}
			if (threadRootId === '456-789-123') {
				return [
					{
						accountId: 200,
						threadRootId: '456-789-123',
						mailboxId: 20,
						databaseId: 2001,
						from: [],
						to: [],
						cc: [],
					},
					{
						accountId: 200,
						threadRootId: '456-789-123',
						mailboxId: 21,
						databaseId: 2002,
						from: [],
						to: [],
						cc: [],
					},
					{
						accountId: 200,
						threadRootId: '456-789-123',
						mailboxId: 20,
						databaseId: 2003,
						from: [],
						to: [],
						cc: [],
					},
					{
						accountId: 200,
						threadRootId: '456-789-123',
						mailboxId: 22,
						databaseId: 2004,
						from: [],
						to: [],
						cc: [],
					},
					{
						accountId: 200,
						threadRootId: '456-789-123',
						mailboxId: 23,
						databaseId: 2005,
						from: [],
						to: [],
						cc: [],
					},
				]
			}
			return []
		})

		store.getMailbox = vi.fn().mockImplementation((id) => {
			if (id === 10) {
				return {
					databaseId: 10,
					name: 'INBOX',
					accountId: 100,
					specialRole: 'inbox',
				}
			}
			if (id === 20) {
				return {
					databaseId: 20,
					name: 'INBOX',
					accountId: 200,
					specialRole: 'inbox',
				}
			}
			if (id === 22) {
				return {
					databaseId: 22,
					name: 'Trash',
					accountId: 200,
					specialRole: 'trash',
				}
			}
			if (id === 23) {
				return {
					databaseId: 23,
					name: 'Junk',
					accountId: 200,
					specialRole: 'junk',
				}
			}
			return undefined
		})

		store.getMailboxes = vi.fn().mockImplementation((accountId) => {
			if (accountId === 100) {
				return [
					{
						databaseId: 10,
						name: 'INBOX',
						specialRole: 'inbox',
					},
					{
						databaseId: 11,
						name: 'Test',
						specialRole: '',
					},
				]
			}
			if (accountId === 200) {
				return [
					{
						databaseId: 20,
						name: 'INBOX',
						specialRole: 'inbox',
					},
					{
						databaseId: 21,
						name: 'Test',
						specialRole: '',
					},
					{
						databaseId: 22,
						name: 'Trash',
						specialRole: 'trash',
					},
					{
						databaseId: 23,
						name: 'Junk',
						specialRole: 'junk',
					},
				]
			}
			return []
		})
	})

	it('empty list when envelope not found', () => {
		const view = shallowMount(Thread, {
			mocks: {
				$route: {
					params: {
						threadId: 100,
					},
				},
			},
			store,
			localVue,
		})

		expect(view.vm.thread).toHaveLength(0)
	})

	it('show messages for thread root from inbox and test folder', () => {
		const view = shallowMount(Thread, {
			mocks: {
				$route: {
					params: {
						threadId: 200,
					},
				},
			},
			store,
			localVue,
		})

		expect(view.vm.thread).toHaveLength(3)
	})

	it('show messages for thread root from inbox and test folder, ignore trash', () => {
		const view = shallowMount(Thread, {
			mocks: {
				$route: {
					params: {
						threadId: 300,
					},
				},
			},
			store,
			localVue,
		})

		expect(view.vm.thread).toHaveLength(3)
	})

	it('show messages for thread root only from trash', () => {
		const view = shallowMount(Thread, {
			mocks: {
				$route: {
					params: {
						threadId: 301,
					},
				},
			},
			store,
			localVue,
		})

		const envelopes = view.vm.thread
		expect(envelopes).toHaveLength(1)
		expect(envelopes[0].mailboxId).toBe(22)
	})

	it('show messages for thread root only from junk', () => {
		const view = shallowMount(Thread, {
			mocks: {
				$route: {
					params: {
						threadId: 302,
					},
				},
			},
			store,
			localVue,
		})

		const envelopes = view.vm.thread
		expect(envelopes).toHaveLength(1)
		expect(envelopes[0].mailboxId).toBe(23)
	})

	describe('printing a message', () => {
		let parent
		let renderedMessages

		const mountThread = () => {
			const view = shallowMount(Thread, {
				mocks: {
					$route: {
						params: {
							threadId: 200,
						},
					},
				},
				store,
				localVue,
			})
			view.vm.$refs.envelopeRefs = view.vm.thread.map((envelope) => ({
				envelope,
				$el: document.createElement('div'),
			}))
			return view
		}

		/**
		 * Element of a rendered message component, attached to the document so
		 * that anything below it has a document of its own.
		 *
		 * @return {HTMLElement} the component's element
		 */
		const messageElement = () => {
			const el = document.createElement('div')
			document.body.appendChild(el)
			renderedMessages.push(el)
			return el
		}

		/**
		 * Stand in for a rendered HTML message: a component whose element holds
		 * the message iframe.
		 *
		 * @param {number} databaseId database id of the message's envelope
		 * @param {string} color color the message's stylesheet paints with
		 * @param {string} text the message's body text
		 * @return {object} the envelope component stub
		 */
		const renderedMessage = (databaseId, color, text) => {
			const el = messageElement()

			const iframe = document.createElement('iframe')
			el.appendChild(iframe)
			iframe.contentDocument.open()
			iframe.contentDocument.write(`<html><head><style>p { color: ${color}; }</style></head><body><p>${text}</p></body></html>`)
			iframe.contentDocument.close()

			return { envelope: { databaseId }, $el: el }
		}

		/**
		 * Stand in for a rendered plain text message: a component whose element
		 * holds the message body as light DOM, the way the app renders it.
		 *
		 * @param {number} databaseId database id of the message's envelope
		 * @param {string} text the message's body text
		 * @return {object} the envelope component stub
		 */
		const renderedPlainTextMessage = (databaseId, text) => {
			const el = messageElement()

			const container = document.createElement('div')
			container.id = 'message-container'
			container.textContent = text
			el.appendChild(container)

			return { envelope: { databaseId }, $el: el }
		}

		beforeEach(() => {
			renderedMessages = []
			parent = document.createElement('div')
			document.body.appendChild(parent)
		})

		afterEach(() => {
			parent.remove()
			renderedMessages.forEach((el) => el.remove())
		})

		it('opens a message with its header', () => {
			const view = mountThread()

			view.vm.appendPrintMessage(parent, 0)

			expect(parent.querySelectorAll('.print-message')).toHaveLength(1)
			expect(parent.querySelector('.print-message').firstElementChild.className).toBe('print-message-header')
		})

		it('gives every message a shadow root of its own so they cannot restyle each other', () => {
			const view = mountThread()
			view.vm.$refs.envelopeRefs = [
				renderedMessage(1001, 'red', 'first'),
				renderedMessage(1002, 'blue', 'second'),
				{ envelope: { databaseId: 1003 }, $el: document.createElement('div') },
			]

			view.vm.appendPrintMessage(parent, 0)
			view.vm.appendPrintMessage(parent, 1)

			const hosts = parent.querySelectorAll('.print-message-content')
			expect(hosts).toHaveLength(2)
			expect(hosts[0].shadowRoot.querySelector('p').textContent).toBe('first')
			expect(hosts[0].shadowRoot.querySelector('style').textContent).toContain('red')
			expect(hosts[1].shadowRoot.querySelector('p').textContent).toBe('second')
			expect(hosts[1].shadowRoot.querySelector('style').textContent).toContain('blue')
		})

		it('prints the messages as part of the document, so they follow whatever paper is picked', () => {
			const view = mountThread()
			view.vm.$refs.envelopeRefs = [renderedMessage(1001, 'red', 'first')]

			view.vm.appendPrintMessage(parent, 0)

			expect(parent.querySelector('iframe')).toBeNull()
			expect(parent.querySelector('.print-message-content').style.height).toBe('')
		})

		it('keeps the header out of reach of the message styles', () => {
			const view = mountThread()
			view.vm.$refs.envelopeRefs = [renderedMessage(1001, 'red', 'first')]

			view.vm.appendPrintMessage(parent, 0)

			const message = parent.querySelector('.print-message')
			expect(message.firstElementChild.className).toBe('print-message-header')
			expect(message.querySelector('.print-message-content').shadowRoot.querySelector('.print-message-header')).toBeNull()
		})

		it('keeps the messages own styles out of the print document', () => {
			const view = mountThread()
			view.vm.$refs.envelopeRefs = [renderedMessage(1001, 'red', 'first')]

			view.vm.appendPrintMessage(parent, 0)

			expect(parent.querySelectorAll('style')).toHaveLength(0)
		})

		it('renders a plain text message the same way, so both kinds print alike', () => {
			const view = mountThread()
			view.vm.$refs.envelopeRefs = [
				renderedPlainTextMessage(1001, 'plain'),
				renderedMessage(1002, 'blue', 'html'),
			]

			view.vm.appendPrintMessage(parent, 0)
			view.vm.appendPrintMessage(parent, 1)

			const hosts = parent.querySelectorAll('.print-message-content')
			expect(hosts).toHaveLength(2)
			expect(hosts[0].shadowRoot.textContent).toContain('plain')
			expect(hosts[1].shadowRoot.querySelector('p').textContent).toBe('html')
		})

		it('prints the header alone when the message frame belongs to another origin', () => {
			const view = mountThread()
			const el = messageElement()
			const iframe = document.createElement('iframe')
			el.appendChild(iframe)
			Object.defineProperty(iframe, 'contentDocument', { value: null })
			view.vm.$refs.envelopeRefs = [
				{ envelope: { databaseId: 1001 }, $el: el },
				renderedMessage(1002, 'blue', 'second'),
			]

			view.vm.appendPrintMessage(parent, 0)
			view.vm.appendPrintMessage(parent, 1)

			const messages = parent.querySelectorAll('.print-message')
			expect(messages).toHaveLength(2)
			expect(messages[0].querySelector('.print-message-header')).not.toBeNull()
			expect(messages[0].querySelector('.print-message-content')).toBeNull()
			expect(messages[1].querySelector('.print-message-content').shadowRoot.querySelector('p').textContent).toBe('second')
		})

		it('pairs a message with its own body, whatever order the refs came in', () => {
			const view = mountThread()
			view.vm.$refs.envelopeRefs = [
				renderedMessage(1002, 'blue', 'second'),
				{ envelope: { databaseId: 1003 }, $el: document.createElement('div') },
				renderedMessage(1001, 'red', 'first'),
			]

			view.vm.appendPrintMessage(parent, 0)
			view.vm.appendPrintMessage(parent, 1)

			const hosts = parent.querySelectorAll('.print-message-content')
			expect(hosts[0].shadowRoot.querySelector('p').textContent).toBe('first')
			expect(hosts[1].shadowRoot.querySelector('p').textContent).toBe('second')
		})

		it('prints the messages in thread order, not in the order they were expanded', () => {
			const view = mountThread()
			view.vm.expandedThreads = [1003, 1001]

			expect(view.vm.expandedIndices).toEqual([0, 2])
		})
	})

	describe('print shortcut', () => {
		const mountThread = (printable = true) => {
			const view = shallowMount(Thread, {
				mocks: {
					$route: {
						params: {
							threadId: 200,
						},
					},
				},
				store,
				localVue,
			})
			view.vm.$refs.envelopeRefs = view.vm.thread.map((envelope) => ({
				envelope,
				printable,
				$el: document.createElement('div'),
			}))
			return view
		}

		const printShortcut = () => ({
			code: 'KeyP',
			key: 'p',
			ctrlKey: true,
			metaKey: false,
			preventDefault: vi.fn(),
		})

		it('prints the thread', async () => {
			const view = mountThread()
			const printMessages = vi.spyOn(view.vm, 'printMessages').mockResolvedValue()

			await view.vm.handleKeyDown(printShortcut())

			expect(printMessages).toHaveBeenCalledWith([0, 1, 2])
		})

		it('prints the thread when the shortcut came from inside a message frame', async () => {
			const view = mountThread()
			const printMessages = vi.spyOn(view.vm, 'printMessages').mockResolvedValue()

			await view.vm.printThread()

			expect(printMessages).toHaveBeenCalledWith([0, 1, 2])
		})

		it('expands the collapsed messages so there is something to print of them', async () => {
			const view = mountThread()
			vi.spyOn(view.vm, 'printMessages').mockResolvedValue()

			await view.vm.handleKeyDown(printShortcut())

			expect(view.vm.expandedThreads).toEqual(expect.arrayContaining([1001, 1002, 1003]))
		})

		it('waits for the messages it expanded to be rendered before printing', async () => {
			const view = mountThread(false)
			const printMessages = vi.spyOn(view.vm, 'printMessages').mockResolvedValue()

			const printed = view.vm.printThread()

			await new Promise((resolve) => setTimeout(resolve, 150))
			expect(printMessages).not.toHaveBeenCalled()

			view.vm.$refs.envelopeRefs.forEach((component) => {
				component.printable = true
			})
			await printed
			expect(printMessages).toHaveBeenCalledWith([0, 1, 2])
		})

		it('leaves the shortcut alone when it is not the print one', async () => {
			const view = mountThread()
			const printMessages = vi.spyOn(view.vm, 'printMessages').mockResolvedValue()
			const event = { ...printShortcut(), code: 'KeyA', key: 'a' }

			await view.vm.handleKeyDown(event)

			expect(printMessages).not.toHaveBeenCalled()
			expect(event.preventDefault).not.toHaveBeenCalled()
		})

		it('does not stage the thread again while a print is still going on', async () => {
			const view = mountThread()
			const printMessages = vi.spyOn(view.vm, 'printMessages').mockResolvedValue()
			const expandedThreads = [...view.vm.expandedThreads]
			view.vm.isolatedPrint = true

			await view.vm.handleKeyDown(printShortcut())

			expect(printMessages).not.toHaveBeenCalled()
			expect(view.vm.expandedThreads).toEqual(expandedThreads)
		})
	})

	describe('browser print', () => {
		const notice = () => document.getElementById('mail-browser-print-notice')

		const mountThread = () => shallowMount(Thread, {
			mocks: {
				$route: {
					params: {
						threadId: 200,
					},
				},
			},
			store,
			localVue,
		})

		afterEach(() => {
			notice()?.remove()
		})

		it('prints a notice instead of the thread, because the page cannot print it correctly', () => {
			mountThread()

			expect(notice()).not.toBeNull()
			expect(notice().textContent).toContain('not supported')
		})

		it('never copies a message into the app document', () => {
			const view = mountThread()
			view.vm.$refs.envelopeRefs = view.vm.thread.map((envelope) => ({
				envelope,
				$el: document.createElement('div'),
			}))

			expect(notice().querySelector('.print-message')).toBeNull()
			expect(document.querySelector('.print-message')).toBeNull()
		})

		it('brings its own stylesheet, which hides the app for print media', () => {
			mountThread()

			const style = notice().querySelector('style').textContent
			expect(style).toContain('@media print')
			expect(style).toContain('body > *:not(#mail-browser-print-notice):not(#mail-print-frame) { display: none !important; }')
		})

		it('is shown for print media only, so it never takes part in the app', () => {
			mountThread()

			const style = notice().querySelector('style').textContent
			expect(style.slice(0, style.indexOf('@media print'))).toContain('#mail-browser-print-notice { display: none; }')
		})

		it('does not put up a second notice', () => {
			mountThread()
			mountThread()

			expect(document.querySelectorAll('#mail-browser-print-notice')).toHaveLength(1)
		})

		it('cleans up the notice when the thread goes away', () => {
			const view = mountThread()

			view.destroy()

			expect(notice()).toBeNull()
		})
	})

	describe('folding the subject away', () => {
		const BAND = 48
		const LINES = 3
		let watched

		beforeEach(() => {
			watched = []
			global.ResizeObserver = class {
				observe(element) {
					watched.push(element)
				}

				unobserve() {}
				disconnect() {}
			}
		})

		// jsdom lays nothing out, so the subject reports the height it is given
		const setSubjectHeight = (view, height) => Object.defineProperty(
			view.vm.$refs.threadSubject,
			'scrollHeight',
			{ value: height, configurable: true },
		)

		const mountThread = () => {
			const view = shallowMount(Thread, {
				mocks: {
					$route: {
						params: {
							threadId: 200,
						},
					},
				},
				store,
				localVue,
			})
			setSubjectHeight(view, BAND * LINES)
			return view
		}

		afterEach(() => {
			document.getElementById('mail-browser-print-notice')?.remove()
		})

		/**
		 * Put the thread at a scroll offset. The marker scrolls with the thread
		 * while the header stays pinned, so the gap between them is the offset.
		 *
		 * @param {object} view the mounted thread
		 * @param {number} scrolled pixels scrolled down from the top
		 */
		const scrollTo = (view, scrolled) => {
			view.vm.$refs.headerSentinel.getBoundingClientRect = () => ({ top: -scrolled, height: BAND })
			view.vm.$refs.threadHeader.getBoundingClientRect = () => ({ top: 0 })
			view.vm.updateHeaderCollapse()
		}

		const header = (view) => view.find('#mail-thread-header')

		it('leaves the subject whole at the top of the thread', () => {
			const view = mountThread()

			scrollTo(view, 0)

			expect(view.vm.headerCollapse).toBe(0)
		})

		it('folds the subject as far as the thread has scrolled', () => {
			const view = mountThread()

			scrollTo(view, BAND / 4)

			expect(view.vm.headerCollapse).toBe(0.25)
		})

		it('stops folding once the subject is down to one line', () => {
			const view = mountThread()

			scrollTo(view, BAND * 5)

			expect(view.vm.headerCollapse).toBe(1)
		})

		it('unfolds again on the way back up', () => {
			const view = mountThread()
			scrollTo(view, BAND)

			scrollTo(view, BAND / 2)

			expect(view.vm.headerCollapse).toBe(0.5)
		})

		it('carries the fold into the header, for the subject to follow', async () => {
			const view = mountThread()

			scrollTo(view, BAND / 2)
			await view.vm.$nextTick()

			expect(header(view).element.style.getPropertyValue('--subject-collapse')).toBe('0.5')
		})

		it('adds the ellipsis only once the subject is one line', async () => {
			const view = mountThread()

			scrollTo(view, BAND / 2)
			await view.vm.$nextTick()
			expect(header(view).classes()).not.toContain('mail-thread-header--stuck')

			scrollTo(view, BAND)
			await view.vm.$nextTick()
			expect(header(view).classes()).toContain('mail-thread-header--stuck')
		})

		it('measures the subject while every line of it is shown', async () => {
			const view = mountThread()

			view.vm.measureSubject()
			await view.vm.$nextTick()

			expect(header(view).element.style.getPropertyValue('--subject-height')).toBe(`${BAND * LINES}px`)
		})

		it('tells the marker how tall the subject is, since it is what sets the pace', async () => {
			const view = mountThread()

			view.vm.measureSubject()
			await view.vm.$nextTick()

			const marker = view.find('.thread-header-sentinel')
			expect(marker.element.style.getPropertyValue('--subject-height')).toBe(`${BAND * LINES}px`)
		})

		it('does not re-measure a subject that is already folding', () => {
			const view = mountThread()
			view.vm.measureSubject()
			scrollTo(view, BAND / 2)

			setSubjectHeight(view, BAND)
			view.vm.measureSubject()

			expect(view.vm.subjectHeight).toBe(`${BAND * LINES}px`)
		})

		it('watches a subject that only shows up once the thread has been fetched', async () => {
			// the thread is still on its way, so the view is a loading screen
			const view = shallowMount(Thread, {
				mocks: {
					$route: {
						params: {
							threadId: 100,
						},
					},
				},
				store,
				localVue,
			})
			expect(view.vm.$refs.threadSubject).toBeUndefined()
			expect(watched).toHaveLength(0)

			view.vm.loading = false
			await view.vm.$nextTick()

			expect(watched).toContain(view.vm.$refs.threadSubject)
		})

		it('follows the scroll from the start, before there is a subject to fold', () => {
			const listening = vi.spyOn(document, 'addEventListener')

			const view = shallowMount(Thread, {
				mocks: {
					$route: {
						params: {
							threadId: 100,
						},
					},
				},
				store,
				localVue,
			})

			expect(listening).toHaveBeenCalledWith('scroll', view.vm.onThreadScroll, { capture: true, passive: true })
			listening.mockRestore()
		})

		it('stops following the scroll once the thread is gone', () => {
			const view = mountThread()
			const removed = vi.spyOn(document, 'removeEventListener')

			view.destroy()

			expect(removed).toHaveBeenCalledWith('scroll', view.vm.onThreadScroll, true)
			removed.mockRestore()
		})
	})
})
