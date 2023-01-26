import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'

export const fixAccountId = (original) => {
	return {
		id: original.accountId,
		...original,
	}
}

export const create = (data) => {
	const url = generateUrl('/apps/mail/api/accounts')

	return axios
		.post(url, data)
		.then((resp) => resp.data.data)
		.then(fixAccountId)
		.catch((e) => {
			if (e.response && e.response.status === 400) {
				throw e.response.data
			}

			throw e
		})
}

export const patch = (account, data) => {
	const url = generateUrl('/apps/mail/api/accounts/{id}', {
		id: account.accountId,
	})

	return axios
		.patch(url, data)
		.then((resp) => resp.data)
		.then(fixAccountId)
}

export const update = (data) => {
	const url = generateUrl('/apps/mail/api/accounts/{id}', {
		id: data.accountId,
	})

	return axios
		.put(url, data)
		.then((resp) => resp.data.data)
		.then(fixAccountId)
		.catch((e) => {
			if (e.response && e.response.status === 400) {
				throw e.response.data
			}

			throw e
		})
}

export const updateSignature = (account, signature) => {
	const url = generateUrl('/apps/mail/api/accounts/{id}/signature', {
		id: account.id,
	})
	const data = {
		signature,
	}

	return axios
		.put(url, data)
		.then((resp) => resp.data)
		.then(fixAccountId)
}

export const fetchAll = () => {
	const url = generateUrl('/apps/mail/api/accounts')

	return axios.get(url).then((resp) => resp.data.map(fixAccountId))
}

export const fetch = (id) => {
	const url = generateUrl('/apps/mail/api/accounts/{id}', {
		id,
	})

	return axios.get(url).then((resp) => fixAccountId(resp.data))
}

export const fetchQuota = async (id) => {
	const url = generateUrl('/apps/mail/api/accounts/{id}/quota', {
		id,
	})

	try {
		const resp = await axios.get(url)

		return resp.data.data
	} catch (e) {
		if ('response' in e && e.response.status === 501) {
			// The server does not support quota
			return false
		}
		// Something else
		throw e
	}
}

export const deleteAccount = (id) => {
	const url = generateUrl('/apps/mail/api/accounts/{id}', {
		id,
	})

	return axios.delete(url).then((resp) => fixAccountId(resp.data))
}

export const updateSmimeCertificate = async (id, smimeCertificateId) => {
	const url = generateUrl('/apps/mail/api/accounts/{id}/smime-certificate', {
		id,
	})

	const response = await axios.put(url, { smimeCertificateId })
	return response.data.data
}
