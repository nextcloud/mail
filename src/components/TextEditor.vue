<!--
  - @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
	<ckeditor
		v-if="ready"
		v-model="text"
		:config="config"
		:editor="editor"
		@input="onInput"
		@ready="onEditorReady"
	></ckeditor>
</template>

<script>
import CKEditor from '@ckeditor/ckeditor5-vue'
import Editor from '@ckeditor/ckeditor5-editor-balloon/src/ballooneditor'
import EssentialsPlugin from '@ckeditor/ckeditor5-essentials/src/essentials'
import BlockQuotePlugin from '@ckeditor/ckeditor5-block-quote/src/blockquote'
import BoldPlugin from '@ckeditor/ckeditor5-basic-styles/src/bold'
import ItalicPlugin from '@ckeditor/ckeditor5-basic-styles/src/italic'
import LinkPlugin from '@ckeditor/ckeditor5-link/src/link'
import ParagraphPlugin from '@ckeditor/ckeditor5-paragraph/src/paragraph'

import {getLanguage} from '@nextcloud/l10n'

import logger from '../logger'

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
	},
	data() {
		return {
			text: '',
			ready: false,
			editor: Editor,
			config: {
				placeholder: this.placeholder,
				plugins: this.html
					? [EssentialsPlugin, ParagraphPlugin, BoldPlugin, ItalicPlugin, LinkPlugin, BlockQuotePlugin]
					: [EssentialsPlugin, ParagraphPlugin],
				toolbar: {
					items: this.html ? ['bold', 'italic', 'blockquote', 'link', 'undo', 'redo'] : ['undo', 'redo'],
				},
				language: 'en',
			},
		}
	},
	beforeMount() {
		this.loadEditorTranslations(getLanguage())
	},
	methods: {
		loadEditorTranslations(language) {
			if (language === 'en') {
				// The default, nothing to fetch
				return this.showEditor('en')
			}

			import(
				/* webpackMode: "lazy-once" */
				/* webpackPrefetch: true */
				/* webpackPreload: true */
				`@ckeditor/ckeditor5-build-balloon/build/translations/${language}`
			)
				.then(() => this.showEditor(language))
				.catch(() => this.showEditor('en'))
		},
		showEditor(language) {
			if (this.html) {
				this.config.language = language
			} else {
				this.config.language = language
			}

			this.ready = true
		},
		onEditorReady(editor) {
			const schema = editor.model.schema

			schema.on(
				'checkChild',
				(evt, args) => {
					const context = args[0]

					if (context.endsWith('blockQuote')) {
						// Prevent next listeners from being called.
						evt.stop()
						// Set the checkChild()'s return value.
						evt.return = true
					}
				},
				{
					priority: 'highest',
				}
			)

			// Set value as late as possible, so the custom schema listener is used
			// for the initial editor model
			this.text = this.value

			logger.debug(`setting TextEditor contents to <${this.text}>`)
		},
		onInput() {
			logger.debug(`TextEditor input changed to <${this.text}>`)
			this.$emit('input', this.text)
		},
	},
}
</script>

<style lang="scss" scoped>
::v-deep p {
	cursor: text;
}
</style>
