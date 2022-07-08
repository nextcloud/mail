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

import { convertAxiosError } from '../../../errors/convert'

describe('convert error', () => {
	it('ignores errors without a response', () => {
		const error = {} // no response

		const result = convertAxiosError(error)

		expect(result instanceof Error).toEqual(false)
		expect(result).toEqual(error)
	})

	it('ignores errors it does not know', () => {
		const error = {
			response: {
				headers: {},
				status: 400,
				data: {},
			},
		}

		const result = convertAxiosError(error)

		expect(result instanceof Error).toEqual(false)
		expect(result).toEqual(error)
	})

	it('converts known exceptions to errors', () => {
		const error = {
			response: {
				headers: {
					'x-mail-response': '1',
				},
				status: 400,
				data: {
					status: 'fail',
					data: {
						type: 'OCA\\Mail\\Exception\\MailboxLockedException',
					},
				},
			},
		}

		const result = convertAxiosError(error)

		expect(result instanceof Error).toEqual(true)
	})
})
