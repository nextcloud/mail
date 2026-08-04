/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createLocalVue, shallowMount } from '@vue/test-utils'
import MessageHTMLBody from '../../../components/MessageHTMLBody.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'

vi.mock('@iframe-resizer/parent', () => ({ default: vi.fn() }))
vi.mock('@nextcloud/initial-state', () => ({ loadState: vi.fn().mockReturnValue(false) }))
vi.mock('../../../service/AiIntergrationsService.js', () => ({ needsTranslation: vi.fn().mockResolvedValue(false) }))
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
