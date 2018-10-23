/*
 * @copyright 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

import Axios from 'nextcloud-axios'
import {generateUrl} from 'nextcloud-server/dist/router'

export function saveAttachmentToFiles (accountId, folderId, messageId, attachmentId, directory) {
	const url = generateUrl('apps/mail/api/accounts/{accountId}/folders/{folderId}/messages/{messageId}/attachment/{attachmentId}', {
		accountId,
		folderId,
		messageId,
		attachmentId,
	})

	return Axios.post(url, {
		targetPath: directory
	})
}

export function saveAttachmentsToFiles (accountId, folderId, messageId, directory) {
	return saveAttachmentToFiles(accountId, folderId, messageId, 0, directory)
}

export function downloadAttachment (url) {
	return Axios.get(url).then(res => res.data)
}
