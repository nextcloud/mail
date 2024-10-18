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

export async function createSnippet(snippet) {
	const url = generateUrl('/apps/mail/api/snippets')
	const response = await axios.post(url, { title: snippet.title, content: snippet.content })
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
	const url = generateUrl('/apps/mail/api/snippets', { id: snippet.id, title: snippet.title, content: snippet.content })
	await axios.put(url)
}

/**
 * @param {number} id
 * @return {Promise<void>}
 */
export async function deleteSnippet(id) {
	const url = generateUrl('/apps/mail/api/snippets', { id })
	await axios.delete(url)
}

/**
 * @param {number} id
 * @param {string} shareWith
 * @param {string} type
 * @return {Promise<void>}
 */
export async function shareSnippet(id, shareWith, type) {
	const url = generateUrl('/apps/mail/api/snippets/share', { id, shareWith, type })
	await axios.post(url)
}

/**
 * @param {number} snippetId
 * @return {Promise<void>}
 */
export async function getShares(snippetId) {
	const url = generateUrl('/apps/mail/api/snippets/share/shares', { snippetId })
	const response = await axios.get(url)
	return response.data.data
}
/**
 * @param {number} snippetId
 * @param {string} shareWith
 * @return {Promise<void>}
 */
export async function unshareSnippet(snippetId, shareWith) {
	const url = generateUrl('/apps/mail/api/snippets/share', { snippetId, shareWith })
	await axios.delete(url)
}
