/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getFilePickerBuilder } from '@nextcloud/dialogs'
import { pickFolder } from '../../../util/filePicker.js'

vi.mock('@nextcloud/dialogs')

describe('pickFolder', () => {
	let builder
	let picker

	beforeEach(() => {
		picker = { pick: vi.fn().mockResolvedValue('/Documents') }
		builder = {
			allowDirectories: vi.fn(() => builder),
			setMultiSelect: vi.fn(() => builder),
			setMimeTypeFilter: vi.fn(() => builder),
			addButton: vi.fn(() => builder),
			build: vi.fn(() => picker),
		}
		getFilePickerBuilder.mockReturnValue(builder)
	})

	it('picks a single folder', async () => {
		const path = await pickFolder('Choose a folder')

		expect(getFilePickerBuilder).toHaveBeenCalledWith('Choose a folder')
		expect(builder.allowDirectories).toHaveBeenCalledWith(true)
		expect(builder.setMultiSelect).toHaveBeenCalledWith(false)
		expect(builder.setMimeTypeFilter).toHaveBeenCalledWith(['httpd/unix-directory'])
		expect(path).toBe('/Documents')
	})

	it('propagates the rejection of a dismissed picker', async () => {
		const closed = new Error('FilePicker: No nodes selected')
		picker.pick.mockRejectedValue(closed)

		await expect(pickFolder('Choose a folder')).rejects.toThrow(closed)
	})
})
