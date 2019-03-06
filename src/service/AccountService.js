import {generateUrl} from 'nextcloud-server/dist/router'
import HttpClient from 'nextcloud-axios'

export const fixAccountId = original => {
	return {
		id: original.accountId,
		...original,
	}
}

export const create = data => {
	const url = generateUrl('/apps/mail/api/accounts')

	return HttpClient.post(url, data)
		.then(resp => resp.data)
		.then(fixAccountId)
}

export const update = data => {
	const url = generateUrl(`/apps/mail/api/accounts/{id}`, {
		id: data.accountId,
	})

	return HttpClient.put(url, data)
		.then(resp => resp.data)
		.then(fixAccountId)
}

export const fetchAll = () => {
	const url = generateUrl('/apps/mail/api/accounts')

	return HttpClient.get(url).then(resp => resp.data.map(fixAccountId))
}

export const fetch = id => {
	const url = generateUrl('/apps/mail/api/accounts/{id}', {
		id,
	})

	return HttpClient.get(url).then(resp => fixAccountId(resp.data))
}

export const deleteAccount = id => {
	const url = generateUrl('/apps/mail/api/accounts/{id}', {
		id,
	})

	return HttpClient.delete(url).then(resp => fixAccountId(resp.data))
}
