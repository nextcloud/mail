/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { convertAxiosError } from '../../../errors/convert.js'

describe('convert error', () => {
	it('ignores errors without a response', () => {
		const error = {} // no response

		const result = convertAxiosError(error)

		expect(result instanceof Error).toEqual(false)
		expect(result).toEqual(error)
	})

	it('ignores errors it does not know', () => {
		const error = {
			response: {
				headers: {},
				status: 400,
				data: {},
			},
		}

		const result = convertAxiosError(error)

		expect(result instanceof Error).toEqual(false)
		expect(result).toEqual(error)
	})

	it('converts known exceptions to errors', () => {
		const error = {
			response: {
				headers: {
					'x-mail-response': '1',
				},
				status: 400,
				data: {
					status: 'fail',
					data: {
						type: 'OCA\\Mail\\Exception\\MailboxLockedException',
					},
				},
			},
		}

		const result = convertAxiosError(error)

		expect(result instanceof Error).toEqual(true)
	})
})
