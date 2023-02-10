/**
 * @copyright Copyright (c) 2023 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import { compareSmimeCertificates } from '../../../util/smime'

describe('smime', () => {
	describe('compareSmimeCertificates', () => {
		it('correctly sorts certificates', () => {
			let a, b

			a = {info: { notAfter: 200 }}
			b = {info: { notAfter: 100 }}
			expect(compareSmimeCertificates(a, b)).toEqual(-1)

			a = {info: { notAfter: 100 }}
			b = {info: { notAfter: 100 }}
			expect(compareSmimeCertificates(a, b)).toEqual(0)

			a = {info: { notAfter: 100 }}
			b = {info: { notAfter: 200 }}
			expect(compareSmimeCertificates(a, b)).toEqual(1)
		})
	})
})
