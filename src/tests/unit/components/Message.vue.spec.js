/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createLocalVue, shallowMount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import Message from '../../../components/Message.vue'
import MessageHTMLBody from '../../../components/MessageHTMLBody.vue'
import MessagePlainTextBody from '../../../components/MessagePlainTextBody.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'

vi.mock('@nextcloud/router', () => ({
	generateUrl: vi.fn().mockReturnValue('/message/html'),
}))

const localVue = createLocalVue()
localVue.mixin(Nextcloud)

describe('Message', () => {
	beforeEach(() => {
		setActivePinia(createPinia())
	})

	const mountMessage = (showTextualVersion = false) => shallowMount(Message, {
		localVue,
		propsData: {
			envelope: { databaseId: 123 },
			message: {
				hasHtmlBody: true,
				body: '<p>HTML body</p>',
				plainBody: 'Plain body',
				signature: 'Signature',
				phishingDetails: { warning: false, checks: [] },
				smime: { isSigned: false },
				scheduling: [],
				attachments: [],
				from: [],
				isPgpMimeEncrypted: false,
			},
			replyButtonLabel: 'Reply',
			showTextualVersion,
		},
	})

	it('shows the HTML body by default', () => {
		const view = mountMessage()

		expect(view.findComponent(MessageHTMLBody).exists()).toBe(true)
		expect(view.findComponent(MessagePlainTextBody).exists()).toBe(false)
		expect(view.classes()).toContain('mail-message-body-html')
	})

	it('shows the MIME plain body when requested', () => {
		const view = mountMessage(true)

		const plainBody = view.findComponent(MessagePlainTextBody)
		expect(view.findComponent(MessageHTMLBody).exists()).toBe(false)
		expect(plainBody.props('body')).toBe('Plain body')
		expect(plainBody.props('signature')).toBe('Signature')
		expect(view.classes()).not.toContain('mail-message-body-html')
	})
})
