/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'

/**
 * @typedef {{ enabled: boolean, start: string, end: string, subject: string, message: string }} OutOfOfficeState
 */

/**
 * @typedef {{ state: OutOfOfficeState, script: string, untouchedScript: string }} OutOfOfficeStateResponse
 */

/**
 * @param {number} accountId
 * @return {Promise<OutOfOfficeStateResponse>}
 */
export async function fetch(accountId) {
	const url = generateUrl('/apps/mail/api/out-of-office/{accountId}', { accountId })

	const { data } = await axios.get(url)
	return data.data
}

/**
 * @param {number} accountId
 * @param {OutOfOfficeState} outOfOfficeState
 * @return {Promise<OutOfOfficeStateResponse>}
 */
export async function update(accountId, outOfOfficeState) {
	const url = generateUrl('/apps/mail/api/out-of-office/{accountId}', { accountId })

	const { data } = await axios.post(url, outOfOfficeState)
	return data.data
}

/**
 * @param {number} accountId
 * @return {Promise<OutOfOfficeStateResponse>}
 */
export async function followSystem(accountId) {
	const url = generateUrl('/apps/mail/api/out-of-office/{accountId}/follow-system', { accountId })

	const { data } = await axios.post(url)
	return data.data
}
