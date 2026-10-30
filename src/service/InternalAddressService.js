/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { fetchIfChanged } from './RevalidationService.js'

export async function addInternalAddress(address, type) {
	const url = generateUrl('/apps/mail/api/internalAddress/{address}?type={type}', {
		address,
		type,
	})
	const response = await axios.put(url)
	return response.data.data
}

export async function removeInternalAddress(address, type) {
	const url = generateUrl('/apps/mail/api/internalAddress/{address}?type={type}', {
		address,
		type,
	})
	await axios.delete(url)
}

export async function fetchInternalAddresses(etag) {
	return fetchIfChanged(generateUrl('/apps/mail/api/internalAddress'), etag)
}
