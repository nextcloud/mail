<template>
	<div>
		<div id="mail-content" v-html="sanitizedBody"></div>
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
	computed: {
		/**
		 * Caution: Despite its name this function doesn't really sanitize a message's body.
		 * It just replaces newline chars by '<br>' HTML tags.
		 * The message's body is expected to have been properly sanitized earlier (currently
		 * via the app's Html service (PHP), via UrlLinker->linkUrlsAndEscapeHtml()).
		 */
		sanitizedBody() {
			return this.body.replace(/\n/g,'<br>')
		}
	}
}
</script>

<style scoped>
.mail-signature {
	font-family: monospace;
	opacity: 0.5;
	line-height: initial;
}
</style>
