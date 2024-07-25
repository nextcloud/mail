/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'

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

export async function fetchInternalAdresses() {
	const url = generateUrl('/apps/mail/api/internalAddress')
	const response = await axios.get(url)
	return response.data.data
}
