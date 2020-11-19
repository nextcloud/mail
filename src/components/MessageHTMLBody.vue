<template>
	<div id="mail-content">
		<div v-if="hasBlockedContent" id="mail-message-has-blocked-content">
			{{ t('mail', 'The images have been blocked to protect your privacy.') }}
			<button @click="onShowBlockedContent">
				{{ t('mail', 'Show images from this sender') }}
			</button>
		</div>
		<div v-if="loading" class="icon-loading" />
		<div id="message-container" :class="{hidden: loading, scroll: !fullHeight}">
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
import { iframeResizer } from 'iframe-resizer'
import PrintScout from 'printscout'
import { trustSender } from '../service/TrustedSenderService'

import logger from '../logger'
const scout = new PrintScout()

export default {
	name: 'MessageHTMLBody',
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
			loading: true,
			hasBlockedContent: false,
			isSenderTrusted: this.message.isSenderTrusted,
		}
	},
	beforeMount() {
		scout.on('beforeprint', this.onBeforePrint)
		scout.on('afterprint', this.onAfterPrint)
	},
	mounted() {
		iframeResizer({
			onInit: () => {
				const getCssVar = (key) => ({
					[key]: getComputedStyle(document.documentElement).getPropertyValue(key),
				})

				// send css vars to client page
				this.$refs.iframe.iFrameResizer.sendMessage({
					cssVars: {
						...getCssVar('--color-main-text'),
					},
				})
			},
		}, this.$refs.iframe)
	},
	beforeDestroy() {
		scout.off('beforeprint', this.onBeforePrint)
		scout.off('afterprint', this.onAfterPrint)
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

			this.loading = false
			if (this.isSenderTrusted) {
				this.onShowBlockedContent()
			}
		},
		onAfterPrint() {
			this.$refs.iframe.style.setProperty('height', '')
		},
		onBeforePrint() {
			this.$refs.iframe.style.setProperty('height', `${this.getIframeDoc().body.scrollHeight}px`, 'important')
		},
		async onShowBlockedContent() {
			const iframeDoc = this.getIframeDoc()
			logger.debug('showing external images')
			iframeDoc.querySelectorAll('[data-original-src]').forEach((node) => {
				node.style.display = null
				node.setAttribute('src', node.getAttribute('data-original-src'))
			})
			iframeDoc
				.querySelectorAll('[data-original-style]')
				.forEach((node) => node.setAttribute('style', node.getAttribute('data-original-style')))

			this.hasBlockedContent = false
			trustSender(this.message.from[0].email, true)

		},
	},
}
</script>

<style lang="scss" scoped>
// account for 8px margin on iframe body
#mail-content {
	margin-left: 48px;
	margin-top: 2px;
	display: flex;
	flex-direction: column;
	height: 100%;
}
#mail-message-has-blocked-content {
	margin-left: 8px;
}

#message-container {
	flex: 1;
	display: flex;

	// TODO: collapse quoted text and remove inner scrollbar
	&.scroll {
		max-height: 50vh;
		overflow-y: auto;
	}
}

.message-frame {
	width: 100%;
}
</style>
