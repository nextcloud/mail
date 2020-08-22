<template>
	<AppContentDetails id="mail-message">
		<Loading v-if="loading" />
		<Error
			v-else-if="!message"
			:error="error && error.message ? error.message : t('mail', 'Not found')"
			:message="errorMessage"
			:data="error" />
		<template v-else>
			<div id="mail-message-header">
				<div id="mail-message-header-fields">
					<h2 :title="message.subject">
						{{ message.subject }}
					</h2>
					<p class="transparency">
						<AddressList :entries="message.from" />
						{{ t('mail', 'to') }}
						<AddressList :entries="message.to" />
						<template v-if="message.cc.length">
							({{ t('mail', 'cc') }} <AddressList :entries="message.cc" />)
						</template>
					</p>
				</div>
				<div id="mail-message-actions">
					<div
						:class="
							hasMultipleRecipients
								? 'icon-reply-all-white button primary'
								: 'icon-reply-white button primary'
						"
						@click="hasMultipleRecipients ? replyAll() : replyMessage()">
						<span class="action-label">{{ t('mail', 'Reply') }}</span>
					</div>
					<Actions id="mail-message-actions-menu" class="app-content-list-item-menu" menu-align="right">
						<ActionButton v-if="hasMultipleRecipients" icon="icon-reply" @click="replyMessage">
							{{ t('mail', 'Reply to sender only') }}
						</ActionButton>
						<ActionButton icon="icon-forward" @click="forwardMessage">
							{{ t('mail', 'Forward') }}
						</ActionButton>
						<ActionButton icon="icon-important" @click.prevent="onToggleImportant">
							{{
								envelope.flags.important ? t('mail', 'Mark unimportant') : t('mail', 'Mark important')
							}}
						</ActionButton>
						<ActionButton icon="icon-starred" @click.prevent="onToggleFlagged">
							{{
								envelope.flags.flagged ? t('mail', 'Mark unfavorite') : t('mail', 'Mark favorite')
							}}
						</ActionButton>
						<ActionButton icon="icon-mail" @click="onToggleSeen">
							{{ envelope.flags.seen ? t('mail', 'Mark unread') : t('mail', 'Mark read') }}
						</ActionButton>

						<ActionButton icon="icon-junk" @click="onToggleJunk">
							{{ envelope.flags.junk ? t('mail', 'Mark not spam') : t('mail', 'Mark as spam') }}
						</ActionButton>
						<ActionButton
							:icon="sourceLoading ? 'icon-loading-small' : 'icon-details'"
							:disabled="sourceLoading"
							@click="onShowSource">
							{{ t('mail', 'View source') }}
						</ActionButton>
						<ActionButton icon="icon-delete" @click.prevent="onDelete">
							{{ t('mail', 'Delete') }}
						</ActionButton>
					</Actions>
					<Modal v-if="showSource" @close="onCloseSource">
						<div class="section">
							<h2>{{ t('mail', 'Message source') }}</h2>
							<pre class="message-source">{{ rawMessage }}</pre>
						</div>
					</Modal>
				</div>
			</div>
			<div :class="[message.hasHtmlBody ? 'mail-message-body mail-message-body-html' : 'mail-message-body']">
				<div v-if="message.itineraries.length > 0" class="message-itinerary">
					<Itinerary :entries="message.itineraries" :message-id="message.messageId" />
				</div>
				<MessageHTMLBody v-if="message.hasHtmlBody" :url="htmlUrl" />
				<MessageEncryptedBody v-else-if="isEncrypted" :body="message.body" :from="from" />
				<MessagePlainTextBody v-else :body="message.body" :signature="message.signature" />
				<Popover v-if="message.attachments[0]" class="attachment-popover">
					<Actions slot="trigger">
						<ActionButton icon="icon-public icon-attachment">
							Attachments
						</ActionButton>
					</Actions>
					<MessageAttachments :attachments="message.attachments" />
				</Popover>
				<div id="reply-composer" />
			</div>
		</template>
	</AppContentDetails>
</template>

<script>
import Actions from '@nextcloud/vue/dist/Components/Actions'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import Popover from '@nextcloud/vue/dist/Components/Popover'
import AppContentDetails from '@nextcloud/vue/dist/Components/AppContentDetails'
import axios from '@nextcloud/axios'
import Modal from '@nextcloud/vue/dist/Components/Modal'
import { generateUrl } from '@nextcloud/router'

