/**
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { convertAxiosError } from '../errors/convert'

export async function updateAccount(id, data) {
	const url = generateUrl('/apps/mail/api/sieve/account/{id}', {
		id,
	})

	try {
		return (await axios.put(url, data)).data
	} catch (error) {
		throw convertAxiosError(error)
	}
}

/**
 * Fetch active sieve script of given account id.
 *
 * @param {string} id Account id
 * @return {Promise<{script: string, scriptName: string}>}
 */
export async function getActiveScript(id) {
	const url = generateUrl('/apps/mail/api/sieve/active/{id}', {
		id,
	})

	try {
		return (await axios.get(url)).data
	} catch (error) {
		throw convertAxiosError(error)
	}
}

/**
 * Update active sieve script of given account id.
 *
 * @param {string} id Account id
 * @param {{script: string, scriptName: string}} data Script data object
 * @return {Promise<void>}
 */
export async function updateActiveScript(id, data) {
	const url = generateUrl('/apps/mail/api/sieve/active/{id}', {
		id,
	})

	try {
		return (await axios.put(url, data)).data
	} catch (error) {
		throw convertAxiosError(error)
	}
}
