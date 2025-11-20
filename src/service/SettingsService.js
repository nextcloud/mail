/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export function getProvisioningSettings(config) {
	const url = generateUrl('/apps/mail/api/settings/provisioning')

	return axios.get(url, config).then((resp) => resp.data)
}

export function provisionAll() {
	const url = generateUrl('/apps/mail/api/settings/provisioning/all')

	return axios.put(url).then((resp) => resp.data)
}

export function createProvisioningSettings(config) {
	const url = generateUrl('/apps/mail/api/settings/provisioning')
	const data = {
		data: config,
	}
	return axios.post(url, data).then((resp) => resp.data)
}

export function updateProvisioningSettings(config) {
	const url = generateUrl('/apps/mail/api/settings/provisioning/{id}', {
		id: config.id,
	})
	const data = {
		data: config,
	}
	return axios.post(url, data).then((resp) => resp.data)
}

export function disableProvisioning(id) {
	const url = generateUrl('/apps/mail/api/settings/provisioning/{id}', {
		id,
	})
	return axios.delete(url).then((resp) => resp.data)
}

export function setAntiSpamEmail(email) {
	return axios.post(generateUrl('/apps/mail/api/settings/antispam'), { spam: email.spam, ham: email.ham })
		.then((resp) => resp.data)
}

export function deleteAntiSpamEmail() {
	return axios.delete(generateUrl('/apps/mail/api/settings/antispam'))
		.then((resp) => resp.data)
}

export function updateAllowNewMailAccounts(allowed) {
	const url = generateUrl('/apps/mail/api/settings/allownewaccounts')
	const data = {
		allowed,
	}
	return axios.post(url, data).then((resp) => resp.data)
}

export async function updateLlmEnabled(enabled) {
	const url = generateUrl('/apps/mail/api/settings/llm')
	const data = {
		enabled,
	}
	const resp = await axios.put(url, data)
	return resp.data
}

export async function updateEnabledSmartReply(enabled) {
	const url = generateUrl('/apps/mail/api/settings/smartreply')
	const data = {
		enabled,
	}
	const resp = await axios.put(url, data)
	return resp.data
}
/**
 * @param {boolean} value
 * @return {Promise<void>}
 */
export async function setLayoutMessageView(value) {
	const url = generateUrl('/apps/mail/api/settings/layout-message-view')
	const data = {
		value,
	}
	const resp = await axios.put(url, data)
	return resp.data
}
