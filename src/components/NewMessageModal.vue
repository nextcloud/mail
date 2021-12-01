<template>
	<Modal
		size="normal"
		:title="t('mail', 'New message')"
		@close="$emit('close')">
		<Composer v-if="!fetchingTemplateMessage"
			:from-account="composerData.accountId"
			:to="composerData.to"
			:cc="composerData.cc"
			:bcc="composerData.bcc"
			:subject="composerData.subject"
			:body="composerData.body"
			:draft="saveDraft"
			:send="sendMessage"
			:forwarded-messages="forwardedMessages" />
	</Modal>
</template>
<script>
import Modal from '@nextcloud/vue/dist/Components/Modal'
import logger from '../logger'
import { detect, html, plain, toPlain } from '../util/text'
import { saveDraft, sendMessage } from '../service/MessageService'
import Composer from './Composer'
import { showWarning } from '@nextcloud/dialogs'
import Axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { translate as t } from '@nextcloud/l10n'
export default {
	name: 'NewMessageModal',
	components: {
		Modal,
		Composer,
	},
	props: {
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
		composerData() {
			logger.debug('composing a new message or handling a mailto link', {
				threadId: this.$route.params.threadId,
			})

			let accountId
			// Only preselect an account when we're not in a unified mailbox
			if (this.$route.params.accountId !== 0 && this.$route.params.accountId !== '0') {
				accountId = parseInt(this.$route.params.accountId, 10)
			}
			if (this.templateMessageId !== undefined) {
				if (this.original.attachments.length) {
					showWarning(t('mail', 'Attachments were not copied. Please add them manually.'))
				}

				return {
					accountId: this.original.accountId,
					to: this.original.to,
					cc: this.original.cc,
					subject: this.original.subject,
					body: this.originalBody,
					originalBody: this.originalBody,
				}
			}

			return {
				accountId,
				to: this.stringToRecipients(this.$route.query.to),
				cc: this.stringToRecipients(this.$route.query.cc),
				subject: this.$route.query.subject || '',
				body: this.$route.query.body ? detect(this.$route.query.body) : html(''),
			}
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
			const { id } = await saveDraft(data.account, dataForServer)

			// Remove old draft envelope
			this.$store.commit('removeEnvelope', { id: data.draftId })
			this.$store.commit('removeMessage', { id: data.draftId })

			// Fetch new draft envelope
			await this.$store.dispatch('fetchEnvelope', id)

			return id
		},
		async sendMessage(data) {
			logger.debug('sending message', { data })
			const dataForServer = {
				...data,
				body: data.isHtml ? data.body.value : toPlain(data.body).value,
			}
			await sendMessage(data.account, dataForServer)

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
