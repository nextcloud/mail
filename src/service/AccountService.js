/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export function fixAccountId(original) {
	return {
		id: original.accountId,
		...original,
	}
}

export function create(data) {
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

export function patch(account, data) {
	const url = generateUrl('/apps/mail/api/accounts/{id}', {
		id: account.accountId,
	})

	return axios
		.patch(url, data)
		.then((resp) => resp.data)
		.then(fixAccountId)
}

export function update(data) {
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

export function updateSignature(account, signature) {
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

export function fetchAll() {
	const url = generateUrl('/apps/mail/api/accounts')

	return axios.get(url).then((resp) => resp.data.map(fixAccountId))
}

export function fetch(id) {
	const url = generateUrl('/apps/mail/api/accounts/{id}', {
		id,
	})

	return axios.get(url).then((resp) => fixAccountId(resp.data))
}

export async function fetchQuota(id) {
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

export function deleteAccount(id) {
	const url = generateUrl('/apps/mail/api/accounts/{id}', {
		id,
	})

	return axios.delete(url).then((resp) => fixAccountId(resp.data))
}

export async function updateSmimeCertificate(id, smimeCertificateId) {
	const url = generateUrl('/apps/mail/api/accounts/{id}/smime-certificate', {
		id,
	})

	const response = await axios.put(url, { smimeCertificateId })
	return response.data.data
}

export async function testAccountConnection(id) {
	const url = generateUrl('/apps/mail/api/accounts/{id}/test', {
		id,
	})

	const resp = await axios.get(url)
	return resp.data.data
}
