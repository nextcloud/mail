/*
 * @copyright 2022 Daniel Kesselberg <mail@danielkesselberg.de>
 *
 * @author 2022 Daniel Kesselberg <mail@danielkesselberg.de>
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

import VirtualTestEditor from '../../virtualtesteditor'
import ParagraphPlugin from '@ckeditor/ckeditor5-paragraph/src/paragraph'
import MailPlugin from '../../../ckeditor/mail/MailPlugin'

describe('MailPlugin', () => {

	it('Add margin:0 to paragraph', async() => {
		const text = '<p>bonjour bonjour</p>'
		const expected = '<p style="margin:0;">bonjour bonjour</p>'

		const editor = await VirtualTestEditor.create({
			initialData: text,
			plugins: [ParagraphPlugin, MailPlugin],
		})

		expect(editor.getData()).toEqual(expected)
	})

})
