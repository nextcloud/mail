<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="html-message-body">
		<BlockedContentWarning
			v-if="hasBlockedContent"
			:sender="sender"
			:domain="domain"
			@show="displayIframe"
			@trust-sender="onShowBlockedContent"
			@trust-domain="onShowBlockedContentForDomain" />
		<div class="html-message-body__content">
			<MdnRequest :message="message" />
			<NeedsTranslationInfo
				v-if="needsTranslation"
				:is-html="true"
				@translate="$emit('translate')" />
			<div id="message-container" :class="{ scroll: !fullHeight }">
				<iframe
					ref="iframe"
					class="message-frame"
					:title="t('mail', 'Message frame')"
					:src="url"
					seamless
					@load="onMessageFrameLoad" />
			</div>
		</div>
	</div>
</template>

<script>
import iframeResize from '@iframe-resizer/parent'
import { loadState } from '@nextcloud/initial-state'
import BlockedContentWarning from './BlockedContentWarning.vue'
import MdnRequest from './MdnRequest.vue'
import NeedsTranslationInfo from './NeedsTranslationInfo.vue'
import logger from '../logger.js'
import { needsTranslation } from '../service/AiIntergrationsService.js'
import { trustSender } from '../service/TrustedSenderService.js'
import { isPrintShortcut } from '../util/printMessage.ts'

export default {
	name: 'MessageHTMLBody',
	components: {
		BlockedContentWarning,
		MdnRequest,
		NeedsTranslationInfo,
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
		// The frame's document goes away with the frame, so this is housekeeping
		// rather than a fix for a leak. It is done because the listener is added
		// to a document this component does not own: nothing guarantees the frame
		// is torn down right away, and until it is, a keydown in it would still
		// reach a destroyed component.
		this.getIframeDoc()?.removeEventListener('keydown', this.onFrameKeyDown)
		this.$refs.iframe?.iFrameResizer?.close()
	},

	methods: {
		getIframeDoc() {
			const iframe = this.$refs.iframe
			return iframe?.contentDocument ?? iframe?.contentWindow?.document ?? null
		},

		onMessageFrameLoad() {
			const iframeDoc = this.getIframeDoc()

			// A frame has a window of its own, and a keydown is delivered to the
			// window of whatever is focused. So while the reader has the message
			// focused, the app's own window listener never sees the print
			// shortcut and the browser would take it instead.
			iframeDoc.addEventListener('keydown', this.onFrameKeyDown)

			this.hasBlockedContent
				= iframeDoc.querySelectorAll('[data-original-src]').length > 0
					|| iframeDoc.querySelectorAll('[data-original-style]').length > 0
					|| iframeDoc.querySelectorAll('style[data-original-content]').length > 0

			this.$emit('load')
			if (this.isSenderTrusted) {
				this.displayIframe()
			}
		},

		/**
		 * Hand the print shortcut to the app, from inside the message frame.
		 *
		 * @param {KeyboardEvent} event the frame's keydown event
		 */
		onFrameKeyDown(event) {
			if (!isPrintShortcut(event)) {
				return
			}
			event.preventDefault()
			this.$emit('print-shortcut')
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
// account for 12px (was 8) margin on iframe body
// should be 12px so it matches the rest of the content
.html-message-body {
	display: flex;
	flex-direction: column;
	gap: calc(var(--default-grid-baseline) * 2);
	margin : 2px calc(var(--default-grid-baseline) * 3) 0 calc(var(--default-grid-baseline) * 14);

	@media (max-width: 600px) {
        margin-inline: calc(var(--default-grid-baseline) * 3);
    }

	&__content {
		background-color: #FFFFFF;
		border-radius: var(--border-radius-element);
	}
}

#message-container {
	flex: 1;
	display: flex;
	background-color: #FFFFFF;
	border-radius: var(--border-radius-element);

	// TODO: collapse quoted text and remove inner scrollbar
	@media only screen {
		&.scroll {
			overflow-y: auto;
		}
	}
}

.message-frame {
	width: 100%;
	border-radius: var(--border-radius-element);
}
</style>
