/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { flushPromises } from '@vue/test-utils'
import { trustSender } from '../../../service/TrustedSenderService.js'

vi.mock('@nextcloud/axios')

describe('TrustedSenderService', () => {
	beforeEach(() => {
		vi.clearAllMocks()
	})

	it('trusts a sender with a PUT request', async () => {
		axios.put.mockResolvedValueOnce({})

		await trustSender('alice@example.test', 'individual', true)

		expect(axios.put).toHaveBeenCalledExactlyOnceWith(expect.stringContaining('/apps/mail/api/trustedsenders/alice%40example.test?type=individual'))
		expect(axios.delete).not.toHaveBeenCalled()
	})

	it('stops trusting a sender with a DELETE request', async () => {
		axios.delete.mockResolvedValueOnce({})

		await trustSender('example.test', 'domain', false)

		expect(axios.delete).toHaveBeenCalledExactlyOnceWith(expect.stringContaining('/apps/mail/api/trustedsenders/example.test?type=domain'))
		expect(axios.put).not.toHaveBeenCalled()
	})

	it('sends a change only after the previous one is saved', async () => {
		const trusting = Promise.withResolvers()
		axios.put.mockReturnValueOnce(trusting.promise)
		axios.delete.mockResolvedValueOnce({})

		const saving = trustSender('alice@example.test', 'individual', true)
		const removing = trustSender('alice@example.test', 'individual', false)
		await flushPromises()
		const removedWhileTrusting = axios.delete.mock.calls.length
		trusting.resolve({})
		await Promise.all([saving, removing])

		expect(removedWhileTrusting).toBe(0)
		expect(axios.delete).toHaveBeenCalledOnce()
	})

	it('sends changes of different senders without waiting for each other', async () => {
		const trustingAlice = Promise.withResolvers()
		axios.put
			.mockReturnValueOnce(trustingAlice.promise)
			.mockResolvedValueOnce({})

		const savingAlice = trustSender('alice@example.test', 'individual', true)
		await trustSender('bob@example.test', 'individual', true)
		const requestsWhileSavingAlice = axios.put.mock.calls.length
		trustingAlice.resolve({})
		await savingAlice

		expect(requestsWhileSavingAlice).toBe(2)
	})

	it('sends the next change after the previous one failed', async () => {
		const trusting = Promise.withResolvers()
		const error = new Error('Request failed with status code 500')
		axios.put.mockReturnValueOnce(trusting.promise)
		axios.delete.mockResolvedValueOnce({})

		const saving = trustSender('alice@example.test', 'individual', true)
		const removing = trustSender('alice@example.test', 'individual', false)
		trusting.reject(error)

		await expect(saving).rejects.toBe(error)
		await removing
		expect(axios.delete).toHaveBeenCalledOnce()
	})
})
