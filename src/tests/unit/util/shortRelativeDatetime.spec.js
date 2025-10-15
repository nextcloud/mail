/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { shortDatetime } from '../../../util/shortRelativeDatetime'

describe('shortRelativeDatetime', () => {
	describe('toPlain', () => {
		it('shortens todays time', () => {
			const ref = new Date()
			ref.setFullYear(2020, 1, 13)
			ref.setHours(13, 14)
			const d = new Date()
			d.setFullYear(2020, 1, 13)
			d.setHours(9, 27)

			const formatted = shortDatetime(ref, d)

			expect(formatted).toBe('9:27')
		})

		it('shortens this weeks day', () => {
			const ref = new Date()
			ref.setFullYear(2020, 1, 13)
			ref.setHours(13, 14)
			const d = new Date()
			d.setFullYear(2020, 1, 11)
			d.setHours(9, 27)

			const formatted = shortDatetime(ref, d)

			expect(formatted).toBe('Tu')
		})

		it('shortens this years date', () => {
			const ref = new Date()
			ref.setFullYear(2020, 1, 13)
			ref.setHours(13, 14)
			const d = new Date()
			d.setFullYear(2020, 0, 1)
			d.setHours(9, 27)

			const formatted = shortDatetime(ref, d)

			expect(formatted).toBe('Jan 1')
		})

		it('shortens recent last years date', () => {
			const ref = new Date()
			ref.setFullYear(2020, 1, 13)
			ref.setHours(13, 14)
			const d = new Date()
			d.setFullYear(2019, 10, 17)
			d.setHours(9, 27)

			const formatted = shortDatetime(ref, d)

			expect(formatted).toBe('Nov 17')
		})

		it('shortens older last years date', () => {
			const ref = new Date()
			ref.setFullYear(2020, 1, 13)
			ref.setHours(13, 14)
			const d = new Date()
			d.setFullYear(2019, 0, 3)
			d.setHours(9, 27)

			const formatted = shortDatetime(ref, d)

			expect(formatted).toBe('Jan 3, 2019')
		})

		it('shortens old date', () => {
			const ref = new Date()
			ref.setFullYear(2020, 1, 13)
			ref.setHours(13, 14)
			const d = new Date()
			d.setFullYear(2013, 7, 8)
			d.setHours(9, 27)

			const formatted = shortDatetime(ref, d)

			expect(formatted).toBe('Aug 8, 2013')
		})
	})
})
