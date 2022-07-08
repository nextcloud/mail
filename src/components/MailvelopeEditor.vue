<!--
  - @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @license AGPL-3.0-or-later
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
	<div id="mailvelope-composer" />
</template>

<script>
import logger from '../logger'
import { isPgpgMessage } from '../crypto/pgp'

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
