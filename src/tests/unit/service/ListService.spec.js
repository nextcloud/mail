/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { unsubscribe } from '../../../service/ListService.js'

vi.mock('@nextcloud/axios')
vi.mock('@nextcloud/router')

describe('service/ListService', () => {
	beforeEach(() => {
		generateUrl.mockImplementation((url, params) => url.replace('{id}', params.id))
	})

	afterEach(() => {
		vi.clearAllMocks()
	})

	it('posts the unsubscribe request for the message', async () => {
		axios.post.mockResolvedValue({ data: {} })

		await unsubscribe(13)

		expect(axios.post).toHaveBeenCalledWith('/apps/mail/api/list/unsubscribe/13')
	})

	it('rejects when the unsubscribe request fails', async () => {
		axios.post.mockRejectedValue(new Error('Request failed with status code 500'))

		await expect(unsubscribe(13)).rejects.toThrow('Request failed with status code 500')
	})
})
