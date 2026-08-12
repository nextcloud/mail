<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="section">
		<div>
			<input
				id="signature-above-quote-toggle"
				v-model="signatureAboveQuote"
				type="checkbox"
				class="checkbox">
			<label for="signature-above-quote-toggle">
				{{ t("mail", "Place signature above quoted text") }}
			</label>
		</div>
		<NcSelect
			v-if="identities.length > 1"
			:allow-empty="false"
			:options="identities"
			:aria-label-combobox="t('mail', 'Select an alias')"
			:searchable="false"
			:model-value="identity"
			label="label"
			track-by="id"
			@option:selected="changeIdentity" />
		<!-- Added wrapper to give the signature editor a clear input-style border -->
		<div class="signature-editor-wrapper">
			<!-- Plugins are picked when the editor is created, so re-create it whenever html changes -->
			<TextEditor
				:key="allowHtml"
				v-model="signature"
				:html="allowHtml"
				:placeholder="t('mail', 'Signature …')"
				:bus="bus"
				class="signature-editor-wrapper__editor" />
		</div>
		<NcNoteCard v-if="isLargeSignature" type="warning">
			<!-- TRANSLATORS: "It is added to every message" refers to the signature, not to the image -->
			<p>{{ t('mail', 'This signature is larger than 2 MB, usually because an image is embedded in it. It is added to every message you send and may slow down the editor.') }}</p>
		</NcNoteCard>
		<NcNoteCard v-if="overridesPlainText" type="warning">
			<!-- TRANSLATORS: "writing mode", "rich text" and "plain text" are labels of the Writing mode setting -->
			<p>{{ t('mail', 'This signature contains images. New messages will use rich text, even though your writing mode is set to plain text.') }}</p>
		</NcNoteCard>
		<ButtonVue
			type="primary"
			:disabled="loading"
			:aria-label="t('mail', 'Save signature')"
			@click="saveSignature">
			<template #icon>
				<IconLoading v-if="loading" :size="20" fill-color="white" />
				<IconCheck v-else :size="20" />
			</template>
			{{ t('mail', 'Save signature') }}
		</ButtonVue>
		<ButtonVue
			v-if="signature"
			:aria-label="t('mail', 'Delete')"
			type="tertiary-no-background"
			class="button-text"
			@click="deleteSignature">
			{{ t('mail', 'Delete') }}
		</ButtonVue>
	</div>
</template>

<script>
import { NcButton as ButtonVue, NcLoadingIcon as IconLoading, NcNoteCard, NcSelect } from '@nextcloud/vue'
import mitt from 'mitt'
import { mapStores } from 'pinia'
import IconCheck from 'vue-material-design-icons/Check.vue'
import TextEditor from './TextEditor.vue'
import logger from '../logger.js'
import { EDITOR_MODE_HTML } from '../store/constants.js'
import useMainStore from '../store/mainStore.js'
import { containsImage, detect, toHtml } from '../util/text.js'

