<template>
	<div>
		<div v-if="mailvelope" id="mail-content" />
		<span v-else>{{ t('mail', 'This message is encrypted with PGP. Install Mailvelope to decrypt it.') }}</span>
	</div>
</template>

<script>
import { getMailvelope } from '../crypto/mailvelope'

export default {
	name: 'MessageEncryptedBody',
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
