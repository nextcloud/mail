/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getFilePickerBuilder } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'

/**
 * Let the user pick a folder from Files.
 *
 * @param {string} name Title of the file picker
 * @return {Promise<string>} Path of the picked folder
 * @throws {import('@nextcloud/dialogs').FilePickerClosed} If the picker was closed without picking
 */
export function pickFolder(name) {
	return getFilePickerBuilder(name)
		.allowDirectories(true)
		.setMultiSelect(false)
		.setMimeTypeFilter(['httpd/unix-directory'])
		.addButton({
			label: t('mail', 'Choose'),
			variant: 'primary',
			callback: () => {},
		})
		.build()
		.pick()
}
