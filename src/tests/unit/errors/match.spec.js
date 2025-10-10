/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { matchError } from '../../../errors/match.js'

describe('match', () => {
	it('throws an error when nothing matches', (done) => {
		const error = new Error('henlo')

		matchError(error, {}).catch(() => done())
	})

	it('uses the default', (done) => {
		const map = {
			default: (error) => 3,
		}
		const error = new Error('henlo')

		matchError(error, map).then((result) => {
			expect(expect(result).toEqual(3))
			done()
		})
	})

	it('matches errors', (done) => {
		const map = {
			MyErr: (error) => 2,
			default: (error) => 3,
		}
		const error = new Error('henlo')
		error.name = 'MyErr'

		matchError(error, map).then((result) => {
			expect(expect(result).toEqual(2))
			done()
		})
	})
})
