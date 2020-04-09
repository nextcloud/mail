<template>
	<div>
		<div id="mail-content" v-html="htmlBody" />
		<div v-if="signature" class="mail-signature" v-html="htmlSignature" />
	</div>
</template>

<script>
export default {
	name: 'MessagePlainTextBody',
	props: {
		body: {
			type: String,
			required: true,
		},
		signature: {
			type: String,
			default: () => undefined,
		},
	},
	computed: {
		htmlBody() {
			return this.nl2br(this.body)
		},
		htmlSignature() {
			return this.nl2br(this.signature)
		},
	},
	methods: {
		nl2br(str) {
			return str.replace(/(\r\n|\n\r|\n|\r)/g, '<br />')
		},
	},
}
</script>

<style scoped>
.mail-signature {
	font-family: monospace;
	opacity: 0.5;
	line-height: initial;
}
</style>
