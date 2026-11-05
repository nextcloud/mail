/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import pLimit from 'p-limit'
import { fetchIfChanged } from './RevalidationService.js'

// The server handles parallel requests in any order, so a later change of a sender could be overwritten by an earlier one
const queuesByEmail = new Map()

export async function trustSender(email, type, trustFlag) {
	const url = generateUrl('/apps/mail/api/trustedsenders/{email}?type={type}', {
		email,
		type,
	})

	const queue = queuesByEmail.get(email) ?? pLimit(1)
	queuesByEmail.set(email, queue)
	try {
		await queue(() => (trustFlag ? axios.put(url) : axios.delete(url)))
	} finally {
		if (queue.activeCount === 0 && queue.pendingCount === 0) {
			queuesByEmail.delete(email)
		}
	}
}
export async function fetchTrustedSenders(etag) {
	return fetchIfChanged(generateUrl('/apps/mail/api/trustedsenders'), etag)
}
