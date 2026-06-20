/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

/**
 * Fetch an external image through the server and return it as a data: URI so it
 * can be embedded into the editor and later sent as an inline attachment.
 *
 * @param {string} url The external image URL
 * @return {Promise<string>} The image encoded as a data: URI
 */
export async function fetchImageAsDataUri(url) {
	const endpoint = generateUrl('/apps/mail/api/image/proxy')
	const { data } = await axios.get(endpoint, { params: { url } })
	return data.data
}
