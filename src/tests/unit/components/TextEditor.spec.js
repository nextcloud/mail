/*
 * @copyright 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
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

import {createLocalVue, shallowMount} from '@vue/test-utils'
import Vue from 'vue'
import Vuex from 'vuex'

import Nextcloud from '../../../mixins/Nextcloud'
import TextEditor from '../../../components/TextEditor'
import VirtualTestEditor from '../../virtualtesteditor'
import ParagraphPlugin from '@ckeditor/ckeditor5-paragraph/src/paragraph'
import MailPlugin from '../../../ckeditor/mail/MailPlugin'

const localVue = createLocalVue()

localVue.use(Vuex)
localVue.mixin(Nextcloud)

describe('TextEditor', () => {

	it('shallow mounts', async() => {
		const wrapper = shallowMount(TextEditor, {
			localVue,
			propsData: {
				value: 'bonjour',
				bus: new Vue(),
			},
		})
	})

	it('throw when editor not ready', async() => {
		const wrapper = shallowMount(TextEditor, {
			localVue,
			propsData: {
				value: 'bonjour',
				bus: new Vue(),
			},
		})

		const error = new Error(
			'Impossible to execute a command before editor is ready.')
		expect(() => wrapper.vm.editorExecute('insertSignature', {})).
			toThrowError(error)
	})

	it('emit event on input', async() => {
		const wrapper = shallowMount(TextEditor, {
			localVue,
			propsData: {
				value: 'bonjour',
				bus: new Vue(),
			},
		})

		wrapper.vm.onEditorInput('bonjour bonjour')

		expect(wrapper.emitted().input[0]).toBeTruthy()
		expect(wrapper.emitted().input[0]).toEqual(['bonjour bonjour'])
	})

	it('emit event on ready', async() => {
		const wrapper = shallowMount(TextEditor, {
			localVue,
			propsData: {
				value: 'bonjour',
				bus: new Vue(),
			},
		})

		const editor = await VirtualTestEditor.create({
			initialData: '<p>bonjour bonjour</p>',
			plugins: [ParagraphPlugin],
		})

		wrapper.vm.onEditorReady(editor)

		expect(wrapper.emitted().ready[0]).toBeTruthy()
	})

	it('register conversion to add margin: 0px to every <p> element',
		async() => {
			const wrapper = shallowMount(TextEditor, {
				localVue,
				propsData: {
					value: '',
					bus: new Vue(),
				},
			})

			const editor = await VirtualTestEditor.create({
				initialData: '<p>bonjour bonjour</p>',
				plugins: [ParagraphPlugin, MailPlugin],
			})

			wrapper.vm.onEditorReady(editor)

			expect(wrapper.emitted().ready[0]).toBeTruthy()
			expect(wrapper.emitted().ready[0][0].getData()).
				toEqual('<p style="margin:0;">bonjour bonjour</p>')
		})

})
