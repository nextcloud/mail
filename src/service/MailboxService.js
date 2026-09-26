/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export async function fetchAll(accountId, forceSync = false) {
	const url = generateUrl(`/apps/mail/api/mailboxes?accountId={accountId}&forceSync=${forceSync}`, {
		accountId,
	})

	const resp = await axios.get(url)

	// FIXME: this return format is weird and should be avoided
	// TODO: respect `resp.data.delimiter` value
	return resp.data.mailboxes
}

export function create(accountId, name) {
	const url = generateUrl('/apps/mail/api/mailboxes')

	const data = {
		accountId,
		name,
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

export async function setMailboxFlags(id, filter, flags) {
	const url = generateUrl('/apps/mail/api/mailboxes/{id}/flags', {
		id,
	})

	await axios.put(url, { filter, flags })
}

export async function moveMailboxMessages(id, filter, destinationId) {
	const url = generateUrl('/apps/mail/api/mailboxes/{id}/move-messages', {
		id,
	})

	await axios.post(url, { filter, destinationId })
}

export async function deleteMailboxMessages(id, filter) {
	const url = generateUrl('/apps/mail/api/mailboxes/{id}/delete-messages', {
		id,
	})

	await axios.post(url, { filter })
}

export async function setMailboxTag(id, filter, imapLabel) {
	const url = generateUrl('/apps/mail/api/mailboxes/{id}/tags/{imapLabel}', {
		id,
		imapLabel,
	})

	const { data } = await axios.put(url, { filter })
	return data
}

export async function removeMailboxTag(id, filter, imapLabel) {
	const url = generateUrl('/apps/mail/api/mailboxes/{id}/tags/{imapLabel}', {
		id,
		imapLabel,
	})

	const { data } = await axios.delete(url, { params: { filter } })
	return data
}

export async function setMailboxJunk(id, filter, junk) {
	const url = generateUrl('/apps/mail/api/mailboxes/{id}/junk', {
		id,
	})

	const { data } = await axios.put(url, { filter, junk })
	return data.moved
}

export async function deleteMailbox(id) {
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

export async function clearMailbox(id) {
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
export async function repairMailbox(id) {
	const url = generateUrl('/apps/mail/api/mailboxes/{id}/repair', {
		id,
	})

	await axios.post(url)
}
