/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Keeps an element from growing wider than its container, unless its own
 * inline style already sets a max-width. Fixes the width of some newsletters.
 *
 * @param element the element to restrict
 */
export function limitToContainerWidth(element: HTMLElement): void {
	if (!element.style.maxWidth) {
		element.style.maxWidth = '100%'
	}
}
