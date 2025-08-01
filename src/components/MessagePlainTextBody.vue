<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div id="mail-content">
		<NeedsTranslationInfo v-if="needsTranslation"
			:is-html="false"
			@translate="$emit('translate')" />
		<MdnRequest :message="message" />
		<div id="message-container" v-html="nl2br(enhancedBody)" />
		<details v-if="signature" class="mail-signature">
			<summary v-html="nl2br(signatureSummaryAndBody.summary)" />
			<span v-html="nl2br(signatureSummaryAndBody.body)" />
		</details>
	</div>
</template>

<script>
import MdnRequest from './MdnRequest.vue'
import NeedsTranslationInfo from './NeedsTranslationInfo.vue'
import { needsTranslation } from '../service/AiIntergrationsService.js'
import { loadState } from '@nextcloud/initial-state'

const regFirstParagraph = /(.+\n\r?)+(\n\r?)+/

export default {
	name: 'MessagePlainTextBody',
	components: { MdnRequest, NeedsTranslationInfo },
	props: {
		body: {
			type: String,
			required: true,
		},
		signature: {
			type: String,
			default: () => undefined,
		},
		message: {
			required: true,
			type: Object,
		},
	},
	data() {
		return {
			needsTranslation: false,
			enabledFreePrompt: loadState('mail', 'llm_freeprompt_available', false),
		}
	},
	computed: {
		enhancedBody() {
			return this.body.replace(/(^&gt;.*\n)+/gm, (match) => {
				return `<details class="quoted-text"><summary>${t('mail', 'Quoted text')}</summary>${match}</details>`
			})
		},
		signatureSummaryAndBody() {
			const matches = this.signature.trim().match(regFirstParagraph)

			if (matches && matches[0]) {
				return {
					summary: matches[0],
					body: this.signature.substring(matches[0].length),
				}
			}

			const lines = this.signature.trim().split(/\r?\n/)
			return {
				summary: lines[0],
				body: lines.slice(1).join('\n'),
			}
		},
		signatureSummary() {
			console.info(this.signature.match(regFirstParagraph))

			return this.signatureSummaryAndBody.summary
		},
	},
	async mounted() {
		if (this.enabledFreePrompt && this.message) {
			this.needsTranslation = await needsTranslation(this.message.databaseId)
		}
	},
	methods: {
		nl2br(str) {
			return str.replace(/(\r\n|\n\r|\n|\r)/g, '<br />')
		},
	},
}
</script>

<style lang="scss">
.quoted-text {
	color: var(--color-text-maxcontrast)

	summary {
		cursor: pointer
	}
}
</style>
<style lang="scss" scoped>
.message-container,
.mail-signature {
	white-space: pre-wrap;
}

.mail-signature, .quoted {
	color: var(--color-text-maxcontrast)

	summary {
		cursor: pointer
	}
}
</style>
