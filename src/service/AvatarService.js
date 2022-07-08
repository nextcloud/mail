/**
 * @copyright 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
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

import memoize from 'lodash/fp/memoize'
import Axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export const fetchAvatarUrl = (email) => {
	if (email === null) {
		return Promise.resolve(undefined)
	}

	const url = generateUrl('/apps/mail/api/avatars/url/{email}', {
		email,
	})

	return Axios.get(url)
		.then((resp) => resp.data)
		.then((avatar) => {
			if (avatar.isExternal) {
				return generateUrl('/apps/mail/api/avatars/image/{email}', {
					email,
				})
			} else {
				return avatar.url
			}
		})
		.catch((err) => {
			if (err.response.status === 404) {
				return undefined
			}

			return Promise.reject(err)
		})
}

export const fetchAvatarUrlMemoized = memoize(fetchAvatarUrl)
