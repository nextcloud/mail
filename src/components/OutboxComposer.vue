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
			:send="sendMessage"
			:forwarded-messages="forwardedMessages" />
	</Modal>
</template>
<script>
import Modal from '@nextcloud/vue/dist/Components/Modal'
import logger from '../logger'
import { html, plain, toPlain } from '../util/text'
import Composer from './Composer'
import Axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { translate as t } from '@nextcloud/l10n'

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
		forwardedMessages: {
			type: Array,
			required: false,
			default: () => [],
		},
		templateMessageId: {
			type: Number,
			required: false,
			default: undefined,
		},
	},
	data() {
		return {
			original: undefined,
			originalBody: undefined,
			fetchingTemplateMessage: true,
		}
	},
	computed: {
		outboxBody() {
			if (this.message.html) {
				return html(this.message.text)
			}
			return plain(this.message.text)
		},
	},
	created() {
		this.fetchOriginalMessage()
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

			// Remove old draft envelope
			this.$store.commit('removeEnvelope', { id: data.draftId })
			this.$store.commit('removeMessage', { id: data.draftId })
		},
		async fetchOriginalMessage() {
			if (this.templateMessageId === undefined) {
				this.fetchingTemplateMessage = false
				return
			}
			this.loading = true
			this.error = undefined
			this.errorMessage = ''

			logger.debug(`fetching original message ${this.templateMessageId}`)

			try {
				const message = await this.$store.dispatch('fetchMessage', this.templateMessageId)
				logger.debug('original message fetched', { message })
				this.original = message

				let body = plain(message.body || '')
				if (message.hasHtmlBody) {
					logger.debug('original message has HTML body')
					const resp = await Axios.get(
						generateUrl('/apps/mail/api/messages/{id}/html?plain=true', {
							Id: this.templateMessageId,
						})
					)

					body = html(resp.data)
				}
				this.originalBody = body
			} catch (error) {
				logger.error('could not load original message ' + this.templateMessageId, { error })
				if (error.isError) {
					this.errorMessage = t('mail', 'Could not load original message')
					this.error = error
					this.loading = false
				}
			} finally {
				this.loading = false
			}
			this.fetchingTemplateMessage = false
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
