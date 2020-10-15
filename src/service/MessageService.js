import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { curry } from 'ramda'

import { parseErrorResponse } from '../http/ErrorResponseParser'
import { convertAxiosError } from '../errors/convert'
import SyncIncompleteError from '../errors/SyncIncompleteError'

const amendEnvelopeWithIds = curry((accountId, envelope) => ({
	accountId,
	...envelope,
}))

export function fetchEnvelope(id) {
	const url = generateUrl('/apps/mail/api/messages/{id}', {
		id,
	})

	return axios
		.get(url)
		.then((resp) => resp.data)
		.catch((error) => {
			if (error.response && error.response.status === 404) {
				return undefined
			}
			return Promise.reject(parseErrorResponse(error.response))
		})
}

export function fetchEnvelopes(mailboxId, query, cursor, limit) {
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

	return axios
		.get(url, {
			params,
		})
		.then((resp) => resp.data)
		.catch((error) => {
			throw convertAxiosError(error)
		})
}
export const fetchThread = async(id) => {
	const url = generateUrl('apps/mail/api/messages/{id}/thread', {
		id,
	})
	const resp = await axios.get(url)
	return resp.data
}

export async function syncEnvelopes(accountId, id, ids, query, init = false) {
	const url = generateUrl('/apps/mail/api/mailboxes/{id}/sync', {
		id,
	})

	try {
		const response = await axios.post(url, {
			ids,
			query,
			init,
		})

		if (response.status === 202) {
			throw new SyncIncompleteError()
		}

		const amend = amendEnvelopeWithIds(accountId)
		return {
			newMessages: response.data.newMessages.map(amend),
			changedMessages: response.data.changedMessages.map(amend),
			vanishedMessages: response.data.vanishedMessages,
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

export function setEnvelopeFlag(id, flag, value) {
	const url = generateUrl('/apps/mail/api/messages/{id}/flags', {
		id,
	})

	return axios
		.put(url, {
			flags: {
				[flag]: value,
			},
		})
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

export async function sendMessage(accountId, data) {
	const url = generateUrl('/apps/mail/api/accounts/{accountId}/send', {
		accountId,
	})

	try {
		const resp = await axios.post(url, data)
		return resp.data
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
