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
			:is-plain-text="composerData.isPlainText"
		/>
	</AppContentDetails>
</template>

<script>
import AppContentDetails from '@nextcloud/vue/dist/Components/AppContentDetails'
import Axios from '@nextcloud/axios'
import {generateUrl} from '@nextcloud/router'

import {buildForwardSubject, buildHtmlReplyBody, buildReplyBody, buildReplySubject} from '../ReplyBuilder'
import Composer from './Composer'
import {getRandomMessageErrorMessage} from '../util/ErrorMessageFactory'
import Error from './Error'
import Loading from './Loading'
import Logger from '../logger'
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
				Logger.info('todo: handle draft data', {draft: this.draft})
				return {
					to: this.draft.to,
					cc: this.draft.cc,
					bcc: this.draft.bcc, // TODO: impl in composer
					subject: this.draft.subject,
					body: this.draft.body,
					isPlainText: this.draft.hasHtmlBody !== undefined,
				}
			} else if (this.$route.query.uid !== undefined) {
				// Forward or reply to a message
				const message = this.original
				Logger.debug('forwarding or replying to message', {message})

				// message headers set for 'reply' actions by default
				let subject = buildReplySubject(message.subject)
				let msgTo = message.from
				let msgCc = []
				if (this.$route.params.messageUid === 'replyAll') {
					msgCc = message.to.concat(message.cc)
				} else if (this.$route.params.messageUid !== 'reply') {
					// forward
					subject = buildForwardSubject(message.subject)
					msgTo = []
				}

				return {
					to: msgTo,
					cc: msgCc,
					subject: subject,
					body: this.getForwardReplyBody(),
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
					body: this.$route.query.body || '',
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
				Logger.debug('detected navigation to current (new) draft UID, not reloading')
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
				.then(draft => {
					if (draft.uid !== this.$route.params.draftUid) {
						Logger.debug("User navigated away, loaded draft won't be shown")
						return
					}

					this.draft = draft

					if (this.draft === undefined) {
						Logger.info('draft could not be found', {draftUid})
						this.errorMessage = getRandomMessageErrorMessage()
						this.loading = false
						return
					}

					this.loading = false
				})
				.catch(error => {
					Logger.error('could not load draft ' + draftUid, {error})
					if (error.isError) {
						this.errorMessage = t('mail', 'Could not load your draft')
						this.error = error
						this.loading = false
					}
				})
		},
		fetchOriginalMessage(uid) {
			this.loading = true
			this.error = undefined
			this.errorMessage = ''

			this.$store
				.dispatch('fetchMessage', uid)
				.then(message => {
					if (message.uid !== this.$route.query.uid) {
						Logger.debug("User navigated away, loaded original message won't be used")
						return
					}

					Logger.debug('original message fetched', {message})
					this.original = message

					if (message.hasHtmlBody) {
						Logger.debug('original message has HTML body')
						return Axios.get(
							generateUrl('/apps/mail/api/accounts/{accountId}/folders/{folderId}/messages/{id}/html', {
								accountId: message.accountId,
								folderId: message.folderId,
								id: message.id,
							})
						).then(resp => resp.data)
					} else {
						return message.body
					}
				})
				.then(body => {
					this.originalBody = body

					this.loading = false
				})
				.catch(error => {
					Logger.error('could not load original message ' + uid, {error})
					if (error.isError) {
						this.errorMessage = t('mail', 'Could not load original message')
						this.error = error
						this.loading = false
					}
				})
		},
		getForwardReplyBody() {
			if (this.original.hasHtmlBody) {
				return buildHtmlReplyBody(this.originalBody, this.original.from[0], this.original.dateInt)
			}
			return buildReplyBody(this.originalBody, this.original.from[0], this.original.dateInt)
		},
		saveDraft(data) {
			if (data.draftUID === undefined && this.draft) {
				Logger.debug('draft data does not have a draftUID, adding one')
				data.draftUID = this.draft.id
			}
			return saveDraft(data.account, data).then(({uid}) => {
				if (this.draft === undefined) {
					return uid
				}

				Logger.info('replacing draft ' + this.draft.uid + ' with ' + uid)
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
			return sendMessage(data.account, data)
		},
	},
}
</script>
