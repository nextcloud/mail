import { generateUrl } from '@nextcloud/router'
import Axios from '@nextcloud/axios'

export function fetchAll(accountId) {
	const url = generateUrl('/apps/mail/api/accounts/{accountId}/folders', {
		accountId,
	})

	// FIXME: this return format is weird and should be avoided
	// TODO: respect `resp.data.delimiter` value
	return Axios.get(url).then((resp) => resp.data.folders)
}

export function create(accountId, name) {
	const url = generateUrl('/apps/mail/api/accounts/{accountId}/folders', {
		accountId,
	})

	const data = {
		name,
	}
	return Axios.post(url, data).then((resp) => resp.data)
}

export function getFolderStats(accountId, folderId) {
	const url = generateUrl('/apps/mail/api/accounts/{accountId}/folders/{folderId}/stats', {
		accountId,
		folderId,
	})

	return Axios.get(url).then((resp) => resp.data)
}

export function markFolderRead(accountId, folderId) {
	const url = generateUrl('/apps/mail/api/accounts/{accountId}/folders/{folderId}/read', {
		accountId,
		folderId,
	})

	return Axios.post(url).then((resp) => resp.data)
}
