/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import NewMessageModal from '../../../components/NewMessageModal.vue'
import { EDITOR_MODE_TEXT } from '../../../store/constants.js'
import useMainStore from '../../../store/mainStore.js'

const account = {
	id: 1,
	isUnified: false,
	name: 'Jane Doe',
	emailAddress: 'jane@example.org',
	editorMode: EDITOR_MODE_TEXT,
	signature: '',
	signatureAboveQuote: false,
	smimeCertificateId: null,
	connectionStatus: true,
	aliases: [],
}

export const OneSender = {
	created() {
		const store = useMainStore()

		store.$patch({
			textBlocksFetched: true,
			myTextBlocks: [],
			sharedTextBlocks: [],
			smimeCertificates: [],
			preferences: { 'reply-mode': 'top' },
			accountsUnmapped: { [account.id]: account },
			accountList: [account.id],
		})

		// Sets newMessage, composerSessionId and showMessageComposer together.
		store.startComposerSessionMutation({
			type: 'imap',
			data: {
				accountId: account.id,
				aliasId: null,
				to: [],
				cc: [],
				bcc: [],
				subject: '',
				attachments: [],
				bodyPlain: '',
				isHtml: false,
				sendAt: 0,
			},
			forwardedMessages: [],
		})
	},

	render(h) {
		return h('div', [
			h(NewMessageModal, { props: { accounts: [account] } }),
		])
	},
}
