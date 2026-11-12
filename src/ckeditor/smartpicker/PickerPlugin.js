/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

import { Plugin } from 'ckeditor5'
import InsertItemCommand from './InsertItemCommand.js'
export default class PickerPlugin extends Plugin {
	init() {
		this.editor.commands.add(
			'insertItem',
			new InsertItemCommand(this.editor),
		)
	}
}
