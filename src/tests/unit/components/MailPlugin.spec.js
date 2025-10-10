/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import VirtualTestEditor from '../../virtualtesteditor.js'
import ParagraphPlugin from '@ckeditor/ckeditor5-paragraph/src/paragraph'
import MailPlugin from '../../../ckeditor/mail/MailPlugin.js'

describe('MailPlugin', () => {

	it('Add margin:0 to paragraph', async() => {
		const text = '<p>bonjour bonjour</p>'
		const expected = '<p style="margin:0;">bonjour bonjour</p>'

		const editor = await VirtualTestEditor.create({
			licenseKey: 'GPL',
			initialData: text,
			plugins: [ParagraphPlugin, MailPlugin],
		})

		expect(editor.getData()).toEqual(expected)
	})

})
