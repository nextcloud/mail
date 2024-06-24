<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div id="mailvelope-composer" />
</template>

<script>
import logger from '../logger.js'
import { isPgpgMessage } from '../crypto/pgp.js'

export default {
	name: 'MailvelopeEditor',
	props: {
		value: {
			type: String,
			required: true,
		},
		recipients: {
			type: Array,
			required: true,
		},
		quotedText: {
			type: Object,
			required: false,
			default: () => undefined,
		},
		isReplyOrForward: {
			type: Boolean,
			default: false,
		},
	},
	data() {
		return {
			editor: undefined,
		}
	},
	async mounted() {
		const isEncrypted = this.quotedText ? isPgpgMessage(this.quotedText) : false
		const quotedMail = this.isReplyOrForward ? this.quotedText?.value : undefined

		this.editor = await window.mailvelope.createEditorContainer('#mailvelope-composer', undefined, {
			quotedMail: isEncrypted ? quotedMail : undefined,
		})
	},
	methods: {
		async pull() {
			const recipients = this.recipients.map((r) => r.email)
			logger.info('encrypting message', { recipients })
			const armored = await this.editor.encrypt(recipients)
			logger.info('message encryted', { armored })

			this.$emit('input', armored)
		},
	},
}
</script>

<style scoped>
#mailvelope-composer {
	width: 100%;
	height: 450px;
}
</style>
