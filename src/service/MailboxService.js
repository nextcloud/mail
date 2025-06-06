/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'

export async function fetchAll(accountId) {
	const url = generateUrl('/apps/mail/api/mailboxes?accountId={accountId}', {
		accountId,
	})

	const resp = await axios.get(url)

	// FIXME: this return format is weird and should be avoided
	// TODO: respect `resp.data.delimiter` value
	return resp.data.mailboxes
}

export function create(accountId, name, specialUseAttributes) {
	const url = generateUrl('/apps/mail/api/mailboxes')

	const data = {
		accountId,
		name,
		specialUseAttributes,
	}
	return axios.post(url, data).then((resp) => resp.data)
}

export function getMailboxStatus(id) {
	const url = generateUrl('/apps/mail/api/mailboxes/{id}/stats', {
		id,
	})

	return axios.get(url).then((resp) => resp.data)
}

export function markMailboxRead(id) {
	const url = generateUrl('/apps/mail/api/mailboxes/{id}/read', {
		id,
	})

	return axios.post(url).then((resp) => resp.data)
}

export const deleteMailbox = async (id) => {
	const url = generateUrl('/apps/mail/api/mailboxes/{id}', {
		id,
	})

	await axios.delete(url)
}
export async function patchMailbox(id, data) {
	const url = generateUrl('/apps/mail/api/mailboxes/{id}', {
		id,
	})

	const response = await axios.patch(url, data)
	return response.data
}

export const clearMailbox = async (id) => {
	const url = generateUrl('/apps/mail/api/mailboxes/{id}/clear', {
		id,
	})

	await axios.post(url)
}

/**
 * Delete all vanished emails that are still cached.
 *
 * @param {number} id Mailbox database id
 * @return {Promise<void>}
 */
export const repairMailbox = async (id) => {
	const url = generateUrl('/apps/mail/api/mailboxes/{id}/repair', {
		id,
	})

	await axios.post(url)
}
