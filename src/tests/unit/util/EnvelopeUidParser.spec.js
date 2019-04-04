/*
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
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

import {parseUid} from '../../../util/EnvelopeUidParser'

describe('EnvelopeUidParser', () => {
	it('parses a simple UID', () => {
		const uid = '1-SU5CT1g=-123'

		const parsed = parseUid(uid)

		expect(parsed.accountId).to.equal(1)
		expect(parsed.folderId).to.equal('SU5CT1g=')
		expect(parsed.id).to.equal(123)
	})

	it('parses the default account UID', () => {
		const uid = '-2-SU5CT1g=-123'

		const parsed = parseUid(uid)

		expect(parsed.accountId).to.equal(-2)
		expect(parsed.folderId).to.equal('SU5CT1g=')
		expect(parsed.id).to.equal(123)
	})
})
