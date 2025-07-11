/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { convertAxiosError } from '../errors/convert.js'

export const getPromptes = async () => {
	const url = generateUrl('/apps/mail/api/prompts')

	try {
		const resp = await axios.get(url)
		return resp.data.data
	} catch (e) {
		throw convertAxiosError(e)
	}
}

export const savePromptValue = async (value, key) => {
	const url = generateUrl('/apps/mail/api/prompts/{key}', { key })

	try {
		 await axios.post(url, { value })
	} catch (e) {
		throw convertAxiosError(e)
	}
}
