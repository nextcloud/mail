<template>
	<div id="mail-content">
		<div v-if="hasBlockedContent">
			{{ t('mail', 'The images have been blocked to protect your privacy.') }}
			<button @click="onShowBlockedContent">
				{{ t('mail', 'Show images from this	sender') }}
			</button>
		</div>
		<div v-if="loading"
			 class="icon-loading"/>
		<div :class="{hidden: loading}"
			 id="message-container">
			<iframe id="message-frame"
					ref="iframe"
					@load="onMessageFrameLoad"
					:src="url"
					seamless/>
		</div>
	</div>
</template>

<script>
	export default {
		name: "MessageHTMLBody",
		props: {
			url: {
				type: String,
				required: true,
			},
		},
		data () {
			return {
				loading: true,
				hasBlockedContent: false,
			}
		},
		methods: {
			getIframeDoc () {
				const iframe = this.$refs.iframe
				return iframe.contentDocument || iframe.contentWindow.document
			},
			onMessageFrameLoad () {
				const iframeDoc = this.getIframeDoc()
				const iframeBody = iframeDoc.querySelectorAll('body')[0]
				this.hasBlockedContent = iframeDoc.querySelectorAll('[data-original-src]').length > 0
						|| iframeDoc.querySelectorAll('[data-original-style]').length > 0

				this.$emit('loaded', iframeBody.outerHTML)
				this.loading = false
			},
			onShowBlockedContent () {
				const iframeDoc = this.getIframeDoc()
				iframeDoc.querySelectorAll('[data-original-src]').forEach(
					node =>	node.setAttribute('src', node.getAttribute('data-original-src'))
				)
				iframeDoc.querySelectorAll('[data-original-style]').forEach(node =>
					node.setAttribute('style', node.getAttribute('data-original-style'))
				)

				this.hasBlockedContent = false
			}
		}
	};
</script>

<style scoped>
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
