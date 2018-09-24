import HttpClient from 'nextcloud-axios'

export function fetchEnvelopes (accountId, folderId, cursor) {
	const url = OC.generateUrl('/apps/mail/api/accounts/{accountId}/folders/{folderId}/messages', {
		accountId,
		folderId,
	})
	const params = {}

	if (cursor) {
		params.cursor = cursor
	}

	return HttpClient.get(url, {
		params: params
	}).then(resp => resp.data)
}

export function fetchMessage (accountId, folderId, id) {
	const url = OC.generateUrl('/apps/mail/api/accounts/{accountId}/folders/{folderId}/messages/{id}', {
		accountId,
		folderId,
		id
	})

	return HttpClient.get(url).then(resp => resp.data)
}
