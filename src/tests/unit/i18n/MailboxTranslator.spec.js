/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { translate } from '../../../i18n/MailboxTranslator.js'

describe('MailboxTranslator', () => {
	it('translates the inbox', () => {
		const mailbox = {
			name: 'INBOX',
			specialUse: ['inbox'],
		}

		const name = translate(mailbox)

		expect(name).toEqual('Inbox')
	})

	it('does not translate an arbitrary mailbox', () => {
		const mailbox = {
			name: 'Newsletters',
			displayName: 'Newsletters',
			specialUse: [],
		}

		const name = translate(mailbox)

		expect(name).toEqual('Newsletters')
	})
})
