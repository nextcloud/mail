/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getClient } from '../dav/client.js'

export async function getFileSize(path) {
	const response = await getClient('files').stat(path, {
		data: `<?xml version="1.0"?>
			<d:propfind  xmlns:d="DAV:"
				xmlns:oc="http://owncloud.org/ns">
				<d:prop>
					<oc:size />
				</d:prop>
			</d:propfind>`,
		details: true,
	})

	return response?.data?.props?.size
}

export async function getFileData(path) {
	const response = await getClient('files').stat(path, {
		data: `<?xml version="1.0"?>
			<d:propfind
			xmlns:d="DAV:"
			xmlns:oc="http://owncloud.org/ns"
			xmlns:nc="http://nextcloud.org/ns">
				<d:prop>
					<oc:size />
					<oc:fileid />
					<nc:has-preview />
				</d:prop>
			</d:propfind>`,
		details: true,
	})

	return response?.data?.props
}
