/**
 * SPDX-FileCopyrightText: 2022-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { handleHttpAuthErrors } from '../../../http/sessionExpiryHandler.js'
import { createPinia, setActivePinia } from 'pinia'
import useMainStore from '../../../store/mainStore.js'

describe('sessionExpiryHandler', () => {
	beforeEach(() => {
		setActivePinia(createPinia())
	})

	it('does not influence successful requests', async () => {
		const mainStore = useMainStore()
		mainStore.isExpiredSession = false

		await handleHttpAuthErrors(async () => {})

		expect(mainStore.isExpiredSession).toBe(false)
	})

	it('ignores other 401s', async () => {
		const mainStore = useMainStore()
		mainStore.isExpiredSession = false

		const exception = {
			response: {
				status: 401,
				data: {
					message: 'Bonjour',
				},
			},
		}

		let actualException
		try {
			await handleHttpAuthErrors(async () => {
				throw exception
			})
		} catch (e) {
			actualException = e
		}

		expect(actualException).toBe(exception)
		expect(mainStore.isExpiredSession).toBe(false)
	})

	it('handles relevant 401s', async () => {
		const mainStore = useMainStore()
		mainStore.isExpiredSession = false

		const exception = {
			response: {
				status: 401,
				data: {
					message: 'Current user is not logged in',
				},
			},
		}

		let actualException
		try {
			await handleHttpAuthErrors(() => {
				throw exception
			})
		} catch (e) {
			actualException = e
		}

		expect(actualException).toBe(exception)
		expect(mainStore.isExpiredSession).toBe(true)
	})
})
