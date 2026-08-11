/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export async function unsubscribe(id) {
	const url = generateUrl('/apps/mail/api/list/unsubscribe/{id}', {
		id,
	})

	axios.post(url)
}
