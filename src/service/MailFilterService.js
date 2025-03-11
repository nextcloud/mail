/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'

export async function getFilters(accountId) {
	const url = generateUrl('/apps/mail/api/filter/{accountId}', { accountId })

	const { data } = await axios.get(url)

	return data
}

export async function updateFilters(accountId, filters) {
	const url = generateUrl('/apps/mail/api/filter/{accountId}', { accountId })

	const { data } = await axios.put(url, { filters })

	return data
}
