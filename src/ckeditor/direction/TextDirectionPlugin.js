/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { ButtonView, Plugin } from 'ckeditor5'
import TextDirectionCommand from './TextDirectionCommand.js'

const ATTRIBUTE = 'textDirection'

// https://pictogrammers.com/library/mdi/icon/format-pilcrow-arrow-left/
const ltrIcon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><title>format-pilcrow-arrow-left</title><path d="M8,17V14L4,18L8,22V19H20V17M10,10V15H12V4H14V15H16V4H18V2H10A4,4 0 0,0 6,6A4,4 0 0,0 10,10Z" /></svg>'

// https://pictogrammers.com/library/mdi/icon/format-pilcrow-arrow-right/
const rtlIcon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><title>format-pilcrow-arrow-right</title><path d="M21,18L17,14V17H5V19H17V22M9,10V15H11V4H13V15H15V4H17V2H9A4,4 0 0,0 5,6A4,4 0 0,0 9,10Z" /></svg>'

/**
 * The text direction plugin. Adds `dir` attribute support to block elements
 * and registers toolbar buttons for switching between LTR and RTL directions.
 */
export default class TextDirectionPlugin extends Plugin {
	static get pluginName() {
		return 'TextDirectionPlugin'
	}

	init() {
		this._defineSchema()
		this._defineConverters()
		this._defineCommand()

		// Only register toolbar buttons when the editor has a UI (not in data-only/virtual editors)
		if (this.editor.ui) {
			this._defineButtons()
		}
	}

	/**
	 * Allows the `textDirection` attribute on all block elements.
	 *
	 * @private
	 */
	_defineSchema() {
		const schema = this.editor.model.schema

		schema.extend('$block', { allowAttributes: ATTRIBUTE })
		schema.setAttributeProperties(ATTRIBUTE, { isFormatting: true })
	}

	/**
	 * Defines converters for the `textDirection` attribute.
	 * Downcasts to `dir` style attribute and upcasts from `dir` HTML attribute.
	 *
	 * @private
	 */
	_defineConverters() {
		const editor = this.editor

		// Downcast: model textDirection attribute -> view dir attribute
		editor.conversion.for('downcast').attributeToAttribute({
			model: {
				key: ATTRIBUTE,
				values: ['ltr', 'rtl'],
			},
			view: {
				ltr: {
					key: 'dir',
					value: 'ltr',
				},
				rtl: {
					key: 'dir',
					value: 'rtl',
				},
			},
		})

		// Upcast: view dir="ltr" attribute -> model textDirection attribute
		editor.conversion.for('upcast').attributeToAttribute({
			view: {
				key: 'dir',
				value: 'ltr',
			},
			model: {
				key: ATTRIBUTE,
				value: 'ltr',
			},
		})

		// Upcast: view dir="rtl" attribute -> model textDirection attribute
		editor.conversion.for('upcast').attributeToAttribute({
			view: {
				key: 'dir',
				value: 'rtl',
			},
			model: {
				key: ATTRIBUTE,
				value: 'rtl',
			},
		})
	}

	/**
	 * Registers the `textDirection` command.
	 *
	 * @private
	 */
	_defineCommand() {
		this.editor.commands.add(ATTRIBUTE, new TextDirectionCommand(this.editor))
	}

	/**
	 * Registers the `textDirection:ltr` and `textDirection:rtl` toolbar buttons.
	 *
	 * @private
	 */
	_defineButtons() {
		const editor = this.editor
		const t = editor.t
		const command = editor.commands.get(ATTRIBUTE)

		editor.ui.componentFactory.add('textDirection:ltr', (locale) => {
			const buttonView = new ButtonView(locale)

			buttonView.set({
				label: t('Left-to-right text'),
				icon: ltrIcon,
				tooltip: true,
				isToggleable: true,
			})

			buttonView.bind('isEnabled').to(command)
			buttonView.bind('isOn').to(command, 'value', (value) => value === 'ltr')

			this.listenTo(buttonView, 'execute', () => {
				editor.execute(ATTRIBUTE, { value: 'ltr' })
				editor.editing.view.focus()
			})

			return buttonView
		})

		editor.ui.componentFactory.add('textDirection:rtl', (locale) => {
			const buttonView = new ButtonView(locale)

			buttonView.set({
				label: t('Right-to-left text'),
				icon: rtlIcon,
				tooltip: true,
				isToggleable: true,
			})

			buttonView.bind('isEnabled').to(command)
			buttonView.bind('isOn').to(command, 'value', (value) => value === 'rtl')

			this.listenTo(buttonView, 'execute', () => {
				editor.execute(ATTRIBUTE, { value: 'rtl' })
				editor.editing.view.focus()
			})

			return buttonView
		})
	}
}
