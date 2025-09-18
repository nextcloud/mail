<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div :class="{ 'editor-wrapper--bordered': isBordered }" class="editor-wrapper">
		<div ref="toolbarContainer" class="toolbar" />

		<div ref="editableContainer" class="editable" />

		<ckeditor v-if="ready"
			:value="value"
			:config="config"
			:editor="editor"
			:disabled="disabled"
			class="editor"
			@input="onEditorInput"
			@ready="onEditorReady" />
	</div>
</template>

<script>
import CKEditor from '@ckeditor/ckeditor5-vue2'
import AlignmentPlugin from '@ckeditor/ckeditor5-alignment/src/alignment.js'
import { Mention } from '@ckeditor/ckeditor5-mention'
import Editor from '@ckeditor/ckeditor5-editor-classic/src/classiceditor.js'
import EssentialsPlugin from '@ckeditor/ckeditor5-essentials/src/essentials.js'
import BlockQuotePlugin from '@ckeditor/ckeditor5-block-quote/src/blockquote.js'
import BoldPlugin from '@ckeditor/ckeditor5-basic-styles/src/bold.js'
import FontPlugin from '@ckeditor/ckeditor5-font/src/font.js'
import ParagraphPlugin from '@ckeditor/ckeditor5-paragraph/src/paragraph.js'
import HeadingPlugin from '@ckeditor/ckeditor5-heading/src/heading.js'
import ItalicPlugin from '@ckeditor/ckeditor5-basic-styles/src/italic.js'
import Underline from '@ckeditor/ckeditor5-basic-styles/src/underline.js'
import Superscript from '@ckeditor/ckeditor5-basic-styles/src/superscript.js'
import Subscript from '@ckeditor/ckeditor5-basic-styles/src/subscript.js'
import LinkPlugin from '@ckeditor/ckeditor5-link/src/link.js'
import ListPlugin from '@ckeditor/ckeditor5-list/src/list.js'
import ListProperties from '@ckeditor/ckeditor5-list/src/listproperties.js'
import RemoveFormat from '@ckeditor/ckeditor5-remove-format/src/removeformat.js'
import SignaturePlugin from '../ckeditor/signature/SignaturePlugin.js'
import StrikethroughPlugin from '@ckeditor/ckeditor5-basic-styles/src/strikethrough.js'
import QuotePlugin from '../ckeditor/quote/QuotePlugin.js'
import Base64UploadAdapter from '@ckeditor/ckeditor5-upload/src/adapters/base64uploadadapter.js'
import ImagePlugin from '@ckeditor/ckeditor5-image/src/image.js'
import FindAndReplace from '@ckeditor/ckeditor5-find-and-replace/src/findandreplace.js'
import ImageResizePlugin from '@ckeditor/ckeditor5-image/src/imageresize.js'
import ImageUploadPlugin from '@ckeditor/ckeditor5-image/src/imageupload.js'
import GeneralHtmlSupport from '@ckeditor/ckeditor5-html-support/src/generalhtmlsupport.js'
import { DropdownView } from '@ckeditor/ckeditor5-ui'
import MailPlugin from '../ckeditor/mail/MailPlugin.js'
import { searchProvider, getLinkWithPicker } from '@nextcloud/vue/components/NcRichText'
import { getLanguage } from '@nextcloud/l10n'
import logger from '../logger.js'
import PickerPlugin from '../ckeditor/smartpicker/PickerPlugin.js'
import { autoCompleteByName } from '../service/ContactIntegrationService.js'
import { emojiSearch, emojiAddRecent } from '@nextcloud/vue'
import { toPlain, Text } from '../util/text.js'
export default {
	name: 'TextEditor',
	components: {
		ckeditor: CKEditor.component,
	},
	props: {
		value: {
			type: String,
			required: true,
		},
		html: {
			type: Boolean,
			default: false,
		},
		placeholder: {
			type: String,
			default: '',
		},
		focus: {
			type: Boolean,
			default: false,
		},
		bus: {
			type: Object,
			required: true,
		},
		disabled: {
			type: Boolean,
			default: false,
		},
		textBlocks: {
			type: Array,
			default: () => [],
		},
		isBordered: {
			type: Boolean,
			default: false,
		},
		readOnly: {
			type: Boolean,
			default: false,
		},
	},
	data() {
		const plugins = [
			EssentialsPlugin,
			ParagraphPlugin,
			SignaturePlugin,
			QuotePlugin,
			PickerPlugin,
			Mention,
			LinkPlugin,
			FindAndReplace,
			GeneralHtmlSupport,
		]
		const toolbar = ['undo', 'redo']

		if (this.html) {
			plugins.push(...[
				HeadingPlugin,
				AlignmentPlugin,
				BoldPlugin,
				ItalicPlugin,
				Underline,
				StrikethroughPlugin,
				Subscript,
				Superscript,
				BlockQuotePlugin,
				ListPlugin,
				ImagePlugin,
				ImageUploadPlugin,
				ImageResizePlugin,
				ListProperties,
				FontPlugin,
				RemoveFormat,
				Base64UploadAdapter,
				MailPlugin,
			])
			toolbar.unshift(...[
				'heading',
				'fontFamily',
				'fontSize',
				'bold',
				'italic',
				'underline',
				'strikethrough',
				'fontColor',
				'subscript',
				'superscript',
				'fontBackgroundColor',
				'insertImage',
				'alignment',
				'bulletedList',
				'numberedList',
				'blockquote',
				'link',
				'removeFormat',
				'findAndReplace',
			])
		}

		return {
			linkTribute: null,
			emojiTribute: null,
			textSmiles: [],
			ready: false,
			editor: Editor,
			config: {
				licenseKey: 'GPL',
				placeholder: this.placeholder,
				plugins,
				toolbar: {
					items: toolbar,
				},
				language: 'en',
				mention: {
					feeds: [
						{
							marker: ':',
							feed: this.getEmoji,
							itemRenderer: this.customEmojiRenderer,
						},
						{
							marker: '/',
							feed: this.getLink,
							itemRenderer: this.customLinkRenderer,
						},
						{
							marker: '@',
							feed: this.getContact,
							itemRenderer: this.customRenderer,
						},
						{
							marker: '!',
							feed: this.getTextBlock,
							itemRenderer: this.customRenderer,
						},
					],
				},
			},
		}
	},
	beforeMount() {
		this.loadEditorTranslations(getLanguage())
	},
	methods: {
		getLink(text) {
			const results = searchProvider(text)
			if (results.length === 1 && !results[0].title.toLowerCase().includes(text.toLowerCase())) {
				return []
			}
			return results
		},
		getEmoji(text) {
			const emojiResults = emojiSearch(text)
			if (this.textSmiles.includes(':' + text)) {

				emojiResults.unshift(':' + text)
			}
			return emojiResults
		},
		async getContact(text) {
			if (text.length === 0) {
				return []
			}
			let contactResults = await autoCompleteByName(text)
			contactResults = contactResults.filter(result => result.email.length > 0)
			return contactResults
		},
		getTextBlock(text) {
			if (text.length === 0) {
				return []
			}
			return this.textBlocks.filter(textBlock => textBlock.title.toLowerCase().includes(text.toLowerCase()))
		},
		 customEmojiRenderer(item) {
			const itemElement = document.createElement('span')

			itemElement.classList.add('custom-item')
			itemElement.id = `mention-list-item-id-${item.colons}`
			itemElement.textContent = `${item.native} `
			const usernameElement = document.createElement('span')

			usernameElement.classList.add('custom-item-username')
			usernameElement.textContent = item.colons

			itemElement.appendChild(usernameElement)

			return itemElement
		},
		customLinkRenderer(item) {
			const itemElement = document.createElement('span')
			itemElement.classList.add('link-container')

			const icon = document.createElement('img')
			icon.classList.add('link-icon')
			icon.src = `${item.icon_url} `

			const usernameElement = document.createElement('span')

			usernameElement.classList.add('link-title')
			usernameElement.textContent = `${item.title} `
			itemElement.appendChild(icon)
			itemElement.appendChild(usernameElement)

			return itemElement
		},
		customRenderer(item, type) {
			const itemElement = document.createElement('span')

			itemElement.classList.add('custom-item')
			itemElement.id = `mention-list-item-id-${item.id}`
			const usernameElement = document.createElement('p')
			const label = type === 'contact' ? item.label : item.title
			usernameElement.classList.add('custom-item-username')
			usernameElement.textContent = label

			itemElement.appendChild(usernameElement)

			return itemElement
		},
		overrideDropdownPositionsToNorth(editor, toolbarView) {
			const {
				south, north, southEast, southWest, northEast, northWest,
				southMiddleEast, southMiddleWest, northMiddleEast, northMiddleWest,
			} = DropdownView.defaultPanelPositions

			let panelPositions

			if (editor.locale.uiLanguageDirection !== 'rtl') {
				panelPositions = [
					northEast, northWest, northMiddleEast, northMiddleWest, north,
					southEast, southWest, southMiddleEast, southMiddleWest, south,
				]
			} else {
				panelPositions = [
					northWest, northEast, northMiddleWest, northMiddleEast, north,
					southWest, southEast, southMiddleWest, southMiddleEast, south,
				]
			}

			for (const item of toolbarView.items) {
				if (!(item instanceof DropdownView)) {
					continue
				}

				item.on('change:isOpen', () => {
					if (!item.isOpen) {
						return
					}

					item.panelView.position = DropdownView._getOptimalPosition({
						element: item.panelView.element,
						target: item.buttonView.element,
						fitInViewport: true,
						positions: panelPositions,
					}).name
				})
			}
		},
		overrideTooltipPositions(toolbarView) {
			for (const item of toolbarView.items) {
				if (item.buttonView) {
					item.buttonView.tooltipPosition = 'n'
				} else if (item.tooltipPosition) {
					item.tooltipPosition = 'n'
				}
			}
		},
		async loadEditorTranslations(language) {
			if (language === 'en') {
				// The default, nothing to fetch
				return this.showEditor('en')
			}

			try {
				logger.debug(`loading ${language} translations for CKEditor`)
				await import(
					/* webpackMode: "lazy-once" */
					/* webpackPrefetch: true */
					/* webpackPreload: true */
					`@ckeditor/ckeditor5-build-decoupled-document/build/translations/${language}`
				)
				this.showEditor(language)
			} catch (error) {
				logger.error(`could not find CKEditor translations for "${language}"`, { error })
				this.showEditor('en')
			}
		},
		showEditor(language) {
			logger.debug(`using "${language}" as CKEditor language`)
			this.config.language = language

			this.ready = true
		},
		/**
		 * @param {module:core/editor/editor~Editor} editor editor the editor instance
		 */
		onEditorReady(editor) {
			logger.debug('TextEditor is ready', { editor })

			// https://ckeditor.com/docs/ckeditor5/latest/examples/builds-custom/bottom-toolbar-editor.html
			this.$refs.toolbarContainer.appendChild(editor.ui.view.toolbar.element)
			this.$refs.editableContainer.appendChild(editor.ui.view.editable.element)
			if (this.readOnly) {
				editor.ui.view.toolbar.element.style.display = 'none'
				editor.enableReadOnlyMode('text-block')
			}
			if (editor.ui) {
				this.overrideDropdownPositionsToNorth(editor, editor.ui.view.toolbar)
				this.overrideTooltipPositions(editor.ui.view.toolbar)
			}
			editor.commands.get('mention')?.on('execute', (event, data) => {
				event.stop()
				const eventData = data[0]
				const item = eventData.mention
				if (eventData.marker === ':') {
					emojiAddRecent(item)
					this.editorInstance.execute('insertItem', item.native, ':')
				}
				if (eventData.marker === '/') {
					getLinkWithPicker(item.id)
						.then((link) => {
							this.editorInstance.execute('insertItem', link, '/')
							this.editorInstance.editing.view.focus()
						})
						.catch((error) => {
							console.debug('Smart picker promise rejected:', error)
						})
				}
				if (eventData.marker === '@') {
					this.editorInstance.execute('insertItem', { email: item.email[0], label: item.label }, '@')
					this.$emit('mention', { email: item.email[0], label: item.label })
				}
				if (eventData.marker === '!') {
					this.insertTextBlock(item, false)
				}
			}, { priority: 'high' })

			this.editorInstance = editor

			if (this.focus) {
				logger.debug('focusing TextEditor')
				editor.editing.view.focus()
			}

			if (this.html) {
				this.$emit('show-toolbar', editor.ui._focusableToolbarDefinitions[0].toolbarView.element)
			}

			this.bus.on('append-to-body-at-cursor', this.appendToBodyAtCursor)
			this.bus.on('insert-text-block', this.insertTextBlock)
			this.$emit('ready', editor)
		},
		onEditorInput(text) {
			if (text !== this.value) {
				logger.debug(`TextEditor input changed to <${text}>`)
				this.$emit('input', text)
			}
		},
		appendToBodyAtCursor(toAppend) {
			// https://ckeditor.com/docs/ckeditor5/latest/builds/guides/faq.html#where-are-the-editorinserthtml-and-editorinserttext-methods-how-to-insert-some-content
			const viewFragment = this.editorInstance.data.processor.toView(toAppend)
			const modelFragment = this.editorInstance.data.toModel(viewFragment)
			this.editorInstance.model.insertContent(modelFragment)
		},
		editorExecute(commandName, ...args) {
			if (this.editorInstance) {
				this.editorInstance.execute(commandName, ...args)
			} else {
				throw new Error('Impossible to execute a command before editor is ready.')
			}
		},
		insertTextBlock(textBlock, addTriggrer = true) {
			if (addTriggrer) {
				this.appendToBodyAtCursor('!')
			}
			let content = textBlock.content
			if (!this.html) {
				const text = new Text('html', content)
				content = toPlain(text).value
			}
			this.editorInstance.execute('insertItem', { content, isHtml: this.html }, '!')
		},
	},
}
</script>

