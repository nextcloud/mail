/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import memoize from 'lodash/fp/memoize.js'
import Axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export const fetchAvatarUrl = (email) => {
	if (email === null) {
		return Promise.resolve(undefined)
	}

	const url = generateUrl('/apps/mail/api/avatars/url/{email}', {
		email,
	})

	return Axios.get(url, { adapter: 'fetch', fetchOptions: { priority: 'low' } })
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
