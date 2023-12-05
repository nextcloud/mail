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

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export async function configure(clientId, clientSecret) {
	const response = await axios.post(
		generateUrl('/apps/mail/api/integration/google'),
		{
			clientId,
			clientSecret,
		},
		{
			headers: {
				Accept: 'application/json',
			},
		},
	)

	return response.data.data
}

export async function unlink() {
	const response = await axios.delete(
		generateUrl('/apps/mail/api/integration/google'),
		{
			headers: {
				Accept: 'application/json',
			},
		},
	)

	return response.data.data
}