import AddressList from './AddressList'
import { buildRecipients as buildReplyRecipients, buildReplySubject } from '../ReplyBuilder'
import Error from './Error'
import { getRandomMessageErrorMessage } from '../util/ErrorMessageFactory'
import { html, plain } from '../util/text'
import { isPgpgMessage } from '../crypto/pgp'
import Itinerary from './Itinerary'
import MessageEncryptedBody from './MessageEncryptedBody'
import MessageHTMLBody from './MessageHTMLBody'
import MessagePlainTextBody from './MessagePlainTextBody'
import Loading from './Loading'
import logger from '../logger'
import MessageAttachments from './MessageAttachments'

export default {
	name: 'Message',
	components: {
		ActionButton,
		Actions,
		AddressList,
		AppContentDetails,
		Error,
		Itinerary,
		Loading,
		MessageAttachments,
		MessageEncryptedBody,
		MessageHTMLBody,
		MessagePlainTextBody,
		Modal,
		Popover,
	},
	data() {
		return {
			loading: true,
			message: undefined,
			envelope: undefined,
			errorMessage: '',
			error: undefined,
			replyRecipient: {},
			replySubject: '',
			rawMessage: '',
			sourceLoading: false,
			showSource: false,
		}
	},
	computed: {
		from() {
			return this.message.from[0]?.email
		},
		isEncrypted() {
			return isPgpgMessage(this.message.hasHtmlBody ? html(this.message.body) : plain(this.message.body))
		},
		htmlUrl() {
			return generateUrl('/apps/mail/api/messages/{id}/html', {
				id: this.envelope.databaseId,
			})
		},
		hasMultipleRecipients() {
			return this.replyRecipient.to.concat(this.replyRecipient.cc).length > 1
		},
	},
	watch: {
		$route(to, from) {
			if (
				from.name === to.name
				&& from.params.mailboxId === to.params.mailboxId
				&& from.params.threadId === to.params.threadId
				&& from.params.filter === to.params.filter
			) {
				logger.debug('navigated but the message is still the same')
				return
			}
			logger.debug('navigated to another message', { to, from })
			this.fetchMessage()
		},
	},
	created() {
		this.fetchMessage()
	},
	methods: {
		async fetchMessage() {
			this.loading = true
			this.message = undefined
			this.errorMessage = ''
			this.error = undefined
			this.replyRecipient = {}
			this.replySubject = ''

			const threadId = this.$route.params.threadId

			try {
				const [envelope, message] = await Promise.all([
					this.$store.dispatch('fetchEnvelope', threadId),
					this.$store.dispatch('fetchMessage', threadId),
				])
				logger.debug('envelope and message fetched', { envelope, message })
				// TODO: add timeout so that message isn't flagged when only viewed
				//       for a few seconds
				if (envelope && envelope.databaseId !== parseInt(this.$route.params.threadId, 10)) {
					logger.debug("User navigated away, loaded message won't be shown nor flagged as seen", {
						messageId: envelope.databaseId,
						threadId: this.$route.params.threadId,
					})
					return
				}

				this.envelope = envelope
				this.message = message

				if (envelope === undefined || message === undefined) {
					logger.info('message could not be found', { threadId, envelope, message })
					this.errorMessage = getRandomMessageErrorMessage()
					this.loading = false
					return
				}

				const account = this.$store.getters.getAccount(envelope.accountId)
				this.replyRecipient = buildReplyRecipients(message, {
					label: account.name,
					email: account.emailAddress,
				})

				this.replySubject = buildReplySubject(message.subject)

				this.loading = false

				if (!envelope.flags.seen) {
					return this.$store.dispatch('toggleEnvelopeSeen', envelope)
				}
			} catch (error) {
				logger.error('could not load message ', { threadId, error })
				if (error.isError) {
					this.errorMessage = t('mail', 'Could not load your message')
					this.error = error
					this.loading = false
				}
			}
		},
		replyMessage() {
			this.$router.push({
				name: 'message',
				params: {
					mailboxId: this.$route.params.mailboxId,
					threadId: 'reply',
					filter: this.$route.params.filter ? this.$route.params.filter : undefined,
				},
				query: {
					messageId: this.$route.params.threadId,
				},
			})
		},
		replyAll() {
			this.$router.push({
				name: 'message',
				params: {
					mailboxId: this.$route.params.mailboxId,
					threadId: 'replyAll',
					filter: this.$route.params.filter ? this.$route.params.filter : undefined,
				},
				query: {
					messageId: this.$route.params.threadId,
				},
			})
		},
		forwardMessage() {
			this.$router.push({
				name: 'message',
				params: {
					mailboxId: this.$route.params.mailboxId,
					threadId: 'new',
					filter: this.$route.params.filter ? this.$route.params.filter : undefined,
				},
				query: {
					messageId: this.$route.params.threadId,
				},
			})
		},
		onToggleSeen() {
			this.$store.dispatch('toggleEnvelopeSeen', this.envelope)
		},
		onToggleJunk() {
			this.$store.dispatch('toggleEnvelopeJunk', this.envelope)
		},
		onDelete() {
			this.$emit('delete', this.envelope.databaseId)
			this.$store.dispatch('deleteMessage', {
				id: this.envelope.databaseId,
			})
		},
		async onShowSource() {
			this.sourceLoading = true

			try {
				const resp = await axios.get(
					generateUrl('/apps/mail/api/messages/{id}/source', {
						id: this.envelope.databaseId,
					})
				)

				this.rawMessage = resp.data.source
				this.showSource = true
			} finally {
				this.sourceLoading = false
			}
		},
		onCloseSource() {
			this.showSource = false
		},
		onToggleImportant() {
			this.$store.dispatch('toggleEnvelopeImportant', this.envelope)
		},
		onToggleFlagged() {
			this.$store.dispatch('toggleEnvelopeFlagged', this.envelope)
		},
	},
}
</script>

