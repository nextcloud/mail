/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getMailvelope } from '../../../crypto/mailvelope'

describe('mailvelope', () => {
	afterEach(() => {
		delete window.mailvelope
	})

	it('loads statically', async() => {
		window.mailvelope = {
			mock: 3,
		}

		const mailvelope = await getMailvelope()

		expect(mailvelope).toEqual(window.mailvelope)
	})

	it('loads dynamically', async() => {
		const p = getMailvelope()
		window.mailvelope = {
			mock: 3,
		}
		window.dispatchEvent(new Event('mailvelope'))

		const mailvelope = await p
		expect(mailvelope).toEqual(window.mailvelope)
	})
})
