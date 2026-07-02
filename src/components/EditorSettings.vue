<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div>
		<p>
			<input
				id="plaintext"
				ref="plaintext"
				:name="radioGroupName"
				type="radio"
				class="radio"
				:checked="account.editorMode === EDITOR_MODE_TEXT"
				@change="selectMode(EDITOR_MODE_TEXT)">
			<label :class="{ primary: account.editorMode === EDITOR_MODE_TEXT }" for="plaintext">
				{{ t('mail', 'Plain text') }}
			</label>
			<input
				id="richtext"
				ref="richtext"
				:name="radioGroupName"
				type="radio"
				class="radio"
				:checked="account.editorMode === EDITOR_MODE_HTML"
				@change="selectMode(EDITOR_MODE_HTML)">
			<label :class="{ primary: account.editorMode === EDITOR_MODE_HTML }" for="richtext">
				{{ t('mail', 'Rich text') }}
			</label>
		</p>
	</div>
</template>

<script>
import { mapStores } from 'pinia'
import Logger from '../logger.js'
import { EDITOR_MODE_HTML, EDITOR_MODE_TEXT } from '../store/constants.js'
import useMainStore from '../store/mainStore.js'

export default {
	name: 'EditorSettings',
	props: {
		account: {
			type: Object,
			required: true,
		},
	},

	computed: {
		...mapStores(useMainStore),

		EDITOR_MODE_TEXT: () => EDITOR_MODE_TEXT,
		EDITOR_MODE_HTML: () => EDITOR_MODE_HTML,
		radioGroupName() {
			return `editor-mode-${this.account.id}`
		},
	},

	methods: {
		selectMode(mode) {
			if (mode === this.account.editorMode) {
				return
			}

			if (this.account.editorMode === EDITOR_MODE_HTML && mode === EDITOR_MODE_TEXT) {
				this.syncRadios()
				OC.dialogs.confirmDestructive(
					t('mail', 'Switching to plain text removes any existing formatting such as bold, italic, underline and inline images — including those in your signature.'),
					t('mail', 'Switch to plain text'),
					{
						type: OC.dialogs.YES_NO_BUTTONS,
						confirm: t('mail', 'Switch and remove formatting'),
						confirmClasses: 'error',
						cancel: t('mail', 'Keep rich text'),
					},
					(decision) => {
						if (decision) {
							this.persistMode(mode)
						}
					},
				)
				return
			}

			this.persistMode(mode)
		},

		persistMode(mode) {
			this.mainStore.patchAccount({
				account: this.account,
				data: {
					editorMode: mode,
				},
			})
				.then(() => {
					Logger.info('editor mode updated')
				})
				.catch((error) => {
					Logger.error('could not update editor mode', { error })
					this.syncRadios()
					throw error
				})
		},

		syncRadios() {
			if (this.$refs.plaintext) {
				this.$refs.plaintext.checked = this.account.editorMode === EDITOR_MODE_TEXT
			}
			if (this.$refs.richtext) {
				this.$refs.richtext.checked = this.account.editorMode === EDITOR_MODE_HTML
			}
		},
	},
}
</script>

<style lang="scss" scoped>

label {
	padding-inline-end: 12px;
}
</style>
