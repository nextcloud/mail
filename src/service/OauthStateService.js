/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export async function generateOauthState(accountId) {
	const response = await axios.post(
		generateUrl('/apps/mail/api/oauth/state'),
		{ accountId },
		{
			headers: {
				Accept: 'application/json',
			},
		},
	)

	return response.data.data.state
}
