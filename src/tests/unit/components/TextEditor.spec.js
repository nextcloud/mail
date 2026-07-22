/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createLocalVue, shallowMount } from '@vue/test-utils'
import { Paragraph } from 'ckeditor5'
import mitt from 'mitt'
import { vi } from 'vitest'
import TextEditor from '../../../components/TextEditor.vue'
import MailPlugin from '../../../ckeditor/mail/MailPlugin.js'
import Nextcloud from '../../../mixins/Nextcloud.js'
import VirtualTestEditor from '../../virtualtesteditor.js'

const localVue = createLocalVue()

localVue.mixin(Nextcloud)

describe('TextEditor', () => {
	it('shallow mounts', async () => {
		const wrapper = shallowMount(TextEditor, {
			localVue,
			propsData: {
				value: 'bonjour',
				bus: mitt(),
			},
		})
	})

	it('throw when editor not ready', async () => {
		const wrapper = shallowMount(TextEditor, {
			localVue,
			propsData: {
				value: 'bonjour',
				bus: mitt(),
			},
		})

		const error = new Error('Impossible to execute a command before editor is ready.')
		expect(() => wrapper.vm.editorExecute('insertSignature', {}))
			.toThrowError(error)
	})

	it('emit event on input', async () => {
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

	it('emit event on ready', async () => {
		const wrapper = shallowMount(TextEditor, {
			localVue,
			propsData: {
				value: 'bonjour',
				bus: mitt(),
			},
		})

		// Mock DOM refs
		wrapper.vm.$refs.toolbarContainer = document.createElement('div')
		wrapper.vm.$refs.editableContainer = document.createElement('div')

		const editor = await VirtualTestEditor.create({
			licenseKey: 'GPL',
			initialData: '<p>bonjour bonjour</p>',
			plugins: [Paragraph],
		})

		editor.ui = {
			view: {
				toolbar: {

					element: document.createElement('div'),
					items: [],
				},
				editable: { element: document.createElement('div') },
			},
		}

		wrapper.vm.onEditorReady(editor)

		expect(wrapper.emitted().ready[0]).toBeTruthy()
	})
	it('register conversion to add margin: 0px to every <p> element', async () => {
		const wrapper = shallowMount(TextEditor, {
			localVue,
			propsData: {
				value: '',
				bus: mitt(),
			},
		})

		// Mock DOM refs
		wrapper.vm.$refs.toolbarContainer = document.createElement('div')
		wrapper.vm.$refs.editableContainer = document.createElement('div')

		const editor = await VirtualTestEditor.create({
			licenseKey: 'GPL',
			initialData: '<p>bonjour bonjour</p>',
			plugins: [Paragraph, MailPlugin],
		})

		editor.ui = {
			view: {
				toolbar: {
					element: document.createElement('div'),
					items: [],
				},
				editable: { element: document.createElement('div') },
			},
		}

		wrapper.vm.onEditorReady(editor)

		expect(wrapper.emitted().ready[0]).toBeTruthy()
		expect(wrapper.emitted().ready[0][0].getData())
			.toEqual('<p style="margin:0;">bonjour bonjour</p>')
	})

	it('emits updated data while editing source', async () => {
		vi.useFakeTimers()

		const wrapper = shallowMount(TextEditor, {
			localVue,
			provide: {
				addToFocusTrap: vi.fn(),
			},
			propsData: {
				value: '<p>bonjour</p>',
				html: true,
				bus: mitt(),
			},
		})

		const textarea = document.createElement('textarea')
		const updateEditorData = vi.fn()
		const sourceEditingPlugin = {
			updateEditorData,
			on: vi.fn(),
			off: vi.fn(),
		}
		const editor = {
			locale: {
				uiLanguageDirection: 'ltr',
			},
			ui: {
				view: {
					toolbar: {
						element: document.createElement('div'),
						items: [],
					},
					editable: { element: document.createElement('div') },
				},
				getEditableElement: vi.fn((name) => name === 'sourceEditing:main' ? textarea : null),
			},
			commands: {
				get: vi.fn(() => null),
			},
			plugins: {
				has: vi.fn((pluginName) => pluginName === 'SourceEditing'),
				get: vi.fn(() => sourceEditingPlugin),
			},
			keystrokes: {
				set: vi.fn(),
			},
			editing: {
				view: {
					focus: vi.fn(),
				},
			},
		}

		wrapper.vm.$refs.toolbarContainer = document.createElement('div')
		wrapper.vm.$refs.editableContainer = { appendChild: vi.fn() }

		wrapper.vm.onEditorReady(editor)

		const sourceEditingModeHandler = sourceEditingPlugin.on.mock.calls.find(([evt]) => evt === 'change:isSourceEditingMode')[1]

		sourceEditingModeHandler(null, 'isSourceEditingMode', true)
		textarea.dispatchEvent(new Event('input'))

		await vi.runAllTimersAsync()

		// updateEditorData() calls editor.data.set() → model change:data → Vue wrapper emits @input.
		// We assert the handoff to CKEditor's pipeline here; the Vue wrapper propagation is tested
		// by @ckeditor/ckeditor5-vue2's own suite.
		expect(updateEditorData).toHaveBeenCalledTimes(1)

		vi.useRealTimers()
	})
})
