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

export async function fetchMessages() {
	const url = generateUrl('/apps/mail/api/outbox')

	const { data } = await axios.get(url)
	return data.data
}

export async function deleteMessage(id) {
	const url = generateUrl('/apps/mail/api/outbox/{id}', {
		id,
	})

	const { data } = await axios.delete(url)
	return data
}

export async function enqueueMessage(message) {
	const url = generateUrl('/apps/mail/api/outbox')

	const { data } = await axios.post(url, message)
	return data.data
}
export async function updateMessage(message, id) {
	const url = generateUrl('/apps/mail/api/outbox/{id}', {
		id,
	})

	const { data } = await axios.put(url, message)
	return data.data
}

export async function sendMessage(id) {
	const url = generateUrl('/apps/mail/api/outbox/{id}', {
		id,
	})

	const { data } = await axios.post(url)
	return data
}
