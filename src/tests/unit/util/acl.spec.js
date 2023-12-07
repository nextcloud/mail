/**
 * @copyright Copyright (c) 2023 Daniel Kesselberg <mail@danielkesselberg.de>
 *
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import {mailboxHasRights} from '../../../util/acl.js'

describe('acl', () => {
	describe('mailboxHasRights', () => {
		it('allow as fallback', () => {
			const mailbox = {}

			const actual = mailboxHasRights(mailbox, 'l')

			expect(actual).toBeTruthy

		})

		it('allow when right included', () => {
			const mailbox = {
				myAcls: 'lrwstipekxa',
			}

			const actual = mailboxHasRights(mailbox, 'l')

			expect(actual).toBeTruthy
		})

		it('deny when right not included', () => {
			const mailbox = {
				myAcls: 'lrw',
			}

			const actual = mailboxHasRights(mailbox, 'iw')

			expect(actual).toBeFalsy
		})
	})
})
