/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export const getProvisioningSettings = (config) => {
	const url = generateUrl('/apps/mail/api/settings/provisioning')

	return axios.get(url, config).then((resp) => resp.data)
}

export const provisionAll = () => {
	const url = generateUrl('/apps/mail/api/settings/provisioning/all')

	return axios.put(url).then((resp) => resp.data)
}

export const createProvisioningSettings = (config) => {
	const url = generateUrl('/apps/mail/api/settings/provisioning')
	const data = {
		data: config,
	}
	return axios.post(url, data).then((resp) => resp.data)
}

export const updateProvisioningSettings = (config) => {
	const url = generateUrl('/apps/mail/api/settings/provisioning/{id}', {
		id: config.id,
	})
	const data = {
		data: config,
	}
	return axios.post(url, data).then((resp) => resp.data)
}

export const disableProvisioning = (id) => {
	const url = generateUrl('/apps/mail/api/settings/provisioning/{id}', {
		id,
	})
	return axios.delete(url).then((resp) => resp.data)
}

export const setAntiSpamEmail = (email) => {
	return axios.post(generateUrl('/apps/mail/api/settings/antispam'), { spam: email.spam, ham: email.ham })
		.then((resp) => resp.data)
}

export const deleteAntiSpamEmail = () => {
	return axios.delete(generateUrl('/apps/mail/api/settings/antispam'))
		.then((resp) => resp.data)
}

export const updateAllowNewMailAccounts = (allowed) => {
	const url = generateUrl('/apps/mail/api/settings/allownewaccounts')
	const data = {
		allowed,
	}
	return axios.post(url, data).then((resp) => resp.data)
}

export const updateLlmEnabled = async (enabled) => {
	const url = generateUrl('/apps/mail/api/settings/llm')
	const data = {
		enabled,
	}
	const resp = await axios.put(url, data)
	return resp.data
}

export const updateEnabledSmartReply = async (enabled) => {
	const url = generateUrl('/apps/mail/api/settings/smartreply')
	const data = {
		enabled,
	}
	const resp = await axios.put(url, data)
	return resp.data
}

/**
 * @param {boolean} enabledByDefault
 * @return {Promise<void>}
 */
export const setImportanceClassificationEnabledByDefault = async (enabledByDefault) => {
	const url = generateUrl('/apps/mail/api/settings/importance-classification-default')
	await axios.put(url, {
		enabledByDefault,
	})
}

/**
 * @param {boolean} value
 * @return {Promise<void>}
 */
export const setLayoutMessageView = async (value) => {
	const url = generateUrl('/apps/mail/api/settings/layout-message-view')
	const data = {
		value,
	}
	const resp = await axios.put(url, data)
	return resp.data
}
