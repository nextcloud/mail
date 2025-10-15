/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { isPgpgMessage, isPgpText } from '../../../crypto/pgp.js'
import { html, plain } from '../../../util/text.js'

describe('pgp', () => {
	it('detects non-pgp messages', () => {
		const messages = plain('Hi Alice')

		const isPgp = isPgpgMessage(messages)

		expect(isPgp).toEqual(false)
	})

	it('detects non-pgp HTML messages', () => {
		const messages = html('Hi Alice')

		const isPgp = isPgpgMessage(messages)

		expect(isPgp).toEqual(false)
	})

	it('detects a pgp message', () => {
		const message = plain('-----BEGIN PGP MESSAGE-----\nVersion: Mailvelope v4.3.1')

		const isPgp = isPgpgMessage(message)

		expect(isPgp).toEqual(true)
	})

	it('detects non-pgp text', () => {
		const text = 'Hi Alice'

		const isPgp = isPgpText(text)

		expect(isPgp).toEqual(false)
	})

	it('detects a pgp text', () => {
		const message = '-----BEGIN PGP MESSAGE-----\nVersion: Mailvelope v4.3.1'

		const isPgp = isPgpText(message)

		expect(isPgp).toEqual(true)
	})
})
