/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { convertAxiosError } from '../errors/convert.js'
import { randomId } from '../util/randomId'

export async function getFilters(accountId) {
	const url = generateUrl('/apps/mail/api/mailfilter/{accountId}', { accountId })

	try {
		return (await axios.get(url)).data
	} catch (error) {
		throw convertAxiosError(error)
	}
}

export async function updateFilters(accountId, filters) {
	const url = generateUrl('/apps/mail/api/mailfilter/{accountId}', { accountId })

	try {
		return (await axios.put(url, { filters })).data
	} catch (error) {
		throw convertAxiosError(error)
	}
}
