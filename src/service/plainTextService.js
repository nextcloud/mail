/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { convertAxiosError } from '../errors/convert.js'

export async function getPlainText(id) {
	const url = generateUrl('/apps/mail/api/messages/{id}/body?plain=true', {
		id,
	})

	const response = await axios.get(url).catch((error) => {
		throw convertAxiosError(error)
	})

	return response.data
}
