/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { handleHttpAuthErrors } from '../http/sessionExpiryHandler.js'

/**
 * @return {Promise<object[]>}
 */
export async function fetchAllQuickActions() {
	const url = generateUrl('/apps/mail/api/quick-actions')
	return handleHttpAuthErrors(async () => {
		const response = await axios.get(url)
		return response.data.data
	})

}

export async function createQuickAction(name, accountId) {
	const url = generateUrl('/apps/mail/api/quick-actions')
	return handleHttpAuthErrors(async () => {
		const response = await axios.post(url, { name, accountId })
		return response.data.data
	})

}

export async function updateQuickAction(id, name) {
	const url = generateUrl('/apps/mail/api/quick-actions/{id}', { id })
	return handleHttpAuthErrors(async () => {
		return (await axios.put(url, { name })).data.data

	})
}

export async function deleteQuickAction(id) {
	const url = generateUrl('/apps/mail/api/quick-actions/{id}', { id })
	return handleHttpAuthErrors(async () => {
		await axios.delete(url)
	})
}

export async function findAllStepsForAction(actionId) {
	const url = generateUrl('/apps/mail/api/action-step/{id}/steps', { id: actionId })
	return handleHttpAuthErrors(async () => {
		const response = await axios.get(url)
		return response.data.data
	})

}

export async function createActionStep(name, order, actionId, tagId = null, mailboxId = null) {
	const url = generateUrl('/apps/mail/api/action-step')
	return handleHttpAuthErrors(async () => {
		const response = await axios.post(url, { name, order, actionId, tagId, mailboxId })
		return response.data.data
	})

}

export async function updateActionStep(id, name, order, tagId, mailboxId) {
	const url = generateUrl('/apps/mail/api/action-step/{id}', { id })
	return handleHttpAuthErrors(async () => {
		return (await axios.put(url, { name, order, tagId, mailboxId })).data.data

	})
}

export async function deleteActionStep(id) {
	const url = generateUrl('/apps/mail/api/action-step/{id}', { id })
	return handleHttpAuthErrors(async () => {
		await axios.delete(url)
	})
}
