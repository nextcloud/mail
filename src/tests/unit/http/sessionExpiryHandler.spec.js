/*
 * @copyright 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
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

import { handleHttpAuthErrors } from '../../../http/sessionExpiryHandler'

describe('sessionExpiryHandler', () => {
	it('does not influence successful requests', async () => {
		const commit = jest.fn()

		await handleHttpAuthErrors(commit, () => {})

		expect(commit).not.toHaveBeenCalled()
	})

	it('ignores other 401s', async () => {
		const commit = jest.fn()
		let exception

		try {
			await handleHttpAuthErrors(commit, () => {
				throw {
					response: {
						status: 401,
						data: {
							message: 'Bonjour',
						},
					},
				}
			})
		} catch (e) {
			exception = e
		}

		// Is this our exception?
		expect(exception.response?.status === 401)
		expect(commit).not.toHaveBeenCalled()
	})

	it('handles relevant 401s', async () => {
		const commit = jest.fn()
		let exception

		try {
			await handleHttpAuthErrors(commit, () => {
				throw {
					response: {
						status: 401,
						data: {
							message: 'Current user is not logged in',
						},
					},
				}
			})
		} catch (e) {
			exception = e
		}

		// Is this our exception?
		expect(exception.response?.status === 401)
		expect(commit).toHaveBeenCalled()
	})

})

