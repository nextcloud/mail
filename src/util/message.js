/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { html, plain } from './text.js'

/**
 * Convert a given message data object to a text instance (see ./text.js)
 *
 * @param {{ isHtml: boolean, bodyHtml?: string|null, bodyPlain?: string|null }} message The message object
 * @return {import('./text.js').Text} The text instance
 */
export function messageBodyToTextInstance(message) {
	if (message.isHtml) {
		return html(message.bodyHtml ?? '')
	}

	return plain(message.bodyPlain ?? '')
}
