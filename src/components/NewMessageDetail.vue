<template>
	<AppContentDetails>
		<Loading v-if="loading" />
		<Error
			v-else-if="error"
			:error="error && error.message ? error.message : t('mail', 'Not found')"
			:message="errorMessage"
			:data="error"
		>
		</Error>
		<Composer
			v-else
			:from-account="composerData.accountId"
			:to="composerData.to"
			:cc="composerData.cc"
			:bcc="composerData.bcc"
			:subject="composerData.subject"
			:body="composerData.body"
			:draft="saveDraft"
			:send="sendMessage"
			:reply-to="composerData.replyTo"
			:forward-from="composerData.forwardFrom"
		/>
	</AppContentDetails>
</template>

<script>
import AppContentDetails from '@nextcloud/vue/dist/Components/AppContentDetails'
import Axios from '@nextcloud/axios'
import {generateUrl} from '@nextcloud/router'

import {buildForwardSubject, buildReplySubject, buildRecipients as buildReplyRecipients} from '../ReplyBuilder'
import Composer from './Composer'
import Error from './Error'
import {getRandomMessageErrorMessage} from '../util/ErrorMessageFactory'
import {detect, html, plain, toPlain} from '../util/text'
import Loading from './Loading'
import logger from '../logger'
import {saveDraft, sendMessage} from '../service/MessageService'

export default {
	name: 'NewMessageDetail',
	components: {
		AppContentDetails,
		Composer,
		Error,
		Loading,
	},
	data() {
		return {
			loading: false,
			draft: undefined,
			original: undefined,
			originalBody: undefined,
			errorMessage: '',
			error: undefined,
		}
	},
	computed: {
		composerData() {
			if (this.draft !== undefined) {
				logger.info('todo: handle draft data', {draft: this.draft})
				return {
					to: this.draft.to,
					cc: this.draft.cc,
					bcc: this.draft.bcc,
					subject: this.draft.subject,
					body: this.draft.hasHtmlBody ? html(this.draft.body) : plain(this.draft.body),
				}
			} else if (this.$route.query.uid !== undefined) {
				// Forward or reply to a message
				const message = this.original
				logger.debug('forwarding or replying to message', {message})

				if (this.$route.params.messageUid === 'reply') {
					logger.debug('simple reply')

					return {
						accountId: message.accountId,
						to: message.from,
						cc: [],
						subject: buildReplySubject(message.subject),
						body: this.originalBody,
						originalBody: this.originalBody,
						replyTo: message,
					}
				} else if (this.$route.params.messageUid === 'replyAll') {
					logger.debug('replying to all', {original: this.original})
					const account = this.$store.getters.getAccount(message.accountId)
					const recipients = buildReplyRecipients(message, {
						email: account.emailAddress,
						label: account.name,
					})

					return {
						accountId: message.accountId,
						to: recipients.to,
						cc: recipients.cc,
						subject: buildReplySubject(message.subject),
						body: this.originalBody,
						originalBody: this.originalBody,
						replyTo: message,
					}
				} else {
					// forward
					return {
						accountId: message.accountId,
						to: [],
						cc: [],
						subject: buildForwardSubject(message.subject),
						body: this.originalBody,
						originalBody: this.originalBody,
						forwardFrom: message,
					}
				}
			} else {
				// New or mailto: message

				let accountId
				// Only preselect an account when we're not in a unified mailbox
				if (this.$route.params.accountId !== 0 && this.$route.params.accountId !== '0') {
					accountId = parseInt(this.$route.params.accountId, 10)
				}

				return {
					accountId,
					to: this.stringToRecipients(this.$route.query.to),
					cc: this.stringToRecipients(this.$route.query.cc),
					subject: this.$route.query.subject || '',
					body: this.$route.query.body ? detect(this.$route.query.body) : html(''),
				}
			}
		},
	},
	watch: {
		$route(to, from) {
			// `saveDraft` replaced the current URL with the updated draft UID
			// in that case we don't really start a new draft but just keep the
			// URL consistent, hence not loading anything
			if (this.draft && to.name === 'message' && to.params.draftUid === this.draft.uid) {
				logger.debug('detected navigation to current (new) draft UID, not reloading')
				return
			}

			this.fetchMessage()
		},
	},
	created() {
		this.fetchMessage()
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
		fetchMessage() {
			if (this.$route.params.draftUid !== undefined) {
				return this.fetchDraftMessage(this.$route.params.draftUid)
			} else if (this.$route.query.uid !== undefined) {
				return this.fetchOriginalMessage(this.$route.query.uid)
			}
		},
		fetchDraftMessage(draftUid) {
			this.loading = true
			this.draft = undefined
			this.error = undefined
			this.errorMessage = ''

			this.$store
				.dispatch('fetchMessage', draftUid)
				.then((draft) => {
					if (draft.uid !== this.$route.params.draftUid) {
						logger.debug("User navigated away, loaded draft won't be shown")
						return
					}

					this.draft = draft

					if (this.draft === undefined) {
						logger.info('draft could not be found', {draftUid})
						this.errorMessage = getRandomMessageErrorMessage()
						this.loading = false
						return
					}

					this.loading = false
				})
				.catch((error) => {
					logger.error('could not load draft ' + draftUid, {error})
					if (error.isError) {
						this.errorMessage = t('mail', 'Could not load your draft')
						this.error = error
						this.loading = false
					}
				})
		},
		async fetchOriginalMessage(uid) {
			this.loading = true
			this.error = undefined
			this.errorMessage = ''

			try {
				const message = await this.$store.dispatch('fetchMessage', uid)
				if (message.uid !== this.$route.query.uid) {
					logger.debug("User navigated away, loaded original message won't be used")
					return
				}

				logger.debug('original message fetched', {message})
				this.original = message

				let body = plain(message.body || '')
				if (message.hasHtmlBody) {
					logger.debug('original message has HTML body')
					const resp = await Axios.get(
						generateUrl('/apps/mail/api/accounts/{accountId}/folders/{folderId}/messages/{id}/html', {
							accountId: message.accountId,
							folderId: message.folderId,
							id: message.id,
						})
					)

					body = html(resp.data)
				}
				this.originalBody = body
			} catch (error) {
				logger.error('could not load original message ' + uid, {error})
				if (error.isError) {
					this.errorMessage = t('mail', 'Could not load original message')
					this.error = error
					this.loading = false
				}
			} finally {
				this.loading = false
			}
		},
		saveDraft(data) {
			if (data.draftUID === undefined && this.draft) {
				logger.debug('draft data does not have a draftUID, adding one')
				data.draftUID = this.draft.id
			}
			const dataForServer = {
				...data,
				body: data.isHtml ? data.body.value : toPlain(data.body).value,
			}
			return saveDraft(data.account, dataForServer).then(({uid}) => {
				if (this.draft === undefined) {
					return uid
				}

				logger.info('replacing draft ' + this.draft.uid + ' with ' + uid)
				const update = {
					draft: this.draft,
					uid,
					data,
				}
				return this.$store
					.dispatch('replaceDraft', update)
					.then(() =>
						this.$router.replace({
							name: 'message',
							params: {
								accountId: this.$route.params.accountId,
								folderId: this.$route.params.folderId,
								messageUid: 'new',
								draftUid: this.draft.uid,
							},
						})
					)
					.then(() => uid)
			})
		},
		sendMessage(data) {
			logger.debug('sending message', {data})
			const dataForServer = {
				...data,
				body: data.isHtml ? data.body.value : toPlain(data.body).value,
			}
			return sendMessage(data.account, dataForServer)
		},
	},
}
</script>
