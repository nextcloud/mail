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

import {translate} from '../../../l10n/MailboxTranslator'

describe('MailboxTranslator', () => {
	it('translates the inbox', () => {
		const folder = {
			id: btoa('INBOX'),
			specialUse: ['inbox'],
		}

		const name = translate(folder)

		expect(name).to.equal('Inbox')
	})

	it('does not translate an arbitrary mailbox', () => {
		const folder = {
			id: btoa('Newsletters'),
			specialUse: [],
		}

		const name = translate(folder)

		expect(name).to.equal('Newsletters')
	})
})
