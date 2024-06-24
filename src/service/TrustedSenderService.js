/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'

export async function trustSender(email, type, trustFlag) {
	const url = generateUrl('/apps/mail/api/trustedsenders/{email}?type={type}', {
		email,
		type,
	})

	if (trustFlag) {
		await axios.put(url)
	} else {
		await axios.delete(url)
	}
}
export async function fetchTrustedSenders() {
	const url = generateUrl('/apps/mail/api/trustedsenders')
	const response = await axios.get(url)
	return response.data.data
}
