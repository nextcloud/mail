<template>
	<div>
		<div id="mail-content" v-html="nl2br(body)" />
		<details v-if="signature" class="mail-signature">
			<summary>{{ signatureSummaryAndBody.summary }}</summary>
			<span v-html="nl2br(signatureSummaryAndBody.body)" />
		</details>
	</div>
</template>

<script>
const regFirstParagraph = /(.+\n\r?)+(\n\r?)+/

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
		signatureSummaryAndBody() {
			const matches = this.signature.match(regFirstParagraph)

			if (matches[0]) {
				return {
					summary: matches[0],
					body: this.signature.substring(matches[0].length),
				}
			}

			const lines = this.signature.trim().split(/\r?\n/)
			return {
				summary: lines[0],
				body: lines.slice(1).join('\n'),
			}
		},
		signatureSummary() {
			console.info(this.signature.match(regFirstParagraph))

			return this.signatureSummaryAndBody.summary
		},
	},
	methods: {
		nl2br(str) {
			return str.replace(/(\r\n|\n\r|\n|\r)/g, '<br />')
		},
	},
}
</script>

<style lang="scss" scoped>
#mail-content, .mail-signature {
	white-space: pre;
}
.mail-signature {
	color: var(--color-text-maxcontrast)
}
</style>
