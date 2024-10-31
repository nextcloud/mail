/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'

/**
 * @return {Promise<object[]>}
 */
export async function fetchMySnippets() {
	const url = generateUrl('/apps/mail/api/snippets')
	const response = await axios.get(url)
	return response.data.data
}

/**
 * @return {Promise<object[]>}
 */
export async function fetchSharedSnippets() {
	const url = generateUrl('/apps/mail/api/snippets/share')
	const response = await axios.get(url)
	return response.data.data
}

/**
 *
 * @param {object} snippet
 * @param {string} snippet.title
 * @param {string} snippet.content
 * @return {Promise<object>}
 */

export async function createSnippet(title, content) {
	const url = generateUrl('/apps/mail/api/snippets')
	const response = await axios.post(url, { title, content })
	return response.data.data
}

/**
 * @param {object} snippet
 * @param {number} snippet.id
 * @param {string} snippet.title
 * @param {string} snippet.content
 * @return {Promise<void>}
 */
export async function updateSnippet(snippet) {
	const url = generateUrl('/apps/mail/api/snippets')
	await axios.put(url, { id: snippet.id, title: snippet.title, content: snippet.content })
}

/**
 * @param {number} id
 * @return {Promise<void>}
 */
export async function deleteSnippet(id) {
	const url = generateUrl('/apps/mail/api/snippets/{id}', { id })
	await axios.delete(url)
}

/**
 * @param {number} id
 * @param {string} shareWith
 * @param {string} type
 * @return {Promise<void>}
 */
export async function shareSnippet(id, shareWith, type) {
	const url = generateUrl('/apps/mail/api/snippets/share')
	await axios.post(url, { id, shareWith, type })
}

/**
 * @param {number} id
 * @return {Promise<void>}
 */
export async function getShares(id) {
	const url = generateUrl('/apps/mail/api/snippets/share/shares/{id}', { id })
	const response = await axios.get(url)
	return response.data.data
}
/**
 * @param {number} snippetId
 * @param {string} shareWith
 * @return {Promise<void>}
 */
export async function unshareSnippet(snippetId, shareWith) {
	const url = generateUrl('/apps/mail/api/snippets/share')
	await axios.delete(url, { snippetId, shareWith })
}
