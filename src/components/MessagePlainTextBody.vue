<template>
	<div>
		<div id="mail-content" v-html="nl2br(enhancedBody)" />
		<details v-if="signature" class="mail-signature">
			<summary v-html="nl2br(signatureSummaryAndBody.summary)" />
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
		enhancedBody() {
			return this.body.replace(/(&gt;.*\n?)+/g, (match) => {
				return `<details class="quoted-text"><summary>${t('mail', 'Quoted text')}</summary>${match}</details>`
			})
		},
		signatureSummaryAndBody() {
			const matches = this.signature.trim().match(regFirstParagraph)

			if (matches && matches[0]) {
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

<style lang="scss">
.quoted-text {
	color: var(--color-text-maxcontrast)
}
</style>
<style lang="scss" scoped>
#mail-content, .mail-signature {
	white-space: pre-wrap;
}
.mail-signature, .quoted {
	color: var(--color-text-maxcontrast)
}
</style>