<style lang="scss" scoped>
.editor-wrapper--bordered{
	--border-offset: calc(var(--border-width-input-focused, 2px) - var(--border-width-input, 2px));
	margin-top: var(--default-grid-baseline);
	border: var(--border-width-input, 2px) solid var(--color-border-maxcontrast);
	border-radius:var(--border-radius-large);
	height: 200px;
	// to align with the text input in the text block modal
	padding: 9px;

	:deep(.ck.ck-editor__editable_inline) {
		padding:0 !important;
	}
	&:focus {
		padding: calc(9px - var(--border-offset));
		border-color: var(--color-main-text);
		border-width: var(--border-width-input-focused, 2px);
	}
	&:hover {
		padding: calc(9px - var(--border-offset));
		border-color: var(--color-main-text);
		border-width: var(--border-width-input-focused, 2px);
	}
}

.editor {
	width: 100%;
	height: calc(100% - 75px);
	overflow: scroll;
	margin-bottom: 10px;

	&.ck {
		border: none !important;
		box-shadow: none !important;
		padding: 0;
	}
}

:deep(a) {
	color: #07d;
}

:deep(p) {
	cursor: text;
	margin: 0 !important;
}
</style>

<style>
/*
Overwrite the default z-index for CKEditor
https://github.com/ckeditor/ckeditor5/issues/1142
 */
 .ck .ck-reset {
	background: var(--color-main-background) !important;
 }
