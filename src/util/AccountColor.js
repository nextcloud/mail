/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import conv from 'color-convert'
import md5 from 'md5'

export function calculateAccountColor(name) {
	const hashed = md5(name)
	const hsl = conv.hex.hsl(hashed)
	const fixedHsl = [Math.round(hsl[0] / 40) * 40, hsl[1], hsl[2]]

	return '#' + conv.hsl.hex(fixedHsl)
}
