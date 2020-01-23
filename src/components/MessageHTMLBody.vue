<template>
	<div id="mail-content">
		<div v-if="loading" class="icon-loading" />
		<div id="message-container" :class="{hidden: loading}">
			<iframe id="message-frame" ref="iframe" :src="url" seamless @load="onMessageFrameLoad" />
		</div>
	</div>
</template>

<script>
import PrintScout from 'printscout'
const scout = new PrintScout()

import logger from '../logger'

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
			const iframeBody = iframeDoc.querySelectorAll('body')[0]
			this.hasBlockedContent =
				iframeDoc.querySelectorAll('[data-original-src]').length > 0 ||
				iframeDoc.querySelectorAll('[data-original-style]').length > 0

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
			iframeDoc.querySelectorAll('[data-original-src]').forEach(node => {
				node.style.display = null
				node.setAttribute('src', node.getAttribute('data-original-src'))
			})
			iframeDoc
				.querySelectorAll('[data-original-style]')
				.forEach(node => node.setAttribute('style', node.getAttribute('data-original-style')))

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
}
#mail-message-has-blocked-content {
	margin-left: 8px;
}

#message-container {
	position: relative;
	width: 100%;
	height: 0;
	padding-bottom: 56.25%;
}

#message-frame {
	position: absolute;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
}
</style>
