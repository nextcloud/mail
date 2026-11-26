/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { copyToClipboard } from '../../../util/clipboard.js'

vi.mock('../../../logger.js', () => ({
	default: {
		debug: vi.fn(),
	},
}))

const TEXT = 'jane@example.com'
const PROMPT_TITLE = 'Copy email address'

describe('copyToClipboard', () => {
	let writeText

	beforeEach(() => {
		writeText = vi.fn()
		Object.defineProperty(navigator, 'clipboard', {
			value: { writeText },
			configurable: true,
		})
		vi.spyOn(window, 'prompt').mockReturnValue(null)
	})

	afterEach(() => {
		vi.restoreAllMocks()
	})

	it('writes the text to the clipboard', async () => {
		writeText.mockResolvedValue(undefined)

		const copied = await copyToClipboard(TEXT, PROMPT_TITLE)

		expect(copied).toBe(true)
		expect(writeText).toHaveBeenCalledWith(TEXT)
		expect(window.prompt).not.toHaveBeenCalled()
	})

	it('shows the text in a prompt when writing to the clipboard fails', async () => {
		writeText.mockRejectedValue(new Error('Permission denied'))

		const copied = await copyToClipboard(TEXT, PROMPT_TITLE)

		expect(copied).toBe(false)
		expect(window.prompt).toHaveBeenCalledWith(PROMPT_TITLE, TEXT)
	})

	it('shows the text in a prompt when the clipboard API is unavailable', async () => {
		Object.defineProperty(navigator, 'clipboard', {
			value: undefined,
			configurable: true,
		})

		const copied = await copyToClipboard(TEXT, PROMPT_TITLE)

		expect(copied).toBe(false)
		expect(window.prompt).toHaveBeenCalledWith(PROMPT_TITLE, TEXT)
	})
})