/* Default ckeditor value of padding-inline-start, to overwrite the global styling from server */
.ck-content ul, .ck-content ol {
	padding-inline-start: 40px;
}

.ck-read-only {
	color: var(--color-main-text) !important;
	background-color: var(--color-main-background) !important;
	opacity: 1 !important;
	font-size: 100% !important;
}

.ck-list__item {
	.ck-off {
		background:var(--color-main-background) !important;
	}
	.ck-on {
		background:var(--color-primary-element-light) !important;
	}
}

.custom-item-username {
	color: var(--color-main-text) !important;
 }

.link-title {
	color: var(--color-main-text) !important;
	margin-inline-start: var(--default-grid-baseline) !important;
}

.custom-item {
	width : 100% !important;
	border-radius : 8px !important;
	padding : 4px 8px !important;
	display :block;
	background:var(--color-main-background)!important;
}

.custom-item:hover {
	background:var(--color-primary-element-light)!important;
}

.link-container{
	border-radius :8px !important;
	padding :4px 8px !important;
	display : block;
	width : 100% !important;
	background:var(--color-main-background)!important;
	img.link-icon {
		width: 16px;
		height: 16px;
	}
}

.link-container:hover {
	background:var(--color-primary-element-light)!important;
}

:root {
	--ck-z-default: 10000;
	--ck-balloon-border-width:  0;
}

