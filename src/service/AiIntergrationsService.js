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

export async function needsTranslation(messageId) {
	const url = generateUrl('/apps/mail/api/messages/{messageId}/needsTranslation', {
		messageId,
	})

	try {
		const resp = await axios.get(url)
		if (resp.status === 204) {
			throw new Error('Checking whether translation is needed failed, error in the llm service')
		}
		if (resp.status === 501) {
			throw new Error('Please enable llm services for groupware to use this feature')
		}
		return resp.data.requiresTranslation
	} catch (e) {
		throw convertAxiosError(e)
	}
}
