/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export const savePreference = (key, value) => {
	const url = generateUrl('/apps/mail/api/preferences/{key}', {
		key,
	})
	const data = {
		key,
		value,
	}

	return Axios.put(url, data).then((resp) => resp.data)
}

export const getPreference = (key) => {
	const url = generateUrl('/apps/mail/api/preferences/{key}', {
		key,
	})

	return Axios.get(url).then((resp) => resp.data)
}
