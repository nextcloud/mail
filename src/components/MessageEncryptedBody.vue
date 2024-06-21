<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div>
		<div v-if="mailvelope" id="mail-content">
			<MdnRequest :message="message" />
		</div>
		<span v-else>{{ t('mail', 'This message is encrypted with PGP. Install Mailvelope to decrypt it.') }}</span>
	</div>
</template>

<script>
import { getMailvelope } from '../crypto/mailvelope.js'
import MdnRequest from './MdnRequest.vue'

export default {
	name: 'MessageEncryptedBody',
	components: { MdnRequest },
	props: {
		body: {
			type: String,
			required: true,
		},
		from: {
			type: String,
			required: false,
			default: undefined,
		},
		message: {
			required: true,
			type: Object,
		},
	},
	data() {
		return {
			mailvelope: false,
		}
	},
	async mounted() {
		this.mailvelope = await getMailvelope()
		this.mailvelope.createDisplayContainer('#mail-content', this.body, undefined, {
			senderAddress: this.from,
		})
	},
}
</script>

<style scoped>
#mail-content {
	height: 450px;
}
</style>
