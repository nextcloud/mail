import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'

/**
 * @typedef {object} Alias
 * @property {number} id the id
 * @property {string} alias alias address
 * @property {string} name alias display name
 * @property {string} signature alias signature
 * @property {boolean} provisioned whether the alias comes from LDAP
 */

/**
 * @param {number} accountId id of account
 * @param {string} alias new alias
 * @param {string} aliasName new alias name
 * @return {Promise<Alias>}
 */
export const createAlias = async (accountId, alias, aliasName) => {
	const url = generateUrl('/apps/mail/api/accounts/{id}/aliases', {
		id: accountId,
	})

	return axios.post(url, { alias, aliasName }).then(resp => resp.data)
}

/**
 * @param {number} accountId id of account
 * @param {number} aliasId if of alias
 * @return {Promise<Alias>}
 */
export const deleteAlias = async (accountId, aliasId) => {
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
 * @return {Promise<Alias>}
 */
export const updateAlias = async (accountId, aliasId, alias, aliasName) => {
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
 * @return {Promise<Alias>}
 */
export const updateSignature = async (accountId, aliasId, signature) => {
	const url = generateUrl(
		'/apps/mail/api/accounts/{id}/aliases/{aliasId}/signature', {
			id: accountId,
			aliasId,
		})

	return axios.put(url, { signature }).then(resp => resp.data)
}
