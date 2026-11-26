<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div
		v-if="translationAvailable"
		class="needs-translation-info"
		:class="{ 'needs-translation-info--html': isHtml }">
		{{ t('mail', 'Translate this message to {language}', { language: localLanguage }) }}
		<NcButton
			variant="tertiary"
			@click="$emit('translate')">
			{{ t('mail', 'Translate') }}
		</NcButton>
	</div>
</template>

<script>
import { getLanguage } from '@nextcloud/l10n'
import { mapState, mapStores } from 'pinia'
import NcButton from '@nextcloud/vue/components/NcButton'
import useMainStore from '../store/mainStore.js'

export default {
	name: 'NeedsTranslationInfo',
	components: {
		NcButton,
	},

	props: {
		isHtml: {
			type: Boolean,
			required: true,
		},
	},

	emits: ['translate'],

	computed: {
		...mapStores(useMainStore),
		...mapState(useMainStore, {
			availableOutputLanguages: 'translationOutputLanguages',
		}),

		translationAvailable() {
			return this.availableOutputLanguages.length > 0 && this.mainStore.isAiEnabled()
		},

		localLanguage() {
			return this.availableOutputLanguages.find((lang) => lang.value === getLanguage())?.name || 'English'
		},

	},
}
</script>

<style lang="scss" scoped>
.needs-translation-info {
    display: flex;
	align-items: center;
    &--html {
		margin-inline-start: 10px;
		color: var(--color-text-maxcontrast) !important;
    }
}
</style>
