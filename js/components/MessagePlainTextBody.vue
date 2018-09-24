<template>
	<div>
		<div id="mail-content" v-html="body" />
		<div v-if="signature"
			 class="mail-signature"
			 v-html="signature" />
	</div>
</template>

<script>
	export default {
		name: "MessagePlainTextBody",
		props: [
			'body',
			'signature',
		]
	};
</script>

<style scoped>
	.mail-signature {
		font-family: monospace;
		opacity: 0.5;
		line-height: initial;
	}
</style>
