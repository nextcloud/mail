/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { Paragraph } from 'ckeditor5'
import TextDirectionPlugin from '../../../ckeditor/direction/TextDirectionPlugin.js'
import VirtualTestEditor from '../../virtualtesteditor.js'

describe('TextDirectionPlugin', () => {
	it('upcasts dir="rtl" from HTML', async () => {
		const text = '<p dir="rtl">مرحبا</p>'
		const expected = '<p dir="rtl">مرحبا</p>'

		const editor = await VirtualTestEditor.create({
			licenseKey: 'GPL',
			initialData: text,
			plugins: [Paragraph, TextDirectionPlugin],
		})

		expect(editor.getData()).toEqual(expected)
	})

	it('upcasts dir="ltr" from HTML', async () => {
		const text = '<p dir="ltr">Hello</p>'
		const expected = '<p dir="ltr">Hello</p>'

		const editor = await VirtualTestEditor.create({
			licenseKey: 'GPL',
			initialData: text,
			plugins: [Paragraph, TextDirectionPlugin],
		})

		expect(editor.getData()).toEqual(expected)
	})

	it('does not add dir attribute when none is set', async () => {
		const text = '<p>Hello</p>'
		const expected = '<p>Hello</p>'

		const editor = await VirtualTestEditor.create({
			licenseKey: 'GPL',
			initialData: text,
			plugins: [Paragraph, TextDirectionPlugin],
		})

		expect(editor.getData()).toEqual(expected)
	})

	it('applies RTL direction via command', async () => {
		const text = '<p>Hello</p>'

		const editor = await VirtualTestEditor.create({
			licenseKey: 'GPL',
			initialData: text,
			plugins: [Paragraph, TextDirectionPlugin],
		})

		// Select the first block
		const root = editor.model.document.getRoot()
		editor.model.change((writer) => {
			writer.setSelection(root.getChild(0), 'on')
		})

		editor.execute('textDirection', { value: 'rtl' })

		expect(editor.getData()).toEqual('<p dir="rtl">Hello</p>')
	})

	it('applies LTR direction via command', async () => {
		const text = '<p>مرحبا</p>'

		const editor = await VirtualTestEditor.create({
			licenseKey: 'GPL',
			initialData: text,
			plugins: [Paragraph, TextDirectionPlugin],
		})

		const root = editor.model.document.getRoot()
		editor.model.change((writer) => {
			writer.setSelection(root.getChild(0), 'on')
		})

		editor.execute('textDirection', { value: 'ltr' })

		expect(editor.getData()).toEqual('<p dir="ltr">مرحبا</p>')
	})

	it('toggles off direction when same value is applied', async () => {
		const text = '<p dir="rtl">مرحبا</p>'

		const editor = await VirtualTestEditor.create({
			licenseKey: 'GPL',
			initialData: text,
			plugins: [Paragraph, TextDirectionPlugin],
		})

		const root = editor.model.document.getRoot()
		editor.model.change((writer) => {
			writer.setSelection(root.getChild(0), 'on')
		})

		// Applying the same direction again should toggle it off
		editor.execute('textDirection', { value: 'rtl' })

		expect(editor.getData()).toEqual('<p>مرحبا</p>')
	})

	it('switches direction from RTL to LTR', async () => {
		const text = '<p dir="rtl">Hello</p>'

		const editor = await VirtualTestEditor.create({
			licenseKey: 'GPL',
			initialData: text,
			plugins: [Paragraph, TextDirectionPlugin],
		})

		const root = editor.model.document.getRoot()
		editor.model.change((writer) => {
			writer.setSelection(root.getChild(0), 'on')
		})

		editor.execute('textDirection', { value: 'ltr' })

		expect(editor.getData()).toEqual('<p dir="ltr">Hello</p>')
	})

	it('registers the textDirection command', async () => {
		const editor = await VirtualTestEditor.create({
			licenseKey: 'GPL',
			initialData: '<p>test</p>',
			plugins: [Paragraph, TextDirectionPlugin],
		})

		expect(editor.commands.get('textDirection')).toBeDefined()
	})

	it('preserves direction across multiple paragraphs', async () => {
		const text = '<p dir="rtl">مرحبا</p><p dir="ltr">Hello</p>'
		const expected = '<p dir="rtl">مرحبا</p><p dir="ltr">Hello</p>'

		const editor = await VirtualTestEditor.create({
			licenseKey: 'GPL',
			initialData: text,
			plugins: [Paragraph, TextDirectionPlugin],
		})

		expect(editor.getData()).toEqual(expected)
	})
})
