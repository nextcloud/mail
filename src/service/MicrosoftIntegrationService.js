/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export async function configure(tenantId, clientId, clientSecret) {
	const response = await axios.post(
		generateUrl('/apps/mail/api/integration/microsoft'),
		{
			tenantId,
			clientId,
			clientSecret,
		},
		{
			headers: {
				Accept: 'application/json',
			},
		},
	)

	return response.data.data
}

export async function unlink() {
	const response = await axios.delete(
		generateUrl('/apps/mail/api/integration/microsoft'),
		{
			headers: {
				Accept: 'application/json',
			},
		},
	)

	return response.data.data
}
