import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'

/**
 * @typedef {Object} Alias
 * @property {number} id
 * @property {string} alias
 * @property {string} name
 * @property {string} signature
 * @property {boolean} provisioned
 */

/**
 * @param {number} accountId id of account
 * @param {string} alias new alias
 * @param {string} aliasName new alias name
 * @returns {Promise<Alias>}
 */
export const createAlias = async(accountId, alias, aliasName) => {
	const url = generateUrl('/apps/mail/api/accounts/{id}/aliases', {
		id: accountId,
	})

	return axios.post(url, { alias, aliasName }).then(resp => resp.data)
}

/**
 * @param {number} accountId id of account
 * @param {number} aliasId if of alias
 * @returns {Promise<Alias>}
 */
export const deleteAlias = async(accountId, aliasId) => {
	const url = generateUrl('/apps/mail/api/accounts/{id}/aliases/{aliasId}', {
		id: accountId,
		aliasId,
	})

	return axios.delete(url).then((resp) => resp.data)
}

/**
 * @param {number} accountId id of account
 * @param {number} aliasId if of alias
 * @param {string} alias new alias
 * @param {string} aliasName new alias name
 * @returns {Promise<Alias>}
 */
export const updateAlias = async(accountId, aliasId, alias, aliasName) => {
	const url = generateUrl(
		'/apps/mail/api/accounts/{id}/aliases/{aliasId}', {
			id: accountId,
			aliasId,
		})

	return axios.put(url, { alias, aliasName }).then(resp => resp.data)
}

/**
 * @param {number} accountId id of account
 * @param {number} aliasId id of alias
 * @param {string} signature new signature
 * @returns {Promise<Alias>}
 */
export const updateSignature = async(accountId, aliasId, signature) => {
	const url = generateUrl(
		'/apps/mail/api/accounts/{id}/aliases/{aliasId}/signature', {
			id: accountId,
			aliasId,
		})

	return axios.put(url, { signature }).then(resp => resp.data)
}
