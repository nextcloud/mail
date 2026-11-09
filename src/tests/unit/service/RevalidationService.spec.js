/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { fetchIfChanged } from '../../../service/RevalidationService.js'

vi.mock('@nextcloud/axios')

describe('RevalidationService', () => {
	const url = '/apps/mail/api/trustedsenders'

	beforeEach(() => {
		vi.clearAllMocks()
	})

	it('requests the whole resource without a known version', async () => {
		axios.get.mockResolvedValueOnce({ status: 200, data: { data: ['example.test'] }, headers: { etag: '"new"' } })

		const result = await fetchIfChanged(url, null)

		expect(axios.get).toHaveBeenCalledExactlyOnceWith(url, expect.objectContaining({ headers: {} }))
		expect(result).toEqual({ data: ['example.test'], etag: '"new"' })
	})

	it('asks for changes of the known version', async () => {
		axios.get.mockResolvedValueOnce({ status: 200, data: { data: ['other.test'] }, headers: { etag: '"new"' } })

		const result = await fetchIfChanged(url, '"known"')

		expect(axios.get).toHaveBeenCalledExactlyOnceWith(url, expect.objectContaining({ headers: { 'If-None-Match': '"known"' } }))
		expect(result).toEqual({ data: ['other.test'], etag: '"new"' })
	})

	it('reports an unchanged resource', async () => {
		axios.get.mockResolvedValueOnce({ status: 304, data: '', headers: {} })

		const result = await fetchIfChanged(url, '"known"')

		expect(result).toBeNull()
	})

	it('treats only a success or an unchanged resource as an answer', async () => {
		axios.get.mockResolvedValueOnce({ status: 304, data: '', headers: {} })

		await fetchIfChanged(url, '"known"')

		const { validateStatus } = axios.get.mock.calls[0][1]
		expect([200, 204, 304].map(validateStatus)).toEqual([true, true, true])
		expect([199, 300, 302, 404, 500].map(validateStatus)).toEqual([false, false, false, false, false])
	})

	it('passes on other failures', async () => {
		const error = { response: { status: 500 } }
		axios.get.mockRejectedValueOnce(error)

		await expect(fetchIfChanged(url, '"known"')).rejects.toBe(error)
	})

	it('passes on failures without a response', async () => {
		const error = new Error('Network Error')
		axios.get.mockRejectedValueOnce(error)

		await expect(fetchIfChanged(url, '"known"')).rejects.toBe(error)
	})
})
