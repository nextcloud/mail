/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getFilePickerBuilder, showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { ButtonView, IconImageAssetManager, ImageInsertUI, MenuBarMenuListItemButtonView, Plugin } from 'ckeditor5'
import { getClient } from '../../dav/client.js'
import logger from '../../logger.js'

const MIME_TYPES = ['image/png', 'image/jpeg', 'image/gif', 'image/bmp', 'image/webp']

const SIZE_LIMIT = 10 * 1024 * 1024

/**
 * Nextcloud file picker for CKEditor.
 */
export default class FilesImagePlugin extends Plugin {
	static get requires() {
		return [ImageInsertUI] as const
	}

	static get pluginName() {
		return 'FilesImage' as const
	}

	init(): void {
		const editor = this.editor

		editor.plugins.get('ImageInsertUI').registerIntegration({
			name: 'assetManager',
			observable: () => editor.commands.get('insertImage')!,
			buttonViewCreator: () => this._setUpButton(new ButtonView(editor.locale), false),
			formViewCreator: () => this._setUpButton(new ButtonView(editor.locale), true),
			menuBarButtonViewCreator: () => this._setUpButton(new MenuBarMenuListItemButtonView(editor.locale), true),
		})
	}

	_setUpButton<T extends ButtonView>(button: T, withText: boolean): T {
		button.set({
			// TRANSLATORS: "Files" is the name of the Nextcloud files app
			label: t('mail', 'Insert image from Files'),
			icon: IconImageAssetManager,
			tooltip: !withText,
			withText,
		})
		button.bind('isEnabled').to(this.editor.commands.get('insertImage')!)
		button.on('execute', () => this._pickAndInsert())

		return button
	}

	async _pickAndInsert(): Promise<void> {
		let node
		try {
			const nodes = await getFilePickerBuilder(t('mail', 'Choose an image'))
				.setMimeTypeFilter(MIME_TYPES)
				.setMultiSelect(false)
				.setCanPick((node) => (node.size ?? 0) <= SIZE_LIMIT)
				.setButtonFactory((selection) => [{
					label: selection.length === 0
						// TRANSLATORS: call to action of the file picker while nothing is selected
						? t('mail', 'Select an image')
						// TRANSLATORS: button of the file picker to insert the selected image, {name} is a file name
						: t('mail', 'Insert {name}', { name: selection[0].basename }, { escape: false, sanitize: false }),
					disabled: selection.length === 0,
					variant: 'primary',
					callback: (nodes) => logger.debug('picked an image from Files', { nodes }),
				}])
				.build()
				.pickNodes()
			node = nodes[0]
		} catch {
			// Picker closed without picking
			return
		}

		if (node.size! > SIZE_LIMIT) {
			showError(t('mail', 'The selected image is too large to embed'))
			return
		}

		try {
			const response = await getClient('files').getFileContents(node.path, { details: true })
			const blob = new Blob([response.data as BlobPart], { type: response.headers['content-type'] })
			const dataUri = await this._readBlobAsDataUri(blob)

			this.editor.execute('insertImage', { source: dataUri })
			this.editor.editing.view.focus()
		} catch (error) {
			logger.error('Could not insert image from Files', { error })
			showError(t('mail', 'Could not insert the selected image'))
		}
	}

	_readBlobAsDataUri(blob: Blob): Promise<string> {
		return new Promise((resolve, reject) => {
			const reader = new FileReader()
			reader.onload = () => resolve(reader.result as string)
			reader.onerror = () => reject(reader.error)
			reader.readAsDataURL(blob)
		})
	}
}
