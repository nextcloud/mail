/**
 * @copyright Copyright (c) 2022 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
