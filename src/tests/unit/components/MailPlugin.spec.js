/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { ListEditing, Paragraph, ShiftEnter } from 'ckeditor5'
import MailPlugin from '../../../ckeditor/mail/MailPlugin.js'
import VirtualTestEditor from '../../virtualtesteditor.js'

/**
 * Paste clipboard data into the editor
 *
 * @param {VirtualTestEditor} editor the editor
 * @param {Record<string, string>} data clipboard data by MIME type
 */
function paste(editor, data) {
	editor.editing.view.document.fire('paste', {
		preventDefault: () => {},
		stopPropagation: () => {},
		dataTransfer: {
			getData: (type) => data[type] ?? '',
		},
	})
}

/**
 * Copy the whole editor content
 *
 * @param {VirtualTestEditor} editor the editor
 * @return {Record<string, string>} clipboard data by MIME type
 */
function copyAll(editor) {
	const data = {}
	editor.model.change((writer) => {
		writer.setSelection(editor.model.document.getRoot(), 'in')
	})
	editor.editing.view.document.fire('copy', {
		dataTransfer: {
			setData: (type, value) => {
				data[type] = value
			},
			getData: (type) => data[type] ?? '',
		},
		preventDefault: () => {},
	})
	return data
}

/**
 * @param {...string} lines the content of each paragraph
 * @return {string} the editor data for these paragraphs
 */
function paragraphs(...lines) {
	return lines.map((line) => `<p style="margin:0;">${line || '&nbsp;'}</p>`).join('')
}

