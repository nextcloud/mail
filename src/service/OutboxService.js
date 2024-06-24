/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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

export async function enqueueMessageFromDraft(id, message) {
	const url = generateUrl('/apps/mail/api/outbox/from-draft/{id}', {
		id,
	})

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
