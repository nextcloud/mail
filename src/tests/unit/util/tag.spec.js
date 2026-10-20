/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { tagBackgroundColor, tagTextColor } from '../../../util/tag.js'

describe('tag', () => {
	describe('tagTextColor', () => {
		it('darkens the color in the light theme', () => {
			const actual = tagTextColor('#0082c9', false)

			expect(actual).toBe('color-mix(in srgb, #0082c9 40%, black)')
		})

		it('lightens the color in the dark theme', () => {
			const actual = tagTextColor('#0082c9', true)

			expect(actual).toBe('color-mix(in srgb, #0082c9 40%, white)')
		})
	})

	describe('tagBackgroundColor', () => {
		it('mixes the color into the dark background', () => {
			const actual = tagBackgroundColor('#0082c9')

			expect(actual).toBe('color-mix(in srgb, #0082c9 20%, var(--color-background-dark))')
		})
	})
})
