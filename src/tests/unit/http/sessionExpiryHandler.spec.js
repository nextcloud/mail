/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { handleHttpAuthErrors } from '../../../http/sessionExpiryHandler.js'

describe('sessionExpiryHandler', () => {
	it('does not influence successful requests', async () => {
		const commit = jest.fn()

		await handleHttpAuthErrors(commit, () => {})

		expect(commit).not.toHaveBeenCalled()
	})

	it('ignores other 401s', async () => {
		const commit = jest.fn()
		let exception

		try {
			await handleHttpAuthErrors(commit, () => {
				throw {
					response: {
						status: 401,
						data: {
							message: 'Bonjour',
						},
					},
				}
			})
		} catch (e) {
			exception = e
		}

		// Is this our exception?
		expect(exception.response?.status === 401)
		expect(commit).not.toHaveBeenCalled()
	})

	it('handles relevant 401s', async () => {
		const commit = jest.fn()
		let exception

		try {
			await handleHttpAuthErrors(commit, () => {
				throw {
					response: {
						status: 401,
						data: {
							message: 'Current user is not logged in',
						},
					},
				}
			})
		} catch (e) {
			exception = e
		}

		// Is this our exception?
		expect(exception.response?.status === 401)
		expect(commit).toHaveBeenCalled()
	})

})

