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

import logger from '../logger'

export async function handleHttpAuthErrors(commit, cb) {
	try {
		const res = await cb()
		logger.debug('req done')
		return res
	} catch (error) {
		logger.debug('req err', { error, status: error.response?.status, message: error.response?.data?.message })
		if (error.response?.status === 401 && error.response?.data?.message === 'Current user is not logged in') {
			logger.warn('Request failed due to expired session')
			commit('setSessionExpired')
		}
		throw error
	}
}
