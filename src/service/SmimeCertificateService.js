/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'

/**
 * @return {Promise<object[]>}
 */
export async function fetchAll() {
	const url = generateUrl('/apps/mail/api/smime/certificates')
	const response = await axios.get(url)
	return response.data.data
}

/**
 * @param {number} id
 * @return {Promise<void>}
 */
export async function deleteCertificate(id) {
	const url = generateUrl('/apps/mail/api/smime/certificates/{id}', { id })
	await axios.delete(url)
}

/**
 *
 * @param {object} files
 * @param {Blob} files.certificate
 * @param {Blob=} files.privateKey
 * @return {Promise<object>}
 */
export async function createCertificate(files) {
	const url = generateUrl('/apps/mail/api/smime/certificates')
	const form = new FormData()
	form.append('certificate', files.certificate)
	if (files.privateKey) {
		form.append('privateKey', files.privateKey)
	}
	const response = await axios.post(url, form)
	return response.data.data
}
