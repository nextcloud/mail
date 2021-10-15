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

/**
 * @typedef {Object} ProviderConfigServer
 * @property {string} authentication
 * @property {string} hostname
 * @property {string} port
 * @property {string} socketType
 * @property {string} username
 */

/**
 * @typedef {Object} ProviderConfig
 * @property {string} displayName
 * @property {ProviderConfigServer[]} imap
 * @property {ProviderConfigServer[]} smtp
 */

/**
 * @param {string} email to lookup the isp
 * @returns {Promise<ProviderConfig>}
 */
export async function ispDb(email) {
	const url = generateUrl('/apps/mail/api/autoconfig/ispdb')

	try {
		return (await axios.post(url, { email })).data
	} catch (error) {
		throw convertAxiosError(error)
	}
}
