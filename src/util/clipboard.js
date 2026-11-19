/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import logger from '../logger.js'

/**
 * Copy text to the clipboard, or show it in a prompt to copy by hand when the
 * Clipboard API is unavailable (e.g. on plain HTTP) or permission is denied.
 *
 * @param {string} text Text to copy
 * @param {string} promptTitle Title of the fallback prompt
 * @return {Promise<boolean>} Whether the text was written to the clipboard
 */
export async function copyToClipboard(text, promptTitle) {
	try {
		await navigator.clipboard.writeText(text)

		return true
	} catch (error) {
		logger.debug('clipboard API unavailable, showing a prompt to copy by hand', { error })
		window.prompt(promptTitle, text)

		return false
	}
}