.ck.ck-toolbar {
	border-radius: var(--border-radius-large) !important;
	background: none;
	background: var(--color-main-background) !important;
    color: var(--color-main-text) !important;
}

.ck-rounded-corners .ck.ck-dropdown__panel, .ck.ck-dropdown__panel.ck-rounded-corners {
	border-radius: var(--border-radius-large) !important;
	overflow: visible;
}

.ck.ck-list-styles-list {
/* our composer is very small, having menus vertically shown is better */
	grid-template-rows: repeat(3,auto) !important;
	grid-template-columns: unset !important;
}

.ck.ck-button {
	border-radius: var(--border-radius-element) !important;
}

.ck-powered-by-balloon {
	display: none !important;
}

.editor-wrapper {
	display: flex;
	flex-direction: column-reverse;
	height: 100%;

	.toolbar {
		position: sticky;
		bottom: 0;
		z-index: 10;
	}

	.editable {
		flex-grow: 1;
		overflow-y: auto;
	}
}

.ck.ck-editor__editable.ck-focused:not(.ck-editor__nested-editable) {
	border: none;
	box-shadow: none;
	width: 99%;
	height: 97%;
}

.ck.ck-button, a.ck.ck-button {
	font-size: small;
	font-weight: normal;
}

.ck-source-editing-area {
	height: 97%;
	overflow: scroll;
}

