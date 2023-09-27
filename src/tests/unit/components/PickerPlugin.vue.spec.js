/*
 * @copyright 2023 Hamza Mahjoubi <mail@danielkesselberg.de>
 *
 * @author 2023 Hamza Mahjoubi <mail@danielkesselberg.de>
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
import PickerPlugin from '../../../ckeditor/smartpicker/PickerPlugin'


describe('PickerPlugin', () => {

		it('Insert an item and remove trigger symbol', async() => {
			const text = '<p>Hello /</p>'
			const expected = '<p>Hello I am a link</p>'

			const editor = await VirtualTestEditor.create({
				initialData: text,
				plugins: [ParagraphPlugin, PickerPlugin],
			})
			
			editor.model.change(writer => {
				writer.setSelection(editor.model.document.getRoot(), 'end')
			})

			editor.execute('InsertItem',
				'I am a link',
				'/',
			)

			expect(editor.getData()).toEqual(expected)
		})


})
