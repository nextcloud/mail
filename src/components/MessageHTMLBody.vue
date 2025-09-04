<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="html-message-body">
		<MdnRequest :message="message" />
		<NeedsTranslationInfo v-if="needsTranslation"
			:is-html="true"
			@translate="$emit('translate')" />
		<div v-if="hasBlockedContent" id="mail-message-has-blocked-content" style="color: #000000">
			{{ t('mail', 'The images have been blocked to protect your privacy.') }}
			<Actions type="tertiary" :menu-name="t('mail', 'Show images')">
				<ActionButton @click="displayIframe">
					<template #icon>
						<IconImage :size="20" />
					</template>
					{{ t('mail', 'Show images temporarily') }}
				</ActionButton>
				<ActionButton v-if="sender"
					@click="onShowBlockedContent">
					<template #icon>
						<IconMail :size="20" />
					</template>
					{{ t('mail', 'Always show images from {sender}', { sender }) }}
				</ActionButton>
				<ActionButton v-if="domain"
					@click="onShowBlockedContentForDomain">
					<template #icon>
						<IconDomain :size="20" />
					</template>
					{{ t('mail', 'Always show images from {domain}', { domain }) }}
				</ActionButton>
			</Actions>
		</div>
		<div id="message-container" :class="{scroll: !fullHeight}">
			<iframe ref="iframe"
				class="message-frame"
				:title="t('mail', 'Message frame')"
				:src="url"
				seamless
				@load="onMessageFrameLoad" />
		</div>
	</div>
</template>

<script>
import iframeResize from '@iframe-resizer/parent'
import PrintScout from 'printscout'
import { trustSender } from '../service/TrustedSenderService.js'
import NeedsTranslationInfo from './NeedsTranslationInfo.vue'
import { NcActionButton as ActionButton, NcActions as Actions } from '@nextcloud/vue'
import IconImage from 'vue-material-design-icons/ImageSizeSelectActual.vue'
import IconMail from 'vue-material-design-icons/EmailOutline.vue'
import IconDomain from 'vue-material-design-icons/Domain.vue'
import { needsTranslation } from '../service/AiIntergrationsService.js'
import { loadState } from '@nextcloud/initial-state'

import logger from '../logger.js'
import MdnRequest from './MdnRequest.vue'
const scout = new PrintScout()

export default {
	name: 'MessageHTMLBody',
	components: {
		MdnRequest,
		NeedsTranslationInfo,
		Actions,
		ActionButton,
		IconImage,
		IconMail,
		IconDomain,
	},
	props: {
		url: {
			type: String,
			required: true,
		},
		fullHeight: {
			type: Boolean,
			required: false,
			default: false,
		},
		message: {
			required: true,
			type: Object,
		},
	},
	data() {
		return {
			hasBlockedContent: false,
			isSenderTrusted: this.message.isSenderTrusted,
			needsTranslation: false,
			enabledFreePrompt: loadState('mail', 'llm_freeprompt_available', false),
		}
	},
	computed: {
		sender() {
			return this.message.from[0]?.email
		},
		domain() {
			return this.sender?.split('@').pop()
		},
	},
	beforeMount() {
		scout.on('beforeprint', this.onBeforePrint)
	},
	async mounted() {
		iframeResize({
			license: 'GPLv3',
			log: false,
			scrolling: true,
		}, this.$refs.iframe)

		if (this.enabledFreePrompt && this.message) {
			this.needsTranslation = await needsTranslation(this.message.databaseId)
		}
	},
	beforeDestroy() {
		scout.off('beforeprint', this.onBeforePrint)
		this.$refs.iframe.iFrameResizer.close()
	},
	methods: {
		getIframeDoc() {
			const iframe = this.$refs.iframe
			return iframe.contentDocument || iframe.contentWindow.document
		},
		onMessageFrameLoad() {
			const iframeDoc = this.getIframeDoc()
			this.hasBlockedContent
				= iframeDoc.querySelectorAll('[data-original-src]').length > 0
				|| iframeDoc.querySelectorAll('[data-original-style]').length > 0
				|| iframeDoc.querySelectorAll('style[data-original-content]').length > 0

			this.$emit('load')
			if (this.isSenderTrusted) {
				this.displayIframe()
			}
		},
		onBeforePrint() {
			// this.$refs.iframe.style.setProperty('height', `${this.getIframeDoc().body.scrollHeight}px`, 'important')
		},
		displayIframe() {
			const iframeDoc = this.getIframeDoc()
			logger.debug('showing external images')
			iframeDoc.querySelectorAll('[data-original-src]').forEach((node) => {
				node.style.display = null
				node.setAttribute('src', node.getAttribute('data-original-src'))
			})
			iframeDoc
				.querySelectorAll('[data-original-style]')
				.forEach((node) => node.setAttribute('style', node.getAttribute('data-original-style')))
			iframeDoc
				.querySelectorAll('style[data-original-content]')
				.forEach((node) => {
					node.innerHTML = node.getAttribute('data-original-content')
				})
			this.hasBlockedContent = false
		},
		async onShowBlockedContent() {
			this.displayIframe()
			await trustSender(this.message.from[0].email, 'individual', true)
		},
		async onShowBlockedContentForDomain() {
			this.displayIframe()
			// TODO: there might be more than one @ in an email address
			await trustSender(this.domain, 'domain', true)
		},
	},
}
</script>

<style lang="scss" scoped>
// account for 8px margin on iframe body
.html-message-body {
	margin : 2px calc(var(--default-grid-baseline) * 2) 0 calc(var(--default-grid-baseline) * 14);
	background-color: #FFFFFF;
}

#mail-message-has-blocked-content {
	margin-inline-start: 10px;
	color: var(--color-text-maxcontrast) !important;
}

#message-container {
	flex: 1;
	display: flex;
	background-color: #FFFFFF;

	// TODO: collapse quoted text and remove inner scrollbar
	@media only screen {
		&.scroll {
			overflow-y: auto;
		}
	}
}

:deep(.button-vue__text) {
	border: none !important;
	font-weight: normal !important;
	padding-inline: 14px 10px !important;
	text-decoration: underline !important;
}

.message-frame {
	width: 100%;
}

:deep(.button-vue__icon) {
	display: none !important;
}

:deep(.button-vue--vue-tertiary) {
	color: var(--color-text-maxcontrast);
}
</style>
