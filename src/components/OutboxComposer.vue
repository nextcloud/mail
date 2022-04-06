<template>
	<Modal
		size="normal"
		:title="t('mail', 'Outbox draft')"
		@close="$emit('close')">
		<Composer
			:from-account="message.accountId"
			:to="message.to"
			:cc="message.cc"
			:bcc="message.bcc"
			:subject="message.subject"
			:body="outboxBody"
			:draft="saveDraft"
			:send="sendMessage" />
	</Modal>
</template>
<script>
import Modal from '@nextcloud/vue/dist/Components/Modal'
import logger from '../logger'
import { html, plain, toPlain } from '../util/text'
import Composer from './Composer'

export default {
	name: 'OutboxComposer',
	components: {
		Modal,
		Composer,
	},
	props: {
		message: {
			type: Object,
			required: true,
		},
	},
	computed: {
		outboxBody() {
			if (this.message.html) {
				return html(this.message.text)
			}
			return plain(this.message.text)
		},
	},
	methods: {
		stringToRecipients(str) {
			if (str === undefined) {
				return []
			}

			return [
				{
					label: str,
					email: str,
				},
			]
		},
		async saveDraft(data) {
			if (data.draftId === undefined && this.draft) {
				logger.debug('draft data does not have a draftId, adding one', {
					draft: this.draft,
					data,
					id: this.draft.databaseId,
				})
				data.draftId = this.draft.databaseId
			}
			const dataForServer = {
				...data,
				body: data.isHtml ? data.body.value : toPlain(data.body).value,
			}
			await this.$store.dispatch('outbox/updateMessage', { message: dataForServer, id: this.message.id })
		},
		async sendMessage(data) {
			logger.debug('sending message', { data })
			const now = new Date().getTime()
			const dataForServer = {
				accountId: data.account,
				sendAt: Math.floor(now / 1000), // JS timestamp is in milliseconds
				subject: data.subject,
				body: data.isHtml ? data.body.value : toPlain(data.body).value,
				isHtml: data.isHtml,
				isMdn: false,
				inReplyToMessageId: '',
				to: data.to,
				cc: data.cc,
				bcc: data.bcc,
				attachmentIds: [],
			}
			const message = await this.$store.dispatch('outbox/enqueueMessage', {
				message: dataForServer,
			})

			await this.$store.dispatch('outbox/sendMessage', { id: message.id })
		},
	},
}

</script>

<style lang="scss" scoped>
@media only screen and (max-width: 600px) {
	::v-deep .modal-container {
		max-width: 80%;
	}
}
::v-deep .modal-container {
	width: 80%;
	min-height: 60%;
}
::v-deep .modal-wrapper .modal-container {
	overflow-y: auto !important;
	overflow-x: auto !important;
}
</style>
