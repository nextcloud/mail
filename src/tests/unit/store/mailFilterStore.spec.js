/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import * as MailFilterService from '../../../service/MailFilterService.js'
import useMailFilterStore from '../../../store/mailFilterStore.ts'

vi.mock('../../../service/MailFilterService.js', () => ({
	getFilters: vi.fn(),
	updateFilters: vi.fn(),
}))

const ACCOUNT_ID = 1

/**
 * @param {string} name filter name
 * @param {number} priority filter priority
 * @return {object} a stored filter
 */
function filter(name, priority) {
	return { name, priority, tests: [], actions: [] }
}

describe('mailFilterStore', () => {
	let store

	beforeEach(async () => {
		vi.clearAllMocks()
		setActivePinia(createPinia())
		store = useMailFilterStore()
		MailFilterService.getFilters.mockResolvedValue([filter('Newsletters', 10), filter('Invoices', 20)])
		await store.fetch(ACCOUNT_ID)
	})

	it('keeps a new filter after it was saved', async () => {
		MailFilterService.updateFilters.mockResolvedValue()

		await store.update({ ...filter('Receipts', 30), id: 'new' })
		await store.store(ACCOUNT_ID)

		expect(store.filters.map((f) => f.name)).toEqual(['Newsletters', 'Invoices', 'Receipts'])
	})

	it('drops a new filter when saving it fails', async () => {
		MailFilterService.updateFilters.mockRejectedValue(new Error('Invalid sieve script'))
		await store.update({ ...filter('Broken', 30), id: 'new' })

		await expect(store.store(ACCOUNT_ID)).rejects.toThrow('Invalid sieve script')

		expect(store.filters.map((f) => f.name)).toEqual(['Newsletters', 'Invoices'])
	})

	it('restores an edited filter when saving it fails', async () => {
		MailFilterService.updateFilters.mockRejectedValue(new Error('Invalid sieve script'))
		const original = store.filters[0]
		await store.update({ ...original, name: 'Broken' })

		await expect(store.store(ACCOUNT_ID)).rejects.toThrow()

		expect(store.filters[0].name).toBe('Newsletters')
	})

	it('restores a deleted filter when deleting it fails', async () => {
		MailFilterService.updateFilters.mockRejectedValue(new Error('Server error'))
		await store.delete(store.filters[0])

		await expect(store.store(ACCOUNT_ID)).rejects.toThrow()

		expect(store.filters.map((f) => f.name)).toEqual(['Newsletters', 'Invoices'])
	})

	it('restores the last successfully saved list after a later failure', async () => {
		MailFilterService.updateFilters.mockResolvedValueOnce()
		await store.update({ ...filter('Receipts', 30), id: 'new' })
		await store.store(ACCOUNT_ID)
		MailFilterService.updateFilters.mockRejectedValueOnce(new Error('Server error'))
		await store.update({ ...filter('Broken', 40), id: 'broken' })

		await expect(store.store(ACCOUNT_ID)).rejects.toThrow()

		expect(store.filters.map((f) => f.name)).toEqual(['Newsletters', 'Invoices', 'Receipts'])
	})
})