describe('MailPlugin', () => {
	let editor

	beforeEach(async () => {
		editor = await VirtualTestEditor.create({
			licenseKey: 'GPL',
			plugins: [Paragraph, ShiftEnter, ListEditing, MailPlugin],
		})
		// There is no DOM to scroll to the pasted content
		editor.editing.view.scrollToTheSelection = () => {}
	})

	it('Add margin:0 to paragraph', async () => {
		editor.setData('<p>bonjour bonjour</p>')

		expect(editor.getData()).toEqual('<p style="margin:0;">bonjour bonjour</p>')
	})

	describe('pasting plain text', () => {
		it('pastes one paragraph per line', () => {
			paste(editor, { 'text/plain': 'Testing testing\nThis is second line\n\nHere is another paragraph\n\n\nAnd another paragraph' })

			expect(editor.getData()).toEqual(paragraphs('Testing testing', 'This is second line', '', 'Here is another paragraph', '', '', 'And another paragraph'))
		})

		it('handles Windows line endings', () => {
			paste(editor, { 'text/plain': 'One\r\n\r\nTwo' })

			expect(editor.getData()).toEqual(paragraphs('One', '', 'Two'))
		})

		it('keeps text without line breaks in the current paragraph', () => {
			editor.setData('<p>ab</p>')
			editor.model.change((writer) => {
				writer.setSelection(editor.model.document.getRoot().getChild(0), 1)
			})

			paste(editor, { 'text/plain': 'x' })

			expect(editor.getData()).toEqual(paragraphs('axb'))
		})

		it('splits the current paragraph like pressing Enter', () => {
			editor.setData('<p>ab</p>')
			editor.model.change((writer) => {
				writer.setSelection(editor.model.document.getRoot().getChild(0), 1)
			})

			paste(editor, { 'text/plain': 'x\n\ny' })

			expect(editor.getData()).toEqual(paragraphs('ax', '', 'yb'))
		})
	})

	describe('pasting HTML', () => {
		it('adds a blank line between paragraphs with margins', () => {
			paste(editor, { 'text/html': '<p>Lorem ipsum</p>\n<p>Dolor sit amet</p>', 'text/plain': 'Lorem ipsum\n\nDolor sit amet' })

			expect(editor.getData()).toEqual(paragraphs('Lorem ipsum', '', 'Dolor sit amet'))
		})

		it('finds paragraphs nested in other elements', () => {
			paste(editor, { 'text/html': '<div><p>Lorem ipsum</p><p>Dolor sit amet</p></div>' })

			expect(editor.getData()).toEqual(paragraphs('Lorem ipsum', '', 'Dolor sit amet'))
		})

		it.each([
			'<p style="margin:0;">Line one</p><p style="margin:0;">Line two</p>',
			'<p style="margin-top:0pt;margin-bottom:0pt;">Line one</p><p style="margin-top:0pt;margin-bottom:0pt;">Line two</p>',
			'<p style="margin:0 0 0 1em;">Line one</p><p style="margin:0px;">Line two</p>',
			'<div>Line one</div><div>Line two</div>',
		])('does not add a blank line between lines without space between them: %s', (html) => {
			paste(editor, { 'text/html': html })

			expect(editor.getData()).toEqual(paragraphs('Line one', 'Line two'))
		})

		it('adds a blank line when only one side has no margin', () => {
			paste(editor, { 'text/html': '<p style="margin-bottom:0;">Line one</p><p>Line two</p>' })

			expect(editor.getData()).toEqual(paragraphs('Line one', '', 'Line two'))
		})

		it('does not add a blank line between lines that are adjacent in the plain text', () => {
			paste(editor, {
				'text/html': '<p style="margin-bottom: 0in">One</p><p style="margin-bottom: 0in">Two</p><p style="margin-bottom: 0in"><br></p><p style="margin-bottom: 0in">Three</p>',
				'text/plain': 'One\nTwo\n\nThree',
			})

			expect(editor.getData()).toEqual(paragraphs('One', 'Two', '', 'Three'))
		})

		it('turns line breaks between blocks into blank lines', () => {
			paste(editor, { 'text/html': '<b><p style="margin:0pt;">One</p><br><p style="margin:0pt;">Two</p><br><br><p style="margin:0pt;">Three</p></b>' })

			expect(editor.getData()).toEqual(paragraphs('One', '', 'Two', '', '', 'Three'))
		})

		it('keeps divs with only a line break as blank lines', () => {
			paste(editor, { 'text/html': '<div dir="ltr">Hi,<div><br></div><div>first line</div><div><br></div><div><br></div><div>Bye</div></div>' })

			expect(editor.getData()).toEqual(paragraphs('Hi,', '', 'first line', '', '', 'Bye'))
		})

		it('drops a line break at the end of a paragraph', () => {
			paste(editor, { 'text/html': '<div>Line one<br>Line two<br><br>Line four<br></div><div>Next</div>' })

			expect(editor.getData()).toEqual(paragraphs('Line one<br>Line two<br><br>Line four', 'Next'))
		})

		it('keeps the lines of preformatted text', () => {
			paste(editor, { 'text/html': '<pre>line one\nline two\n\nline four\n</pre>' })

			expect(editor.getData()).toEqual(paragraphs('line one<br>line two<br><br>line four'))
		})

		it('does not change content copied from the composer', () => {
			editor.setData('<p>Best regards,</p><p>Jan</p><p>&nbsp;</p><p>PS<br>&nbsp;</p>')
			const copied = editor.getData()
			editor.setData('')

			paste(editor, { 'text/html': copied })

			expect(editor.getData()).toEqual(copied)
		})
	})

	it('keeps lists when pasting HTML into an editor with list support', () => {
		paste(editor, { 'text/html': '<ul><li>One</li><li>Two</li></ul>', 'text/plain': 'One\nTwo' })

		expect(editor.getData()).toContain('<ul><li')
		expect(editor.getData()).not.toContain('- One')
	})

	describe('pasting lists into an editor without list support', () => {
		let plainEditor

		beforeEach(async () => {
			plainEditor = await VirtualTestEditor.create({
				licenseKey: 'GPL',
				plugins: [Paragraph, ShiftEnter, MailPlugin],
			})
			plainEditor.editing.view.scrollToTheSelection = () => {}
		})

		it('marks the items of a list from HTML copied from a text editor', () => {
			paste(plainEditor, {
				'text/html': '<p>Test</p><ul><li><p>LIst</p></li><li><p>item</p></li></ul><p>Hello</p>',
				'text/plain': 'Test\n\n- LIst\n- item\n\nHello',
			})

			expect(plainEditor.getData()).toEqual(paragraphs('Test', '- LIst', '- item', 'Hello'))
		})

		it('marks the items of an unordered list', () => {
			paste(plainEditor, { 'text/html': '<ul><li>One</li><li>Two</li></ul>' })

			expect(plainEditor.getData()).toEqual(paragraphs('- One', '- Two'))
		})

		it('numbers the items of an ordered list', () => {
			paste(plainEditor, { 'text/html': '<ol><li>One</li><li>Two</li></ol>' })

			expect(plainEditor.getData()).toEqual(paragraphs('1. One', '2. Two'))
		})

		it('numbers the items of an ordered list from its start attribute', () => {
			paste(plainEditor, { 'text/html': '<ol start="3"><li>One</li><li>Two</li></ol>' })

			expect(plainEditor.getData()).toEqual(paragraphs('3. One', '4. Two'))
		})

		it('numbers the items from 1 if the start attribute is not a number', () => {
			paste(plainEditor, { 'text/html': '<ol start="x"><li>One</li></ol>' })

			expect(plainEditor.getData()).toEqual(paragraphs('1. One'))
		})

		it('indents nested lists', () => {
			paste(plainEditor, { 'text/html': '<ul><li>A<ul><li>B</li></ul></li><li>C</li></ul>' })

			expect(plainEditor.getData()).toEqual(paragraphs('- A', '&nbsp; - B', '- C'))
		})

		it('marks lists inside other blocks', () => {
			paste(plainEditor, { 'text/html': '<blockquote><ol><li>X</li></ol></blockquote>' })

			expect(plainEditor.getData()).toEqual(paragraphs('1. X'))
		})

		it('does not change paragraphs without a list', () => {
			paste(plainEditor, { 'text/html': '<p>a</p>' })

			expect(plainEditor.getData()).toEqual(paragraphs('a'))
		})
	})

	describe('copying', () => {
		it('copies paragraphs and list items as consecutive lines', () => {
			editor.setData('<p>Best regards,</p><p>Jan</p><p>&nbsp;</p><p>Line one<br>Line two</p><ul><li>One</li><li>Two</li></ul><p>After</p>')

			const data = copyAll(editor)

			expect(data['text/plain']).toEqual('Best regards,\nJan\n\nLine one\nLine two\n\nOne\nTwo\n\nAfter')
			expect(data['text/html']).toContain('<p style="margin:0;">Jan</p>')
		})

		it('pastes copied plain text back as it was', () => {
			editor.setData(paragraphs('Best regards,', 'Jan', '', '', 'PS'))
			const html = editor.getData()
			const { 'text/plain': text } = copyAll(editor)

			editor.setData('')
			paste(editor, { 'text/plain': text })

			expect(editor.getData()).toEqual(html)
		})
	})
})
