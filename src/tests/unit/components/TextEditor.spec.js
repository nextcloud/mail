/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { Ckeditor } from '@ckeditor/ckeditor5-vue'
import { flushPromises, mount, shallowMount } from '@vue/test-utils'
import { GeneralHtmlSupport, Paragraph } from 'ckeditor5'
import mitt from 'mitt'
import { beforeEach, vi } from 'vitest'
import TextEditor from '../../../components/TextEditor.vue'
import ImageDowncastPlugin from '../../../ckeditor/image/ImageDowncastPlugin.ts'
import MailPlugin from '../../../ckeditor/mail/MailPlugin.js'
import Nextcloud from '../../../mixins/Nextcloud.js'
import VirtualTestEditor from '../../virtualtesteditor.js'

describe('TextEditor', () => {
	it('shallow mounts', async () => {
		const wrapper = shallowMount(TextEditor, {
			global: {
				mixins: [Nextcloud],
			},
			props: {
				modelValue: 'bonjour',
				bus: mitt(),
			},
		})
	})

	it('does not support additional html elements in plain text mode', async () => {
		const wrapper = shallowMount(TextEditor, {
			global: {
				mixins: [Nextcloud],
			},
			props: {
				modelValue: 'bonjour',
				bus: mitt(),
			},
		})

		expect(wrapper.vm.config.plugins).not.toContain(GeneralHtmlSupport)
	})

	it('supports additional html elements in html mode', async () => {
		const wrapper = shallowMount(TextEditor, {
			global: {
				mixins: [Nextcloud],
				provide: {
					addToFocusTrap: vi.fn(),
				},
			},
			props: {
				modelValue: 'bonjour',
				html: true,
				bus: mitt(),
			},
		})

		expect(wrapper.vm.config.plugins).toContain(GeneralHtmlSupport)
		expect(wrapper.vm.config.htmlSupport.allow.some((rule) => rule.name === 'img')).toBe(true)
	})

	it('aligns and resizes images in html mode', async () => {
		const wrapper = shallowMount(TextEditor, {
			global: {
				mixins: [Nextcloud],
				provide: {
					addToFocusTrap: vi.fn(),
				},
			},
			props: {
				modelValue: 'bonjour',
				html: true,
				bus: mitt(),
			},
		})

		expect(wrapper.vm.config.plugins).toContain(ImageDowncastPlugin)
		expect(wrapper.vm.config.image.resizeUnit).toBe('px')
		expect(wrapper.vm.config.image.toolbar).toContain('imageStyle:alignCenter')
	})

	it('throw when editor not ready', async () => {
		const wrapper = shallowMount(TextEditor, {
			global: {
				mixins: [Nextcloud],
			},
			props: {
				modelValue: 'bonjour',
				bus: mitt(),
			},
		})

		const error = new Error('Impossible to execute a command before editor is ready.')
		expect(() => wrapper.vm.editorExecute('insertSignature', {}))
			.toThrowError(error)
	})

	it('emit event on input', async () => {
		const wrapper = shallowMount(TextEditor, {
			global: {
				mixins: [Nextcloud],
			},
			props: {
				modelValue: 'bonjour',
				bus: mitt(),
			},
		})

		wrapper.vm.onEditorInput('bonjour bonjour')

		expect(wrapper.emitted('update:modelValue')[0]).toEqual(['bonjour bonjour'])
	})

	it('emit event on ready', async () => {
		const wrapper = shallowMount(TextEditor, {
			global: {
				mixins: [Nextcloud],
			},
			props: {
				modelValue: 'bonjour',
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
			global: {
				mixins: [Nextcloud],
			},
			props: {
				modelValue: '',
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
			global: {
				mixins: [Nextcloud],
				provide: {
					addToFocusTrap: vi.fn(),
				},
			},
			props: {
				modelValue: '<p>bonjour</p>',
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
					document: {
						on: vi.fn(),
					},
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
		// by @ckeditor/ckeditor5-vue's own suite.
		expect(updateEditorData).toHaveBeenCalledTimes(1)

		vi.useRealTimers()
	})

	describe('emitting the latest text', () => {
		let wrapper
		let editor
		let keystrokes

		beforeEach(async () => {
			wrapper = shallowMount(TextEditor, {
				global: {
					mixins: [Nextcloud],
				},
				props: {
					modelValue: '<p>bonjour</p>',
					bus: mitt(),
				},
			})
			wrapper.vm.$refs.toolbarContainer = document.createElement('div')
			wrapper.vm.$refs.editableContainer = document.createElement('div')
			wrapper.vm.$refs.ckeditor = { lastEditorData: '<p>bonjour</p>' }

			editor = await VirtualTestEditor.create({
				licenseKey: 'GPL',
				initialData: '<p>bonjour</p>',
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
			keystrokes = {}
			vi.spyOn(editor.keystrokes, 'set').mockImplementation((key, handler) => {
				keystrokes[key] = handler
			})
			wrapper.vm.onEditorReady(editor)
			editor.data.set('<p>bonjour bonjour</p>')
		})

		it('emits the text when the editor loses focus', () => {
			editor.editing.view.document.fire('blur', {})

			expect(wrapper.emitted('update:modelValue')).toEqual([['<p>bonjour bonjour</p>']])
			expect(wrapper.vm.$refs.ckeditor.lastEditorData).toBe('<p>bonjour bonjour</p>')
		})

		it('emits the text before asking to save', () => {
			keystrokes['Ctrl+S']({ preventDefault: vi.fn(), stopPropagation: vi.fn() })

			expect(wrapper.emitted('update:modelValue')).toEqual([['<p>bonjour bonjour</p>']])
			expect(Object.keys(wrapper.emitted())).toEqual(expect.arrayContaining(['update:modelValue', 'save']))
			expect(wrapper.emitted('save')).toHaveLength(1)
		})

		it('emits the text before asking to send', () => {
			keystrokes['Ctrl+Enter']({})

			expect(wrapper.emitted('update:modelValue')).toEqual([['<p>bonjour bonjour</p>']])
			expect(wrapper.emitted('submit')).toHaveLength(1)
		})
	})

	it('does not reset the Ckeditor wrapper with text it was told about', async () => {
		class ElementEditor extends VirtualTestEditor {
			static async create(elementOrConfig, config) {
				const editor = await super.create(config ?? elementOrConfig)
				editor.ui = { getEditableElement: () => document.createElement('div') }
				return editor
			}
		}
		const view = mount(Ckeditor, {
			props: {
				editor: ElementEditor,
				modelValue: '<p>bonjour</p>',
				config: { licenseKey: 'GPL', plugins: [Paragraph] },
			},
		})
		await flushPromises()
		const editor = view.vm.instance
		const setData = vi.spyOn(editor.data, 'set')

		view.vm.lastEditorData = '<p>bonjour bonjour</p>'
		await view.setProps({ modelValue: '<p>bonjour bonjour</p>' })

		expect(setData).not.toHaveBeenCalled()

		await view.setProps({ modelValue: '<p>au revoir</p>' })

		expect(setData).toHaveBeenCalledWith('<p>au revoir</p>')
	})
})
