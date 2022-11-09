/*
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

import { isPgpgMessage, isPgpText } from '../../../crypto/pgp'
import { html, plain } from '../../../util/text'

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
