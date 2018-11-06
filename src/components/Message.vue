<template>
	<div class="app-content-details">
		<Loading v-if="loading"/>
		<template v-else>
			<div id="mail-message-header" class="section">
				<h2 :title="message.subject">{{message.subject}}</h2>
				<p class="transparency">
					<AddressList :entries="message.from"/>
					to <!-- TODO: translate -->
					<AddressList :entries="message.to"/>
					<template v-if="message.cc.length">
						(cc <!-- TODO: translate -->
						<AddressList :entries="message.cc"/><!--
						-->)
					</template>
				</p>
			</div>
			<div class="mail-message-body">
				<MessageHTMLBody v-if="message.hasHtmlBody"
								 :url="htmlUrl"
								 @loaded="onHtmlBodyLoaded"/>
				<MessagePlainTextBody v-else
									  :body="message.body"
									  :signature="message.signature"/>
				<MessageAttachments :attachments="message.attachments" />
				<div id="reply-composer"></div>
				<input type="button" id="forward-button" value="Forward">
			</div>
			<Composer v-if="!message.hasHtmlBody || htmlBodyLoaded"
					  :fromAccount="message.accountId"
					  :to="replyRecipient.to"
					  :cc="replyRecipient.cc"
					  :subject="replySubject"
					  :body="replyBody"
					  :replyTo="replyTo"
					  :send="sendReply"
					  :draft="saveReplyDraft"/>
		</template>
	</div>
</template>

<script>
	import { generateUrl } from 'nextcloud-server/dist/router'

	import AddressList from './AddressList'
	import {
		buildReplyBody,
		buildRecipients as buildReplyRecipients,
		buildReplySubject,
	} from '../ReplyBuilder'
	import Composer from './Composer'
	import {htmlToText} from '../util/HtmlHelper'
	import MessageHTMLBody from './MessageHTMLBody'
	import MessagePlainTextBody from './MessagePlainTextBody'
	import Loading from './Loading'
	import MessageAttachments from './MessageAttachments'
	import {saveDraft, sendMessage} from '../service/MessageService'

	export default {
		name: 'Message',
		components: {
			MessageAttachments,
			Loading,
			AddressList,
			Composer,
			MessageHTMLBody,
			MessagePlainTextBody,
		},
		data () {
			return {
				loading: true,
				message: undefined,
				htmlBodyLoaded: false,
				replyRecipient: {},
				replySubject: '',
				replyBody: '',
			};
		},
		computed: {
			htmlUrl () {
				return generateUrl('/apps/mail/api/accounts/{accountId}/folders/{folderId}/messages/{id}/html', {
					accountId: this.message.accountId,
					folderId: this.message.folderId,
					id: this.message.id
				})
			},
			replyTo () {
				return {
					accountId: this.message.accountId,
					folderId: this.message.folderId,
					messageId: this.message.id,
				}
			}
		},
		created () {
			this.fetchMessage()
		},
		watch: {
			'$route' (to, from) {
				this.fetchMessage()
			}
		},
		methods: {
			fetchMessage () {
				this.loading = true
				this.message = undefined
				this.replyRecipient = {}
				this.replySubject = ''
				this.replyBody = ''
				this.htmlBodyLoaded = false

				const messageUid = this.$route.params.messageUid

				this.$store.dispatch(
					'fetchMessage', messageUid).then(message => {
					this.message = message

					this.replyRecipient = buildReplyRecipients(message, {}) // TODO: own address
					this.replySubject = buildReplySubject(message.subject)

					if (!message.hasHtmlBody) {
						this.setReplyText(message.body)
					}

					this.loading = false

					// TODO: add timeout so that message isn't flagged when only viewed
					// for a few seconds
					if (message.uid !== this.$route.params.messageUid) {
						console.debug('User navigated away, loaded message won\'t be flagged as seen')
						return
					}

					const envelope = this.$store.getters.getEnvelope(message.accountId, message.folderId, message.id);
					if (!envelope.flags.unseen) {
						// Already seen -> no change necessary
						return
					}

					return this.$store.dispatch('toggleEnvelopeSeen', envelope)
				}).catch(console.error.bind(this))
			},
			setReplyText (text) {
				this.replyBody = buildReplyBody(
					htmlToText(text),
					this.message.from[0],
					this.message.dateInt,
				)
			},
			onHtmlBodyLoaded (bodyString) {
				this.setReplyText(bodyString)
				this.htmlBodyLoaded = true
			},
			saveReplyDraft (data) {
				return saveDraft(data.account, data)
					.then(({uid}) => uid)
			},
			sendReply (data) {
				return sendMessage(data.account, data)
			},
		}
	}
</script>

<style>
	.mail-message-body {
		margin-bottom: 100px;
	}

	#mail-message-header h2,
	#mail-message-header p {
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
		padding-bottom: 7px;
		margin-bottom: 0;
	}

	#mail-content,
	.mail-message-attachments {
		margin: 10px 10px 50px 30px;
	}

	.mail-message-attachments {
		margin-top: 10px;
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

	#mail-message-header .transparency {
		color: rgba(0, 0, 0, .3) !important;
		opacity: 1;
	}

	#mail-message-header .transparency a {
		color: rgba(0, 0, 0, .5) !important;
	}
</style>
