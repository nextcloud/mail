/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { Paragraph } from 'ckeditor5'
import MailPlugin from '../../../ckeditor/mail/MailPlugin.js'
import VirtualTestEditor from '../../virtualtesteditor.js'

describe('MailPlugin', () => {
	it('Add margin:0 to paragraph', async () => {
		const text = '<p>bonjour bonjour</p>'
		const expected = '<p style="margin:0;">bonjour bonjour</p>'

		const editor = await VirtualTestEditor.create({
			licenseKey: 'GPL',
			initialData: text,
			plugins: [Paragraph, MailPlugin],
		})

		expect(editor.getData()).toEqual(expected)
	})
})
