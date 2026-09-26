/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createLocalVue, shallowMount } from '@vue/test-utils'
import BlockedContentWarning from '../../../components/BlockedContentWarning.vue'
import MessageHTMLBody from '../../../components/MessageHTMLBody.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'
import { trustSender } from '../../../service/TrustedSenderService.js'

vi.mock('@iframe-resizer/parent', () => ({ default: vi.fn() }))
vi.mock('@nextcloud/initial-state', () => ({ loadState: vi.fn().mockReturnValue(false) }))
vi.mock('../../../util/languageDetection.ts', () => ({ detectForeignLanguage: vi.fn().mockResolvedValue(null) }))
vi.mock('../../../service/TrustedSenderService.js', () => ({ trustSender: vi.fn() }))

const localVue = createLocalVue()

localVue.mixin(Nextcloud)

describe('MessageHTMLBody', () => {
	// Attached, because only an iframe that is part of a document has a
	// `contentDocument` to listen on.
	const mountBody = () => shallowMount(MessageHTMLBody, {
		attachTo: document.body,
		propsData: {
			url: 'https://cloud.example.com/apps/mail/api/messages/1/html',
			message: {
				databaseId: 1,
				from: [{ email: 'alice@example.com' }],
				isSenderTrusted: false,
			},
		},
		localVue,
	})

	const keydown = (key, modifiers = { ctrlKey: true }) => new KeyboardEvent('keydown', {
		key,
		code: `Key${key.toUpperCase()}`,
		cancelable: true,
		...modifiers,
	})

	// The frame starts out empty here, because jsdom does not fetch its source
	const loadFrameWithBlockedImage = (view) => {
		const doc = view.vm.getIframeDoc()
		doc.write('<img data-original-src="https://example.com/tracker.gif">')
		doc.close()
		view.vm.onMessageFrameLoad()
	}

	describe('blocked content', () => {
		beforeEach(() => {
			trustSender.mockClear()
		})

		it('warns about images the message would have loaded', async () => {
			const view = mountBody()

			loadFrameWithBlockedImage(view)
			await view.vm.$nextTick()

			expect(view.findComponent(BlockedContentWarning).exists()).toBe(true)
		})

		it('keeps the warning out of the message body, where it would sit on the message\'s own background', async () => {
			const view = mountBody()

			loadFrameWithBlockedImage(view)
			await view.vm.$nextTick()

			const body = view.find('.html-message-body__content')
			expect(body.findComponent(BlockedContentWarning).exists()).toBe(false)
		})

		it('reveals the images for this message only', async () => {
			const view = mountBody()
			loadFrameWithBlockedImage(view)
			await view.vm.$nextTick()

			view.findComponent(BlockedContentWarning).vm.$emit('show')
			await view.vm.$nextTick()

			expect(view.vm.getIframeDoc().querySelector('img').getAttribute('src'))
				.toBe('https://example.com/tracker.gif')
			expect(trustSender).not.toHaveBeenCalled()
			expect(view.findComponent(BlockedContentWarning).exists()).toBe(false)
		})

		it('remembers a sender the reader trusts', async () => {
			const view = mountBody()
			loadFrameWithBlockedImage(view)
			await view.vm.$nextTick()

			view.findComponent(BlockedContentWarning).vm.$emit('trust-sender')
			await view.vm.$nextTick()

			expect(trustSender).toHaveBeenCalledWith('alice@example.com', 'individual', true)
			expect(view.findComponent(BlockedContentWarning).exists()).toBe(false)
		})

		it('remembers a domain the reader trusts', async () => {
			const view = mountBody()
			loadFrameWithBlockedImage(view)
			await view.vm.$nextTick()

			view.findComponent(BlockedContentWarning).vm.$emit('trust-domain')
			await view.vm.$nextTick()

			expect(trustSender).toHaveBeenCalledWith('example.com', 'domain', true)
			expect(view.findComponent(BlockedContentWarning).exists()).toBe(false)
		})
	})

	describe('iframe sandbox', () => {
		it('allows scripts so the injected resizer can size the frame', () => {
			const view = mountBody()

			const sandbox = view.vm.$refs.iframe.getAttribute('sandbox').split(' ')

			expect(sandbox).toContain('allow-scripts')
			expect(sandbox).toContain('allow-same-origin')
		})
	})

	describe('print shortcut', () => {
		it('takes the shortcut from inside the frame, where the app window cannot see it', () => {
			const view = mountBody()
			view.vm.onMessageFrameLoad()

			const event = keydown('p')
			view.vm.getIframeDoc().dispatchEvent(event)

			expect(view.emitted('print-shortcut')).toHaveLength(1)
		})

		it('keeps the browser from printing the page itself', () => {
			const view = mountBody()
			view.vm.onMessageFrameLoad()

			const event = keydown('p')
			view.vm.getIframeDoc().dispatchEvent(event)

			expect(event.defaultPrevented).toBe(true)
		})

		it('leaves every other key in the message alone', () => {
			const view = mountBody()
			view.vm.onMessageFrameLoad()

			const event = keydown('a')
			view.vm.getIframeDoc().dispatchEvent(event)

			expect(view.emitted('print-shortcut')).toBeUndefined()
			expect(event.defaultPrevented).toBe(false)
		})

		it('does not take the shortcut twice when the frame loads again', () => {
			const view = mountBody()
			view.vm.onMessageFrameLoad()
			view.vm.onMessageFrameLoad()

			view.vm.getIframeDoc().dispatchEvent(keydown('p'))

			expect(view.emitted('print-shortcut')).toHaveLength(1)
		})

		it('stops listening once the message is gone', () => {
			const view = mountBody()
			view.vm.onMessageFrameLoad()
			const doc = view.vm.getIframeDoc()
			view.vm.$refs.iframe.iFrameResizer = { close: vi.fn() }

			view.destroy()
			doc.dispatchEvent(keydown('p'))

			expect(view.emitted('print-shortcut')).toBeUndefined()
		})
	})
})
