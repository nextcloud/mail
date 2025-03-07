/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { convertAxiosError } from '../errors/convert.js'

export const summarizeThread = async (threadId) => {
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

export const generateEventData = async (threadId) => {
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

export const smartReply = async (messageId) => {
	const url = generateUrl('/apps/mail/api/messages/{messageId}/smartreply', {
		messageId,
	})

	try {
		const resp = await axios.get(url)
		if (resp.status === 204) {
			throw new Error('Thread summary failed, error in the llm service')
		}
		return resp.data
	} catch (e) {
		throw convertAxiosError(e)
	}
}
