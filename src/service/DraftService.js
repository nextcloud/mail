/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

import { convertAxiosError } from '../errors/convert.js'

export async function saveDraft(data) {
	const url = generateUrl('/apps/mail/api/drafts')

	try {
		return (await axios.post(url, data)).data.data
	} catch (e) {
		throw convertAxiosError(e)
	}
}

export async function updateDraft(data) {
	const url = generateUrl('/apps/mail/api/drafts/{id}', {
		id: data.id,
	})

	try {
		return (await axios.put(url, data)).data.data
	} catch (e) {
		throw convertAxiosError(e)
	}
}

export async function deleteDraft(id) {
	const url = generateUrl('/apps/mail/api/drafts/{id}', {
		id,
	})

	try {
		return (await axios.delete(url)).data.data
	} catch (e) {
		throw convertAxiosError(e)
	}
}

export async function moveDraft(id) {
	const url = generateUrl('/apps/mail/api/drafts/move/{id}', {
		id,
	})

	try {
		return (await axios.post(url)).data.data
	} catch (e) {
		throw convertAxiosError(e)
	}
}
