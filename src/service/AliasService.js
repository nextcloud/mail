import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'

export const createAlias = async(account, data) => {
	const url = generateUrl('/apps/mail/api/accounts/{id}/aliases', {
		id: account.accountId,
	})

	return axios
		.post(url, data)
		.then((resp) => resp.data)
		.catch((e) => {
			if (e.response && e.response.status === 400) {
				throw e.response.data
			}

			throw e
		})
}

export const deleteAlias = async(account, alias) => {
	const url = generateUrl('/apps/mail/api/accounts/{id}/aliases/{aliasId}', {
		id: account.accountId,
		aliasId: alias.id,
	})

	return axios.delete(url).then((resp) => resp.data)
}