export default {
	name: 'SignatureSettings',
	components: {
		TextEditor,
		NcNoteCard,
		NcSelect,
		ButtonVue,
		IconLoading,
		IconCheck,
	},

	props: {
		account: {
			type: Object,
			required: true,
		},
	},

	data() {
		return {
			loading: false,
			bus: mitt(),
			identity: null,
			signature: '',
			// Snapshot from the loaded signature: deleting the image while editing must
			// not swap the editor under the cursor
			signatureEnforcesHtml: false,
			signatureAboveQuote: this.account.signatureAboveQuote,
		}
	},

	computed: {
		...mapStores(useMainStore),
		identities() {
			const identities = this.account.aliases.map((alias) => {
				return {
					id: alias.id,
					label: alias.name + ' (' + alias.alias + ')',
					signature: alias.signature,
				}
			})

			identities.unshift({
				id: -1,
				label: this.account.name + ' (' + this.account.emailAddress + ')',
				signature: this.account.signature,
			})

			return identities
		},

		/**
		 * Plain text accounts get the plain text editor so they can't add images to
		 * their signature in the first place. Signatures that already contain one
		 * keep the rich text editor, otherwise the image would be stripped on load
		 * and silently lost on save.
		 *
		 * @return {boolean}
		 */
		allowHtml() {
			return this.account.editorMode === EDITOR_MODE_HTML || this.signatureEnforcesHtml
		},

		/**
		 * Unlike allowHtml this follows the current content so the warning disappears
		 * as soon as the image is gone.
		 *
		 * @return {boolean}
		 */
		overridesPlainText() {
			return this.account.editorMode !== EDITOR_MODE_HTML
				&& !!this.signature
				&& containsImage(this.signature)
		},

		isLargeSignature() {
			return (new Blob([this.signature])).size > 2 * 1024 * 1024
		},
	},

	watch: {
		async signatureAboveQuote(val, oldVal) {
			try {
				await this.mainStore.patchAccount({
					account: this.account,
					data: {
						signatureAboveQuote: val,
					},
				})
				logger.debug('signature above quoted updated to ' + val)
			} catch (e) {
				logger.error('could not update signature above quote', { e })
				this.signatureAboveQuote = oldVal
			}
		},
	},

	beforeMount() {
		this.changeIdentity(this.identities[0])
	},

	methods: {
		changeIdentity(identity) {
			logger.debug('select identity', { identity })
			this.identity = identity
			this.signature = identity.signature
				? toHtml(detect(identity.signature)).value
				: ''
			this.signatureEnforcesHtml = containsImage(this.signature)
		},

		async deleteSignature() {
			this.signature = null
			await this.saveSignature()
		},

		async saveSignature() {
			this.loading = true

			const payload = {
				account: this.account,
				signature: this.signature,
			}

			if (this.identity.id > -1) {
				payload.aliasId = this.identity.id
				return this.mainStore.updateAliasSignature(payload)
					.then(() => {
						logger.info('signature updated')
						this.loading = false
					})
					.catch((error) => {
						logger.error('could not update account signature', { error })
						throw error
					})
			}

			return this.mainStore.updateAccountSignature(payload)
				.then(() => {
					logger.info('signature updated')
					this.loading = false
				})
				.catch((error) => {
					logger.error('could not update account signature', { error })
					throw error
				})
		},

	},
}
</script>

<style lang="scss" scoped>
/* Wrapper to visually delimit the signature editor area from surrounding settings */
.signature-editor-wrapper {
	margin-top: 8px;
	padding: 4px; /* room for focus ring */
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	background: var(--color-main-background);
	transition: border-color 120ms ease, box-shadow 120ms ease;

	&:focus-within {
		border-color: var(--color-primary-element);
		box-shadow: 0 0 0 2px var(--color-primary-element-light);
	}

	&__editor {
		/* remove internal border since wrapper provides it */
		:deep(.ck.ck-editor__editable_inline) {
			border: none !important;
			box-shadow: none !important;
			min-height: 120px;
		}
	}
}

.primary {
  padding-inline-start: 26px;
  background-position: 6px;
  color: var(--color-main-background);

  &:after {
    inset-inline-start: 14px;
  }
}

.button-text {
  background-color: transparent;
  border: none;
  color: var(--color-text-maxcontrast);
  font-weight: normal;

  &:hover,
  &:focus {
    color: var(--color-main-text);
  }
}

.section {
  display: block;
  padding: 0;
  margin-bottom: 23px;
}

.ck-balloon-panel {
	 z-index: 10000 !important;
 }

.button-vue:deep() {
	/* Keep the buttons inline but restore NcButton's own flex centering,
	   otherwise the icon-and-text button ends up taller than the text-only
	   one and they align on their text baseline instead of their center. */
	display: inline-flex !important;
	align-items: center;
	margin-top: 4px !important;
}

/* it's a bit hard to make it work without this max-width in the modal because it overlaps with the sidebar of the modal */
:deep(.ck.ck-toolbar-dropdown>.ck-dropdown__panel) {
	max-width: 19vw;
}
@media only screen and (max-width: 580px) {
	:deep(.ck.ck-toolbar-dropdown>.ck-dropdown__panel) {
		max-width: 70vw;
	}
}

</style>
