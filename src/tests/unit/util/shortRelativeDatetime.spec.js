/**
 * @copyright 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
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
