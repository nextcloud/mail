<template>
	<div id="mail-content">
		<MdnRequest :message="message" />
		<div id="message-container" v-html="nl2br(enhancedBody)" />
		<details v-if="signature" class="mail-signature">
			<summary v-html="nl2br(signatureSummaryAndBody.summary)" />
			<span v-html="nl2br(signatureSummaryAndBody.body)" />
		</details>
	</div>
</template>

<script>
import MdnRequest from './MdnRequest'
const regFirstParagraph = /(.+\n\r?)+(\n\r?)+/

export default {
	name: 'MessagePlainTextBody',
	components: { MdnRequest },
	props: {
		body: {
			type: String,
			required: true,
		},
		signature: {
			type: String,
			default: () => undefined,
		},
		message: {
			required: true,
			type: Object,
		},
	},
	computed: {
		enhancedBody() {
			return this.body.replace(/(^&gt;.*\n)+/gm, (match) => {
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

	summary {
		cursor: pointer
	}
}
</style>
<style lang="scss" scoped>
.message-container {
	white-space: pre-wrap;
	animation: show 400ms 80ms cubic-bezier(0.38, 0.97, 0.56, 0.76) forwards;
	opacity: 0;
	transform: rotateX(-90deg);
	transform-origin: top center;
}
@keyframes show {
	100% {
		opacity: 1;
		transform: none;
	}
}
.mail-signature {
	white-space: pre-wrap;
}
.mail-signature, .quoted {
	color: var(--color-text-maxcontrast)

	summary {
		cursor: pointer
	}
}
</style>
