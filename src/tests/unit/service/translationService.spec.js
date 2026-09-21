/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'
import { translateText } from '../../../service/translationService.js'

vi.mock('@nextcloud/axios')
vi.mock('@nextcloud/router')
vi.mock('../../../store/mainStore.js', () => ({ default: vi.fn() }))

// Regression test for issue #13156: a translation task that ends in
// STATUS_FAILED never populates task.output, so getTaskOutput must reject
// instead of polling taskprocessing/task/{id} forever.
describe('service/translationService — failed task (issue #13156)', () => {
	beforeEach(() => {
		vi.useFakeTimers()
		generateOcsUrl.mockImplementation((url) => url)
	})
	afterEach(() => {
		vi.clearAllMocks()
		vi.useRealTimers()
	})

	it('rejects and stops polling once the task fails', async () => {
		const runningTask = { id: 42, status: 'STATUS_RUNNING', output: null }
		const failedTask = { id: 42, status: 'STATUS_FAILED', output: null }
		axios.post.mockResolvedValue({ data: { ocs: { data: { task: runningTask } } } })
		axios.get.mockResolvedValue({ data: { ocs: { data: { task: failedTask } } } })

		let rejected = false
		const settledPromise = translateText('hallo', 'de', 'en').catch(() => {
			rejected = true
		})

		await vi.advanceTimersByTimeAsync(2000)
		await settledPromise

		expect(rejected).toBe(true)

		const callsWhenFailed = axios.get.mock.calls.length
		await vi.advanceTimersByTimeAsync(10000)
		expect(axios.get.mock.calls.length).toBe(callsWhenFailed)
	})

	it('resolves with the translated text once the task succeeds', async () => {
		const scheduledTask = { id: 7, status: 'STATUS_SCHEDULED', output: null }
		const doneTask = { id: 7, status: 'STATUS_SUCCESSFUL', output: { output: 'hello' } }
		axios.post.mockResolvedValue({ data: { ocs: { data: { task: scheduledTask } } } })
		axios.get.mockResolvedValue({ data: { ocs: { data: { task: doneTask } } } })

		const resultPromise = translateText('hallo', 'de', 'en')
		await vi.advanceTimersByTimeAsync(2000)

		await expect(resultPromise).resolves.toBe('hello')
	})
})
