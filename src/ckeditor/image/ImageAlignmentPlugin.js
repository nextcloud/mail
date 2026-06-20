/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import alignCenterIcon from '@mdi/svg/svg/format-align-center.svg?raw'
import alignLeftIcon from '@mdi/svg/svg/format-align-left.svg?raw'
import alignRightIcon from '@mdi/svg/svg/format-align-right.svg?raw'
import { translate as t } from '@nextcloud/l10n'
import { addToolbarToDropdown, createDropdown, Plugin } from 'ckeditor5'

const ALIGNMENT_BUTTONS = [
	'imageStyle:alignBlockLeft',
	'imageStyle:alignCenter',
	'imageStyle:alignBlockRight',
]

/**
 * Registers an "imageAlignment" toolbar item that groups the image alignment
 * options into a single dropdown button — the whole button opens the menu,
 * exactly like the text-alignment dropdown. CKEditor's built-in image-style
 * grouping renders a split button instead (with a separately hoverable arrow),
 * which is what this plugin replaces.
 */
export default class ImageAlignmentPlugin extends Plugin {
	static get pluginName() {
		return 'ImageAlignmentPlugin'
	}

	init() {
		const editor = this.editor
		const factory = editor.ui.componentFactory

		factory.add('imageAlignment', (locale) => {
			const dropdown = createDropdown(locale)
			const buttons = ALIGNMENT_BUTTONS.map((name) => factory.create(name))

			addToolbarToDropdown(dropdown, buttons, {
				enableActiveItemFocusOnDropdownOpen: true,
			})

			dropdown.buttonView.set({
				label: t('mail', 'Align image'),
				icon: alignLeftIcon,
				tooltip: true,
			})

			// Reflect the current alignment on the toolbar button, like the text
			// alignment dropdown does.
			const command = editor.commands.get('imageStyle')
			if (command) {
				dropdown.buttonView.bind('icon').to(command, 'value', (value) => {
					if (value === 'alignCenter') {
						return alignCenterIcon
					}
					if (value === 'alignBlockRight') {
						return alignRightIcon
					}
					return alignLeftIcon
				})
			}

			// Only enable the dropdown while an alignment option is available.
			dropdown.bind('isEnabled').toMany(buttons, 'isEnabled', (...enabled) => enabled.some(Boolean))

			return dropdown
		})
	}
}
