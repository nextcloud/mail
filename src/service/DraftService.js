/**
 * @copyright 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

import { convertAxiosError } from '../errors/convert'

export async function saveDraft(accountId, data) {
	const url = generateUrl('/apps/mail/api/drafts', {
		accountId,
	})

	try {
		return (await axios.post(url, data)).data.data
	} catch (e) {
		throw convertAxiosError(e)
	}
}

export async function updateDraft(data) {
	const url = generateUrl('/apps/mail/api/drafts/{id}', {
		id: data.id,
	})

	try {
		return (await axios.put(url, data)).data.data
	} catch (e) {
		throw convertAxiosError(e)
	}
}

export async function deleteDraft(id) {
	const url = generateUrl('/apps/mail/api/drafts/{id}', {
		id,
	})

	try {
		return (await axios.delete(url)).data.data
	} catch (e) {
		throw convertAxiosError(e)
	}
}

export async function moveDraft(id) {
	const url = generateUrl('/apps/mail/api/drafts/move/{id}', {
		id,
	})

	try {
		return (await axios.post(url)).data.data
	} catch (e) {
		throw convertAxiosError(e)
	}
}
