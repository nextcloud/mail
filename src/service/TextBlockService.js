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
export async function fetchMyTextBlocks() {
	const url = generateUrl('/apps/mail/api/textBlocks')
	return handleHttpAuthErrors(async () => {
		const response = await axios.get(url)
		return response.data.data
	})

}

/**
 * @return {Promise<object[]>}
 */
export async function fetchSharedTextBlocks() {
	const url = generateUrl('/apps/mail/api/textBlockshares')
	return handleHttpAuthErrors(async () => {
		const response = await axios.get(url)
		return response.data.data
	})
}

/**
 *
 * @param {object} textBlock
 * @param {string} textBlock.title
 * @param {string} textBlock.content
 * @return {Promise<object>}
 */

export async function createTextBlock(title, content) {
	const url = generateUrl('/apps/mail/api/textBlocks')
	return handleHttpAuthErrors(async () => {
		const response = await axios.post(url, { title, content })
		return response.data.data
	})

}

/**
 * @param {object} textBlock
 * @param {number} textBlock.id
 * @param {string} textBlock.title
 * @param {string} textBlock.content
 * @return {Promise<void>}
 */
export async function updateTextBlock(textBlock) {
	const url = generateUrl('/apps/mail/api/textBlocks/{id}', { id: textBlock.id })
	return handleHttpAuthErrors(async () => {
		return (await axios.put(url, { title: textBlock.title, content: textBlock.content })).data.data

	})
}

/**
 * @param {number} id
 * @return {Promise<void>}
 */
export async function deleteTextBlock(id) {
	const url = generateUrl('/apps/mail/api/textBlocks/{id}', { id })
	return handleHttpAuthErrors(async () => {
		await axios.delete(url)
	})
}

/**
 * @param {number} textBlockId
 * @param {string} shareWith
 * @param {string} type
 * @return {Promise<void>}
 */
export async function shareTextBlock(textBlockId, shareWith, type) {
	const url = generateUrl('/apps/mail/api/textBlockshares')
	return handleHttpAuthErrors(async () => {
		await axios.post(url, { textBlockId, shareWith, type })
	})

}

/**
 * @param {number} id
 * @return {Promise<void>}
 */
export async function getShares(id) {
	const url = generateUrl('/apps/mail/api/textBlocks/{id}/shares', { id })
	return handleHttpAuthErrors(async () => {
		const response = await axios.get(url)
		return response.data.data
	})
}
/**
 * @param {number} textBlockId
 * @param {string} shareWith
 * @return {Promise<void>}
 */
export async function unshareTextBlock(textBlockId, shareWith) {
	const url = generateUrl('/apps/mail/api/textBlockshares/{textBlockId}', { textBlockId })
	return handleHttpAuthErrors(async () => {
		await axios.delete(url, { data: { shareWith } })
	})

}
