/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import folderIcon from '@mdi/svg/svg/folder-image.svg?raw'
import imageIcon from '@mdi/svg/svg/image.svg?raw'
import linkIcon from '@mdi/svg/svg/link-variant.svg?raw'
import uploadIcon from '@mdi/svg/svg/upload.svg?raw'
import { translate as t } from '@nextcloud/l10n'
import { ButtonView, createDropdown, Plugin } from 'ckeditor5'

/**
 * Toolbar dropdown to insert an image, offering three options: uploading a file
 * from the computer, picking one from the Nextcloud files, or fetching one by
 * URL. Each option only fires an event; the surrounding Vue component
 * (TextEditor) performs the actual work.
 */
export default class ImageDropdownPlugin extends Plugin {
	init() {
		const editor = this.editor

		editor.ui.componentFactory.add('imageDropdown', (locale) => {
			const dropdown = createDropdown(locale)
			dropdown.buttonView.set({
				label: t('mail', 'Insert image'),
				icon: imageIcon,
				tooltip: true,
			})

			const fire = (event) => () => {
				dropdown.isOpen = false
				editor.fire(event)
			}

			dropdown.panelView.children.add(this._createActionButton(
				locale,
				t('mail', 'Upload from computer'),
				uploadIcon,
				fire('mail:uploadImageFromComputer'),
			))
			dropdown.panelView.children.add(this._createActionButton(
				locale,
				t('mail', 'Insert with file manager'),
				folderIcon,
				fire('mail:insertImageFromFiles'),
			))
			dropdown.panelView.children.add(this._createActionButton(
				locale,
				t('mail', 'Insert via URL'),
				linkIcon,
				fire('mail:insertImageFromUrl'),
			))

			return dropdown
		})
	}

	_createActionButton(locale, label, icon, onExecute) {
		const button = new ButtonView(locale)
		button.set({
			label,
			icon,
			tooltip: false,
			withText: true,
			isEnabled: true,
		})
		button.on('execute', onExecute)
		return button
	}
}
