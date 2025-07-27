/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import * as MessageService from '../../../service/MessageService.js'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

jest.mock('@nextcloud/axios')
jest.mock('@nextcloud/router')

describe('service/MessageService test suite', () => {
	afterEach(() => {
		jest.clearAllMocks()
	})

	it('should include a given cache buster as a URL parameter', async () => {
		generateUrl.mockReturnValueOnce('/generated-url')
		axios.get.mockResolvedValueOnce({ data: [] })

		await MessageService.fetchEnvelopes(
			13, // account id
			21, // mailbox id
			undefined, // query
			undefined, // cursor
			undefined, // limit
			undefined, // sort ordre
			undefined, // layout
			'abcdef123', // cache buster
		)

		expect(axios.get).toHaveBeenCalledWith('/generated-url', {
			params: {
				mailboxId: 21,
				v: 'abcdef123',
			},
		})
	})

	it('should not include a cache buster by default', async () => {
		generateUrl.mockReturnValueOnce('/generated-url')
		axios.get.mockResolvedValueOnce({ data: [] })

		await MessageService.fetchEnvelopes(
			13, // account id
			21, // mailbox id
		)

		expect(axios.get).toHaveBeenCalledWith('/generated-url', {
			params: {
				mailboxId: 21,
			},
		})
	})
})
