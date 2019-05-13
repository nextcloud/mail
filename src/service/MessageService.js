import {generateUrl} from 'nextcloud-router'
import HttpClient from 'nextcloud-axios'

import {parseErrorResponse} from '../http/ErrorResponseParser'

export function fetchEnvelopes(accountId, folderId, query, cursor) {
	const url = generateUrl('/apps/mail/api/accounts/{accountId}/folders/{folderId}/messages', {
		accountId,
		folderId,
	})
	const params = {}

	if (query) {
		params.filter = query
	}
	if (cursor) {
		params.cursor = cursor
	}

	return HttpClient.get(url, {
		params: params,
	}).then(resp => resp.data)
}

export function syncEnvelopes(accountId, folderId, syncToken, uids) {
	const url = generateUrl('/apps/mail/api/accounts/{accountId}/folders/{folderId}/sync', {
		accountId,
		folderId,
	})

	return HttpClient.get(url, {
		params: {
			syncToken,
			uids,
		},
	}).then(resp => resp.data)
}

export function setEnvelopeFlag(accountId, folderId, id, flag, value) {
	const url = generateUrl('/apps/mail/api/accounts/{accountId}/folders/{folderId}/messages/{id}/flags', {
		accountId,
		folderId,
		id,
	})

	const flags = {}
	flags[flag] = value

	return HttpClient.put(url, {
		flags: flags,
	}).then(() => {
		value
	})
}

export function fetchMessage(accountId, folderId, id) {
	const url = generateUrl('/apps/mail/api/accounts/{accountId}/folders/{folderId}/messages/{id}', {
		accountId,
		folderId,
		id,
	})

	return HttpClient.get(url)
		.then(resp => resp.data)
		.catch(error => {
			if (error.response && error.response.status === 404) {
				return undefined
			}
			return Promise.reject(parseErrorResponse(error.response))
		})
}

export function saveDraft(accountId, data) {
	const url = generateUrl('/apps/mail/api/accounts/{accountId}/draft', {
		accountId,
	})

	return HttpClient.post(url, data).then(resp => resp.data)
}

export function sendMessage(accountId, data) {
	const url = generateUrl('/apps/mail/api/accounts/{accountId}/send', {
		accountId,
	})

	return HttpClient.post(url, data).then(resp => resp.data)
}

export function deleteMessage(accountId, folderId, id) {
	const url = generateUrl('/apps/mail/api/accounts/{accountId}/folders/{folderId}/messages/{id}', {
		accountId,
		folderId,
		id,
	})

	return HttpClient.delete(url).then(resp => resp.data)
}

export function moveMessage(accountId, startFolderId, targetFolderId, id) {
	const url = generateUrl('/apps/mail/api/accounts/{accountId}/folders/{startFolderId}/messages/{id}/move', {
		accountId,
		startFolderId,
		id,
	})

	return HttpClient.post(url, {destAccountId: accountId, destFolderId: targetFolderId}).then(resp => resp.data)
}
