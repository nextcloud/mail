/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import imageUrlIcon from '@mdi/svg/svg/image-plus.svg?raw'
import { translate as t } from '@nextcloud/l10n'
import { ButtonView, Plugin } from 'ckeditor5'

/**
 * Adds a toolbar button that lets the user insert an image by URL. The button
 * only fires an event; the surrounding Vue component (TextEditor) opens a dialog,
 * downloads the image through the server and inserts it as a data: URI.
 */
export default class ImageFromUrlPlugin extends Plugin {
	init() {
		const editor = this.editor

		editor.ui.componentFactory.add('imageFromUrl', (locale) => {
			const button = new ButtonView(locale)
			button.set({
				label: t('mail', 'Insert image from URL'),
				icon: imageUrlIcon,
				tooltip: true,
				isEnabled: true,
			})
			button.on('execute', () => {
				editor.fire('mail:insertImageFromUrl')
			})
			return button
		})
	}
}
