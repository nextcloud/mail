<template>
	<div id="mail-content">
		<div id="show-images-text">
			<!--{{ t 'The images have been blocked to protect your privacy.' }}-->
			<button id="show-images-button">
				<!--{{ t 'Show images from this	sender' }}-->
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
				loading: true
			}
		},
		methods: {
			onMessageFrameLoad () {
				const iframe = this.$refs.iframe
				const iframeDoc = iframe.contentDocument || iframe.contentWindow.document
				const iframeBody = iframeDoc.querySelectorAll('body')[0]

				console.log('todo: resize', iframe)
				this.$emit('loaded', iframeBody.outerHTML)
				this.loading = false
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
