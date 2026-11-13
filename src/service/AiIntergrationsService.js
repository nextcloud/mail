/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { convertAxiosError } from '../errors/convert.js'

export async function summarizeThread(threadId) {
	const url = generateUrl('/apps/mail/api/thread/{threadId}/summary', {
		threadId,
	})

	try {
		const resp = await axios.get(url)
		if (resp.status === 204) {
			throw new Error('Thread summary failed, error in the llm service')
		}
		return resp.data.data
	} catch (e) {
		throw convertAxiosError(e)
	}
}

export async function generateEventData(threadId) {
	const url = generateUrl('/apps/mail/api/thread/{threadId}/eventdata', {
		threadId,
	})

	try {
		const resp = await axios.get(url)
		return resp.data.data
	} catch (e) {
		throw convertAxiosError(e)
	}
}

export async function smartReply(messageId) {
	const url = generateUrl('/apps/mail/api/messages/{messageId}/smartreply', {
		messageId,
	})

	try {
		const resp = await axios.get(url)
		if (resp.status === 204) {
			throw new Error('Smart replies failed, error in the llm service')
		}
		return resp.data
	} catch (e) {
		throw convertAxiosError(e)
	}
}

export async function translateMessage(messageId, fromLanguage, toLanguage) {
	const url = generateUrl('/apps/mail/api/messages/{messageId}/translate', {
		messageId,
	})

	try {
		const resp = await axios.post(url, { fromLanguage, toLanguage })
		if (resp.status === 204) {
			throw new Error('Translation failed, error in the llm service')
		}
		return resp.data.data
	} catch (e) {
		throw convertAxiosError(e)
	}
}
