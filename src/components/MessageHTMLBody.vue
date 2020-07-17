<template>
	<div id="mail-content">
		<div v-if="hasBlockedContent" id="mail-message-has-blocked-content">
			{{ t('mail', 'The images have been blocked to protect your privacy.') }}
			<button @click="onShowBlockedContent">
				{{ t('mail', 'Show images from this sender') }}
			</button>
		</div>
		<div v-if="loading" class="icon-loading" />
		<div id="message-container" :class="{hidden: loading}">
			<iframe id="message-frame"
				ref="iframe"
				:title="t('mail', 'Message frame')"
				:src="url"
				seamless
				@load="onMessageFrameLoad" />
		</div>
	</div>
</template>

<script>
import PrintScout from 'printscout'

import logger from '../logger'
const scout = new PrintScout()

export default {
	name: 'MessageHTMLBody',
	props: {
		url: {
			type: String,
			required: true,
		},
	},
	data() {
		return {
			loading: true,
			hasBlockedContent: false,
		}
	},
	beforeMount() {
		scout.on('beforeprint', this.onBeforePrint)
		scout.on('afterprint', this.onAfterPrint)
	},
	beforeDestroy() {
		scout.off('beforeprint', this.onBeforePrint)
		scout.off('afterprint', this.onAfterPrint)
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
		},
		onAfterPrint() {
			this.$refs.iframe.style.setProperty('height', '')
		},
		onBeforePrint() {
			this.$refs.iframe.style.setProperty('height', `${this.getIframeDoc().body.scrollHeight}px`, 'important')
		},
		onShowBlockedContent() {
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
		},
	},
}
</script>

<style lang="scss" scoped>
// account for 8px margin on iframe body
#mail-content {
	margin-left: 30px;
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
}

#message-frame {
	height: 100%;
	width: 100%;
}
</style>
