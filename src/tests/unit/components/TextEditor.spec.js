/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import {createLocalVue, shallowMount} from '@vue/test-utils'
import mitt from 'mitt'

import Nextcloud from '../../../mixins/Nextcloud.js'
import TextEditor from '../../../components/TextEditor.vue'
import VirtualTestEditor from '../../virtualtesteditor.js'
import ParagraphPlugin from '@ckeditor/ckeditor5-paragraph/src/paragraph'
import MailPlugin from '../../../ckeditor/mail/MailPlugin.js'

const localVue = createLocalVue()

localVue.mixin(Nextcloud)

describe('TextEditor', () => {

	it('shallow mounts', async() => {
		const wrapper = shallowMount(TextEditor, {
			localVue,
			propsData: {
				value: 'bonjour',
				bus: mitt(),
			},
		})
	})

	it('throw when editor not ready', async() => {
		const wrapper = shallowMount(TextEditor, {
			localVue,
			propsData: {
				value: 'bonjour',
				bus: mitt(),
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
				bus: mitt(),
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
				bus: mitt(),
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
					bus: mitt(),
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
