<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcDialog ref="translateDialog"
		class="translate-dialog"
		:name="t('mail', 'Translate message')"
		size="large"
		close-on-click-outside
		@update:open="$emit('close')">
		<template v-if="isMounted" #default>
			<div class="translate-dialog__wrapper">
				<NcSelect v-model="selectedFrom"
					class="translate-dialog__select"
					input-id="from"
					label="name"
					:aria-label-combobox="t('mail', 'Source language to translate from')"
					:placeholder="t('mail', 'Translate from')"
					:options="availableInputLanguages"
					no-wrap />

				<ArrowRight />

				<NcSelect v-model="selectedTo"
					class="translate-dialog__select"
					input-id="to"
					label="name"
					:aria-label-combobox="t('spreed', 'Target language to translate into')"
					:placeholder="t('mail', 'Translate to')"
					:options="availableOutputLanguages"
					no-wrap />

				<NcButton type="primary"
					:disabled="isLoading"
					class="translate-dialog__button"
					@click="handleTranslate">
					<template v-if="isLoading" #icon>
						<NcLoadingIcon />
					</template>
					{{ isLoading ? t('mail', 'Translating') : t('mail', 'Translate') }}
				</NcButton>
			</div>

			<NcRichText class="translate-dialog__message translate-dialog__message-source"
				:text="message"
				:arguments="richParameters"
				:use-markdown="true"
				:reference-limit="0" />

			<NcRichText v-if="translatedMessage"
				class="translate-dialog__message translate-dialog__message-translation"
				:text="translatedMessage"
				:arguments="richParameters"
				:use-markdown="true"
				:reference-limit="0" />
		</template>

		<template v-if="translatedMessage" #actions>
			<NcButton @click="handleCopyTranslation">
				<template #icon>
					<ContentCopy />
				</template>
				{{ t('mail', 'Copy translated text') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<script>
import ArrowRight from 'vue-material-design-icons/ArrowRight.vue'
import ContentCopy from 'vue-material-design-icons/ContentCopy.vue'

import { showError, showSuccess } from '@nextcloud/dialogs'

import { NcButton, NcDialog, NcLoadingIcon, NcRichText, NcSelect } from '@nextcloud/vue'
import { getLanguage } from '@nextcloud/l10n'

import { translateText } from '../service/translationService.js'
import { mapState } from 'pinia'
import useMainStore from '../store/mainStore.js'

export default {
	name: 'TranslationModal',

	components: {
		NcButton,
		NcDialog,
		NcLoadingIcon,
		NcRichText,
		NcSelect,
		ArrowRight,
		ContentCopy,
	},

	props: {
		message: {
			type: String,
			required: true,
		},
		richParameters: {
			type: Object,
			required: true,
		},
	},

	emits: ['close'],

	data() {
		return {
			isMounted: false,
			selectedFrom: null,
			selectedTo: null,
			isLoading: false,
			translatedMessage: '',
		}
	},

	computed: {
		...mapState(useMainStore, {
			availableInputLanguages: 'translationInputLanguages',
			availableOutputLanguages: 'translationOutputLanguages',
		}),

		userLanguage() {
			return getLanguage()
		},
	},

	watch: {
		selectedTo() {
			this.translatedMessage = ''
		},
		selectedFrom() {
			this.translatedMessage = ''
		},
	},

	async mounted() {
		this.selectedTo = this.availableOutputLanguages.find(language => language.value === this.userLanguage) || null
		this.selectedFrom = this.availableInputLanguages.find(language => language.value === 'detect_language')
		this.$nextTick(() => {
			// FIXME trick to avoid focusTrap() from activating on NcSelect
			this.isMounted = !!this.$refs.translateDialog.navigationId
		})
	},

	methods: {
		handleTranslate() {
			if (!this.selectedFrom || !this.selectedTo) {
				showError(t('mail', 'Please select languages to translate to and from'))
				return
			}

			this.translateMessage()
		},

		async translateMessage() {
			try {
				this.isLoading = true
				const response = await translateText(this.message.trim(), this.selectedFrom.value, this.selectedTo.value)
				this.translatedMessage = response
			} catch (error) {
				console.error(error)
				showError(error.response?.data?.ocs?.data?.message ?? t('mail', 'The message could not be translated'))
			} finally {
				this.isLoading = false
			}
		},

		async handleCopyTranslation() {
			try {
				await navigator.clipboard.writeText(this.translatedMessage)
				showSuccess(t('mail', 'Translation copied to clipboard'))
			} catch (error) {
				showError(t('mail', 'Translation could not be copied'))
			}
		},
	},
}

</script>

<style lang="scss" scoped>
.translate-dialog {
	:deep(.dialog__content) {
		position: relative;
		display: flex;
		flex-direction: column;
		gap: calc(var(--default-grid-baseline) * 2);
		min-height: 300px;
		padding-bottom: calc(var(--default-grid-baseline) * 3);
	}

	&__wrapper {
		display: flex;
		align-items: center;
		gap: calc(var(--default-grid-baseline) * 4);
	}

	& &__select {
		width: 50%;
	}

	&__button {
		flex-shrink: 0;
		margin-inline-start: auto;
	}

	&__message {
		padding: calc(var(--default-grid-baseline) * 2);
		flex-grow: 1;
		border-radius: var(--border-radius-large);

		&-source {
			color: var(--color-text-maxcontrast);
			border: 2px solid var(--color-border);
		}

		&-translation {
			border: 2px solid var(--color-primary-element);
		}
	}
}
</style>
