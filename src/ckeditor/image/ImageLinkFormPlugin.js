/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { Plugin } from 'ckeditor5'

/**
 * Adjusts the link form when it is opened for an image. An image link has no
 * "displayed text" (the image itself is the link content), so that input is
 * hidden to avoid confusion. Dismissing the link balloon while the image stays
 * selected returns to the image toolbar, which gives the same "back to the
 * image menu" navigation as the text-alternative form.
 *
 * The implementation only reads CKEditor's public API and toggles a DOM style;
 * if a future version renames the form pieces it simply does nothing.
 */
export default class ImageLinkFormPlugin extends Plugin {
	static get requires() {
		return ['LinkUI']
	}

	static get pluginName() {
		return 'ImageLinkFormPlugin'
	}

	init() {
		const editor = this.editor

		if (!editor.plugins.has('ContextualBalloon')) {
			return
		}

		const linkUI = editor.plugins.get('LinkUI')
		const balloon = editor.plugins.get('ContextualBalloon')

		balloon.on('change:visibleView', () => {
			const formView = linkUI.formView
			const displayedText = formView?.displayedTextInputView
			if (!displayedText?.element || balloon.visibleView !== formView) {
				return
			}

			const imageUtils = editor.plugins.has('ImageUtils')
				? editor.plugins.get('ImageUtils')
				: null
			const isOnImage = !!imageUtils?.getClosestSelectedImageElement(
				editor.model.document.selection,
			)
			displayedText.element.style.display = isOnImage ? 'none' : ''
		})
	}
}
