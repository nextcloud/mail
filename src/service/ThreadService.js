/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { convertAxiosError } from '../errors/convert.js'

export async function deleteThread(id) {
	const url = generateUrl('/apps/mail/api/thread/{id}', {
		id,
	})

	try {
		return await axios.delete(url)
	} catch (e) {
		throw convertAxiosError(e)
	}
}

export async function moveThread(id, destMailboxId) {
	const url = generateUrl('/apps/mail/api/thread/{id}', {
		id,
	})

	try {
		return await axios.post(url, { destMailboxId })
	} catch (e) {
		throw convertAxiosError(e)
	}
}

export async function snoozeThread(id, unixTimestamp, destMailboxId) {
	const url = generateUrl('/apps/mail/api/thread/{id}/snooze', {
		id,
	})

	try {
		return await axios.post(url, { unixTimestamp, destMailboxId })
	} catch (e) {
		throw convertAxiosError(e)
	}
}

export async function unSnoozeThread(id) {
	const url = generateUrl('/apps/mail/api/thread/{id}/unsnooze', {
		id,
	})

	try {
		return await axios.post(url, {})
	} catch (e) {
		throw convertAxiosError(e)
	}
}
