<template>
	<div>
		<div id="mail-content" v-html="body"></div>
		<div v-if="signature" class="mail-signature" v-html="signature"></div>
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
}
</script>

<style scoped>
.mail-signature {
	font-family: monospace;
	opacity: 0.5;
	line-height: initial;
}
</style>
