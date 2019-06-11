<template>
	<span>
		<button @click="onPrint">
			{{ t('mail', 'Print') }}
		</button>
		<iframe id="message-print" ref="iframe" src="about:blank" seamless @load="replaceBody"></iframe>
	</span>
</template>

<script>
export default {
	name: 'MessagePrint',
	props: {
		header: {
			type: String,
			required: true,
		},
		body: {
			type: String,
			required: true,
		},
	},
	methods: {
		getIframeDoc() {
			const iframe = this.$refs.iframe
			return iframe.contentDocument || iframe.contentWindow.document
		},
		replaceBody() {
			const iframeDoc = this.getIframeDoc()
			const iframeBody = iframeDoc.querySelectorAll('body')[0]
			iframeBody.innerHTML = this.header + this.body
		},
		onPrint() {
			const iframeWin = this.$refs.iframe.contentWindow
			iframeWin.focus()
			iframeWin.print()
		},
	},
}
</script>

<style scoped>
#message-print {
	display: none;
}
</style>
