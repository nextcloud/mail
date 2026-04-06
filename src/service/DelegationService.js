/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

/**
 * @typedef Delegation
 * @property {number} id the delegation id
 * @property {number} accountId the account id
 * @property {string} userId the delegated user id
 */

/**
 * Fetch all users that have delegation for a given account
 *
 * @param {number} accountId id of the account
 * @return {Promise<Delegation[]>}
 */
export async function fetchDelegatedUsers(accountId) {
	const url = generateUrl('/apps/mail/api/delegations/{accountId}', {
		accountId,
	})

	return axios.get(url).then((resp) => resp.data)
}

/**
 * Delegate an account to a user
 *
 * @param {number} accountId id of the account
 * @param {string} userId id of the user to delegate to
 * @return {Promise<Delegation>}
 */
export async function delegate(accountId, userId) {
	const url = generateUrl('/apps/mail/api/delegations/{accountId}', {
		accountId,
	})

	return axios.post(url, { userId }).then((resp) => resp.data)
}

/**
 * Revoke delegation of an account for a user
 *
 * @param {number} accountId id of the account
 * @param {string} userId id of the user to revoke delegation for
 * @return {Promise}
 */
export async function unDelegate(accountId, userId) {
	const url = generateUrl('/apps/mail/api/delegations/{accountId}/{userId}', {
		accountId,
		userId,
	})

	return axios.delete(url).then((resp) => resp.data)
}
