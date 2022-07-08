/*
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 */

import { matchError } from '../../../errors/match'

describe('match', () => {
	it('throws an error when nothing matches', (done) => {
		const error = new Error('henlo')

		matchError(error, {}).catch(() => done())
	})

	it('uses the default', (done) => {
		const map = {
			default: (error) => 3,
		}
		const error = new Error('henlo')

		matchError(error, map).then((result) => {
			expect(expect(result).toEqual(3))
			done()
		})
	})

	it('matches errors', (done) => {
		const map = {
			MyErr: (error) => 2,
			default: (error) => 3,
		}
		const error = new Error('henlo')
		error.name = 'MyErr'

		matchError(error, map).then((result) => {
			expect(expect(result).toEqual(2))
			done()
		})
	})
})
