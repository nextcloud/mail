import {generateUrl} from '@nextcloud/router'
import axios from '@nextcloud/axios'
import {curry, map} from 'ramda'

import {parseErrorResponse} from '../http/ErrorResponseParser'
import {convertAxiosError} from '../errors/convert'
import SyncIncompleteError from '../errors/SyncIncompleteError'

const amendEnvelopeWithIds = curry((accountId, folderId, envelope) => ({
	accountId,
	folderId,
	uid: `${accountId}-${folderId}-${envelope.id}`,
	...envelope,
}))

export function fetchEnvelope(accountId, folderId, id) {
	const url = generateUrl('/apps/mail/api/accounts/{accountId}/folders/{folderId}/messages/{id}', {
		accountId,
		folderId,
		id,
	})

	return axios
		.get(url)
		.then((resp) => amendEnvelopeWithIds(accountId, folderId, resp.data))
		.catch((error) => {
			if (error.response && error.response.status === 404) {
				return undefined
			}
			return Promise.reject(parseErrorResponse(error.response))
		})
}

export function fetchEnvelopes(accountId, folderId, query, cursor, limit) {
	const url = generateUrl('/apps/mail/api/accounts/{accountId}/folders/{folderId}/messages', {
		accountId,
		folderId,
	})
	const params = {}

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
		.then(map(amendEnvelopeWithIds(accountId, folderId)))
		.catch((error) => {
			throw convertAxiosError(error)
		})
}

export async function syncEnvelopes(accountId, folderId, uids, query, init = false) {
	const url = generateUrl('/apps/mail/api/accounts/{accountId}/folders/{folderId}/sync', {
		accountId,
		folderId,
	})

	try {
		const response = await axios.post(url, {
			uids,
			query,
			init,
		})

		if (response.status === 202) {
			throw new SyncIncompleteError()
		}

		const amend = amendEnvelopeWithIds(accountId, folderId)
		return {
			newMessages: response.data.newMessages.map(amend),
			changedMessages: response.data.changedMessages.map(amend),
			vanishedMessages: response.data.vanishedMessages,
		}
	} catch (e) {
		throw convertAxiosError(e)
	}
}

export async function clearCache(accountId, folderId) {
	const url = generateUrl('/apps/mail/api/accounts/{accountId}/folders/{folderId}/sync', {
		accountId,
		folderId,
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

export function setEnvelopeFlag(accountId, folderId, id, flag, value) {
	const url = generateUrl('/apps/mail/api/accounts/{accountId}/folders/{folderId}/messages/{id}/flags', {
		accountId,
		folderId,
		id,
	})

	const flags = {}
	flags[flag] = value

	return axios
		.put(url, {
			flags: flags,
		})
		.then(() => {
			value
		})
}

export function fetchMessage(accountId, folderId, id) {
	const url = generateUrl('/apps/mail/api/accounts/{accountId}/folders/{folderId}/messages/{id}/body', {
		accountId,
		folderId,
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

export async function saveDraft(accountId, data) {
	const url = generateUrl('/apps/mail/api/accounts/{accountId}/draft', {
		accountId,
	})

	return (await axios.post(url, data)).data
}

export function sendMessage(accountId, data) {
	const url = generateUrl('/apps/mail/api/accounts/{accountId}/send', {
		accountId,
	})

	return axios.post(url, data).then((resp) => resp.data)
}

export function deleteMessage(accountId, folderId, id) {
	const url = generateUrl('/apps/mail/api/accounts/{accountId}/folders/{folderId}/messages/{id}', {
		accountId,
		folderId,
		id,
	})

	return axios.delete(url).then((resp) => resp.data)
}
