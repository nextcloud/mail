/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { convertAxiosError } from '../errors/convert.js'

export async function updateAccount(id, data) {
	const url = generateUrl('/apps/mail/api/sieve/account/{id}', {
		id,
	})

	try {
		return (await axios.put(url, data)).data
	} catch (error) {
		throw convertAxiosError(error)
	}
}

/**
 * Fetch active sieve script of given account id.
 *
 * @param {string} id Account id
 * @return {Promise<{script: string, scriptName: string}>}
 */
export async function getActiveScript(id) {
	const url = generateUrl('/apps/mail/api/sieve/active/{id}', {
		id,
	})

	try {
		return (await axios.get(url)).data
	} catch (error) {
		throw convertAxiosError(error)
	}
}

/**
 * Update active sieve script of given account id.
 *
 * @param {string} id Account id
 * @param {{script: string, scriptName: string}} data Script data object
 * @return {Promise<void>}
 */
export async function updateActiveScript(id, data) {
	const url = generateUrl('/apps/mail/api/sieve/active/{id}', {
		id,
	})

	try {
		return (await axios.put(url, data)).data
	} catch (error) {
		throw convertAxiosError(error)
	}
}
