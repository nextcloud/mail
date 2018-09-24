import HttpClient from 'nextcloud-axios'

export function fetchEnvelopes (accountId, folderId) {
	const url = OC.generateUrl('/apps/mail/api/accounts/{accountId}/folders/{folderId}/messages', {
		accountId,
		folderId,
	})

	return HttpClient.get(url).then(resp => resp.data)
}

export function fetchMessage (accountId, folderId, id) {
	const url = OC.generateUrl('/apps/mail/api/accounts/{accountId}/folders/{folderId}/messages/{id}', {
		accountId,
		folderId,
		id
	})

	return HttpClient.get(url).then(resp => resp.data)
}