<style lang="scss">
#mail-message {
	flex-grow: 1;
}

.mail-message-body {
	flex: 1;
	margin-bottom: 10px;
	position: relative;
}

#mail-message-header {
	display: flex;
	flex-direction: row;
	justify-content: space-between;
	align-items: center;
	padding: 30px 0;
	// somehow ios doesn't care about this !important rule
	// so we have to manually set left/right padding to chidren
	// for 100% to be used
	box-sizing: content-box !important;
	height: 44px;
	width: 100%;
}

#mail-message-header-fields {
	// initial width
	width: 0;
	padding-left: 38px;
	// grow and try to fill 100%
	flex: 1 1 auto;
	h2,
	p {
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
		padding-bottom: 7px;
		margin-bottom: 0;
	}

	.transparency {
		opacity: 0.6;
		a {
			font-weight: bold;
		}
	}
}

.v-popover > .trigger > .action-item {
	border-radius: 22px;
	background-color: var(--color-background-darker);
}

.attachment-popover {
	position: sticky;
	bottom: 12px;
	text-align: center;
}

.tooltip-inner {
	text-align: left;
}

#mail-content {
	margin: 10px 38px 50px 38px;

	.mail-message-body-html & {
		margin-bottom: -44px; // accounting for the sticky attachment button
	}
}

#mail-content iframe {
	width: 100%;
}

#show-images-text {
	display: none;
}

#mail-content a,
.mail-signature a {
	color: #07d;
	border-bottom: 1px dotted #07d;
	text-decoration: none;
	word-wrap: break-word;
}

#mail-message-actions {
	display: flex;
	flex-direction: row;
	justify-content: flex-end;
	margin-left: 10px;
	margin-right: 22px;
	height: 44px;
}

.icon-reply-white,
.icon-reply-all-white {
	height: 44px;
	min-width: 44px;
	margin: 0;
	padding: 9px 18px 10px 32px;
}

/* Show action button label and move icon to the left
   on screens larger than 600px */
@media only screen and (max-width: 600px) {
	.action-label {
		display: none;
	}
}
@media only screen and (min-width: 600px) {
	.icon-reply-white,
	.icon-reply-all-white {
		background-position: 12px center;
	}
}

#mail-message-actions-menu {
	margin-left: 4px;
}

.modal-container {
	overflow-y: scroll !important;
}

@media print {
	#mail-message-header-fields {
		position: relative;
	}

	#header,
	.app-navigation,
	#reply-composer,
	#forward-button,
	#mail-message-has-blocked-content,
	.app-content-list,
	.message-composer,
	.mail-message-attachments {
		display: none !important;
	}
	.app-content {
		margin-left: 0 !important;
	}
	.mail-message-body {
		margin-bottom: 0 !important;
	}
}

.message-source {
	font-family: monospace;
	white-space: pre-wrap;
	user-select: text;
}
.app-content-list-item-star.icon-starred {
	display: none;
}
</style>
