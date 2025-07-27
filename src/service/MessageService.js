/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { curry } from 'ramda'

import { parseErrorResponse } from '../http/ErrorResponseParser.js'
import { convertAxiosError } from '../errors/convert.js'
import SyncIncompleteError from '../errors/SyncIncompleteError.js'

const amendEnvelopeWithIds = curry((accountId, envelope) => ({
	accountId,
	...envelope,
}))

export function fetchEnvelope(accountId, id) {
	const url = generateUrl('/apps/mail/api/messages/{id}', {
		id,
	})

	return axios
		.get(url)
		.then((resp) => resp.data)
		.then(amendEnvelopeWithIds(accountId))
		.catch((error) => {
			if (error.response && error.response.status === 404) {
				return undefined
			}
			return Promise.reject(parseErrorResponse(error.response))
		})
}

export function fetchEnvelopes(accountId, mailboxId, query, cursor, limit, sort, view, cacheBuster) {
	const url = generateUrl('/apps/mail/api/messages')
	const params = {
		mailboxId,
	}

	if (query) {
		params.filter = query
	}
	if (limit) {
		params.limit = limit
	}
	if (cursor) {
		params.cursor = cursor
	}
	if (sort) {
		params.sort = sort
	}
	if (view) {
		params.view = view
	}
	if (cacheBuster) {
		params.v = cacheBuster
	}

	return axios
		.get(url, {
			params,
		})
		.then((resp) => resp.data)
		.then(envelopes => envelopes.map(amendEnvelopeWithIds(accountId)))
		.catch((error) => {
			throw convertAxiosError(error)
		})
}
export const fetchThread = async (id) => {
	const url = generateUrl('apps/mail/api/messages/{id}/thread', {
		id,
	})
	const resp = await axios.get(url)
	return resp.data
}

export async function syncEnvelopes(accountId, id, ids, lastMessageTimestamp, query, init = false, sortOrder) {
	const url = generateUrl('/apps/mail/api/mailboxes/{id}/sync', {
		id,
	})

	try {
		const response = await axios.post(url, {
			ids,
			lastMessageTimestamp,
			init,
			sortOrder,
			query,
		})

		if (response.status === 202) {
			throw new SyncIncompleteError()
		}

		const amend = amendEnvelopeWithIds(accountId)
		return {
			newMessages: response.data.newMessages.map(amend),
			changedMessages: response.data.changedMessages.map(amend),
			vanishedMessages: response.data.vanishedMessages,
			stats: response.data.stats,
		}
	} catch (e) {
		throw convertAxiosError(e)
	}
}

export async function clearCache(accountId, id) {
	const url = generateUrl('/apps/mail/api/mailboxes/{id}/sync', {
		id,
	})

	try {
		const response = await axios.delete(url)

		if (response.status === 202) {
			throw new SyncIncompleteError()
		}
	} catch (e) {
		throw convertAxiosError(e)
	}
}

/**
 * Set flags for envelope
 *
 * @param {number} id
 * @param {object} flags
 */
export async function setEnvelopeFlags(id, flags) {
	const url = generateUrl('/apps/mail/api/messages/{id}/flags', {
		id,
	})

	return await axios.put(url, {
		flags,
	})
}

export async function createEnvelopeTag(displayName, color) {
	const url = generateUrl('/apps/mail/api/tags')

	const { data } = await axios.post(url, { displayName, color })
	return data
}

export async function setEnvelopeTag(id, imapLabel) {
	const url = generateUrl('/apps/mail/api/messages/{id}/tags/{imapLabel}', {
		id, imapLabel,
	})

	const { data } = await axios.put(url)
	return data
}
export async function updateEnvelopeTag(id, displayName, color) {
	const url = generateUrl('/apps/mail/api/tags/{id}', {
		id,
	})

	await axios.put(url, { displayName, color })
}

export async function deleteTag(id, accountId) {
	const url = generateUrl('/apps/mail/api/tags/{accountId}/delete/{id}', {
		accountId, id,
	})

	await axios.delete(url)
}

export async function removeEnvelopeTag(id, imapLabel) {
	const url = generateUrl('/apps/mail/api/messages/{id}/tags/{imapLabel}', {
		id, imapLabel,
	})

	const { data } = await axios.delete(url)
	return data
}

export async function fetchMessage(id) {
	const url = generateUrl('/apps/mail/api/messages/{id}/body', {
		id,
	})

	try {
		const resp = await axios.get(url)
		return resp.data
	} catch (error) {
		if (error.response && error.response.status === 404) {
			return undefined
		}

		throw parseErrorResponse(error.response)
	}
}

export async function fetchMessageItineraries(id) {
	const url = generateUrl('/apps/mail/api/messages/{id}/itineraries', {
		id,
	})

	try {
		const resp = await axios.get(url)
		return resp.data
	} catch (error) {
		if (error.response && error.response.status === 404) {
			return undefined
		}

		throw parseErrorResponse(error.response)
	}
}

export async function fetchMessageDkim(id) {
	const url = generateUrl('/apps/mail/api/messages/{id}/dkim', {
		id,
	})

	try {
		const resp = await axios.get(url)
		return resp.data
	} catch (error) {
		if (error.response && error.response.status === 404) {
			return undefined
		}

		throw parseErrorResponse(error.response)
	}
}

export async function saveDraft(accountId, data) {
	const url = generateUrl('/apps/mail/api/accounts/{accountId}/draft', {
		accountId,
	})

	try {
		return (await axios.post(url, data)).data
	} catch (e) {
		throw convertAxiosError(e)
	}
}

export async function deleteMessage(id) {
	const url = generateUrl('/apps/mail/api/messages/{id}', {
		id,
	})

	try {
		return (await axios.delete(url)).data
	} catch (e) {
		throw convertAxiosError(e)
	}
}

export function moveMessage(id, destFolderId) {
	const url = generateUrl('/apps/mail/api/messages/{id}/move', {
		id,
	})

	return axios.post(url, {
		destFolderId,
	})
}

export function snoozeMessage(id, unixTimestamp, destMailboxId) {
	const url = generateUrl('/apps/mail/api/messages/{id}/snooze', {
		id,
	})

	return axios.post(url, {
		unixTimestamp,
		destMailboxId,
	})
}

export function unSnoozeMessage(id) {
	const url = generateUrl('/apps/mail/api/messages/{id}/unsnooze', {
		id,
	})

	return axios.post(url, {})
}

export async function sendMdn(id, data) {
	const url = generateUrl('/apps/mail/api/messages/{id}/mdn', {
		id,
	})

	try {
		await axios.post(url, data)
	} catch (e) {
		throw convertAxiosError(e)
	}
}