.ck-source-editing-area textarea {
	border: 0;
}

.ck.ck-editor__editable_inline {
	width: 99%;
	height: 97%;
	border: 0;
}

.select, button:not(.button-vue,[class^=vs__]), .button, input[type=button], input[type=submit], input[type=reset] {
	color: var(--color-main-text);
}

/* We need the paragraph field a bit smaller so it doesnt break the toolbar for signature */
.ck.ck-dropdown.ck-heading-dropdown .ck-dropdown__button .ck-button__label {
	width: 6em !important;
}

.ck.ck-editor__top .ck-sticky-panel .ck-sticky-panel__content {
	border: none;
}

.ck.ck-balloon-panel_visible {
    border-radius: calc(var(--border-radius-large) + 1px) !important;
    background: var(--color-main-background) !important;
    color: var(--color-main-text) !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

.ck.ck-tooltip__text{
	color: var(--color-main-text) !important;
}

.ck.ck-toolbar .ck-button {
	color: var(--color-main-text) !important;
}

.ck.ck-toolbar .ck-button:hover,
.ck.ck-toolbar .ck-button.ck-on,
.ck.ck-toolbar .ck-button:focus {
	background: var(--color-primary-element-light) !important;
    color: var(--color-main-text) !important;
}

.ck.ck-toolbar .ck-button .ck-button__label {
	color: var(--color-main-text) !important;
}

.ck.ck-dropdown__panel .ck.ck-list {
	border-radius: var(--border-radius-large) !important;
}

.ck-dropdown__panel.ck-dropdown__panel-visible {
	border-radius: var(--border-radius-large) !important;
}

/* Needs to be set to flex, bececause else it breaks the toolbar - it is shown in 2 lines instead of 1 */
.ck.ck-splitbutton.ck-dropdown__button{
	display: flex !important;
}

.ck.ck-input.ck-input-text{
	background: var(--color-main-background) !important;
    color: var(--color-main-text) !important;
	cursor: text !important;
}

.ck.ck-labeled-field-view__input-wrapper .ck.ck-label {
	background: var(--color-main-background) !important;
    color: var(--color-main-text) !important;
}

.ck.ck-button.ck-splitbutton__action {
    margin: 0 !important;
}

.ck.ck-splitbutton.ck-dropdown__button:hover .ck-button,
.ck.ck-splitbutton.ck-dropdown__button:hover .ck-splitbutton__action,
.ck.ck-splitbutton.ck-dropdown__button:hover .ck-splitbutton__arrow {
	background: var(--color-primary-element-light) !important;
	color: var(--color-main-text) !important;
}

.ck.ck-splitbutton .ck-button:focus,
.ck.ck-splitbutton .ck-button:focus-visible,
.ck.ck-splitbutton .ck-button:active,
.ck.ck-splitbutton .ck-button.ck-on {
	background: var(--color-primary-element-light) !important;
	color: var(--color-main-text) !important;
	outline: none !important;
}

.ck.ck-splitbutton.ck-splitbutton_open .ck-button,
.ck.ck-splitbutton.ck-splitbutton_open .ck-splitbutton__action,
.ck.ck-splitbutton.ck-splitbutton_open .ck-splitbutton__arrow {
    background: var(--color-primary-element-light) !important;
    color: var(--color-main-text) !important;
}

</style>
