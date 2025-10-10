/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import VirtualTestEditor from '../../virtualtesteditor.js'
import ParagraphPlugin from '@ckeditor/ckeditor5-paragraph/src/paragraph'
import PickerPlugin from '../../../ckeditor/smartpicker/PickerPlugin.js'

describe('PickerPlugin', () => {

	it('Insert an item and remove trigger symbol', async () => {
		const text = '<p>Hello /</p>'
		const expected = '<p>Hello I am a link</p>'

		const editor = await VirtualTestEditor.create({
			licenseKey: 'GPL',
			initialData: text,
			plugins: [ParagraphPlugin, PickerPlugin],
		})

		editor.model.change(writer => {
			writer.setSelection(editor.model.document.getRoot(), 'end')
		})

		editor.execute('insertItem',
			'I am a link',
			'/',
		)

		expect(editor.getData()).toEqual(expected)
	})

})
