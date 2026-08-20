/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { detailedDatetime, flatDatetime, groupedDatetime } from '../../../util/relativeDatetime.js'

describe('relativeDatetime', () => {
	describe('toPlain', () => {
		it('shortens todays time', () => {
			const ref = new Date()
			ref.setFullYear(2020, 1, 13)
			ref.setHours(13, 14)
			const d = new Date()
			d.setFullYear(2020, 1, 13)
			d.setHours(9, 27)

			const formatted = groupedDatetime(ref, d)

			expect(formatted).toBe('9:27 AM')
		})

		it('shortens yesterdays time', () => {
			const ref = new Date()
			ref.setFullYear(2020, 1, 13)
			ref.setHours(13, 14)
			const d = new Date()
			d.setFullYear(2020, 1, 12)
			d.setHours(9, 27)

			const formatted = groupedDatetime(ref, d)

			expect(formatted).toBe('9:27 AM')
		})

		it('shortens yesterdays time with label', () => {
			const ref = new Date()
			ref.setFullYear(2020, 1, 13)
			ref.setHours(13, 14)
			const d = new Date()
			d.setFullYear(2020, 1, 12)
			d.setHours(9, 27)

			const formatted = flatDatetime(ref, d)

			expect(formatted).toBe('Yesterday 9:27 AM')
		})

		it('shortens todays time without label', () => {
			const ref = new Date()
			ref.setFullYear(2020, 1, 13)
			ref.setHours(13, 14)
			const d = new Date()
			d.setFullYear(2020, 1, 13)
			d.setHours(9, 27)

			const formatted = flatDatetime(ref, d)

			expect(formatted).toBe('9:27 AM')
		})

		it('details yesterdays time with label', () => {
			const ref = new Date()
			ref.setFullYear(2020, 1, 13)
			ref.setHours(13, 14)
			const d = new Date()
			d.setFullYear(2020, 1, 12)
			d.setHours(9, 27)

			const formatted = detailedDatetime(ref, d)

			expect(formatted).toBe('Yesterday 9:27 AM')
		})

		it('details todays time without label', () => {
			const ref = new Date()
			ref.setFullYear(2020, 1, 13)
			ref.setHours(13, 14)
			const d = new Date()
			d.setFullYear(2020, 1, 13)
			d.setHours(9, 27)

			const formatted = detailedDatetime(ref, d)

			expect(formatted).toBe('9:27 AM')
		})

		it('details older dates with date and time', () => {
			const ref = new Date()
			ref.setFullYear(2020, 1, 13)
			ref.setHours(13, 14)
			const d = new Date()
			d.setFullYear(2020, 1, 11)
			d.setHours(9, 27)
			d.setSeconds(0, 0)

			const formatted = detailedDatetime(ref, d)

			expect(formatted).toBe('February 11, 2020 9:27 AM')
		})

		it('shortens this weeks day', () => {
			const ref = new Date()
			ref.setFullYear(2020, 1, 13)
			ref.setHours(13, 14)
			const d = new Date()
			d.setFullYear(2020, 1, 11)
			d.setHours(9, 27)

			const formatted = groupedDatetime(ref, d)

			expect(formatted).toBe('Tu')
		})

		it('shortens this years date', () => {
			const ref = new Date()
			ref.setFullYear(2020, 1, 13)
			ref.setHours(13, 14)
			const d = new Date()
			d.setFullYear(2020, 0, 1)
			d.setHours(9, 27)

			const formatted = groupedDatetime(ref, d)

			expect(formatted).toBe('Jan 1')
		})

		it('shortens recent last years date', () => {
			const ref = new Date()
			ref.setFullYear(2020, 1, 13)
			ref.setHours(13, 14)
			const d = new Date()
			d.setFullYear(2019, 10, 17)
			d.setHours(9, 27)

			const formatted = groupedDatetime(ref, d)

			expect(formatted).toBe('Nov 17')
		})

		it('shortens older last years date', () => {
			const ref = new Date()
			ref.setFullYear(2020, 1, 13)
			ref.setHours(13, 14)
			const d = new Date()
			d.setFullYear(2019, 0, 3)
			d.setHours(9, 27)

			const formatted = groupedDatetime(ref, d)

			expect(formatted).toBe('Jan 3, 2019')
		})

		it('shortens old date', () => {
			const ref = new Date()
			ref.setFullYear(2020, 1, 13)
			ref.setHours(13, 14)
			const d = new Date()
			d.setFullYear(2013, 7, 8)
			d.setHours(9, 27)

			const formatted = groupedDatetime(ref, d)

			expect(formatted).toBe('Aug 8, 2013')
		})
	})
})
