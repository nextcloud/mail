/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'

/**
 * @param {string} url the resource to load
 * @param {string|null} etag the ETag of the known version of the resource
 * @return {Promise<{data: *, etag: string}|null>} the resource, or null if the known version is still current
 */
export async function fetchIfChanged(url, etag) {
	const response = await axios.get(url, {
		headers: etag ? { 'If-None-Match': etag } : {},
		validateStatus: (status) => (status >= 200 && status < 300) || status === 304,
	})
	if (response.status === 304) {
		return null
	}

	return {
		data: response.data.data,
		etag: response.headers.etag,
	}
}
