/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export const wait = (ms) => {
	return new Promise((resolve) => {
		setTimeout(resolve, ms)
	})
}
