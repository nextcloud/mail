/**
 * @copyright Copyright (c) 2019 Marco Ambrosini <marcoambrosini@pm.me>
 *
 * @copyright Copyright (c) 2020 Gary Kim <gary@garykim.dev>
 *
 * @author Marco Ambrosini <marcoambrosini@pm.me>
 *
 * @author Gary Kim <gary@garykim.dev>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

// File originally from https://github.com/nextcloud/spreed/blob/bc22c21cf70a6243e7df7d838d49018c61468050/src/services/filesSharingServices.js
// Slightly modified for use in Mail

import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'
import { showError } from '@nextcloud/dialogs'

/**
 * Makes a share link for a given file or directory.
 *
 * @param {string} path The file path from the user's root directory. e.g. `/myfile.txt`
 * @param {string} token The conversation's token
 * @return {string} url share link
 */
const shareFile = async function(path, token) {
	try {
		const res = await axios.post(generateOcsUrl('apps/files_sharing/api/v1/', 2) + 'shares', {
			shareType: 3, // OC.Share.SHARE_TYPE_LINK,
			path,
			shareWith: token,
		})
		return res.data.ocs.data.url
	} catch (error) {
		if (
			error.response
			&& error.response.data
			&& error.response.data.ocs
			&& error.response.data.ocs.meta
			&& error.response.data.ocs.meta.message
		) {
			console.error(`Error while sharing file: ${error.response.data.ocs.meta.message || 'Unknown error'}`)
			showError(error.response.data.ocs.meta.message)
			throw error
		} else {
			console.error('Error while sharing file: Unknown error')
			showError(t('mail', 'Error while sharing file'))
			throw error
		}
	}
}

export { shareFile }
