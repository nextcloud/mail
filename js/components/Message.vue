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
								 :url="htmlUrl"/>
				<MessagePlainTextBody v-else
									  :body="message.body"
									  :signature="message.signature"/>
				<div class="mail-message-attachments"></div>
				<div id="reply-composer"></div>
				<input type="button" id="forward-button" value="Forward">
			</div>
			<Composer :send="sendReply"
					  :draft="saveReplyDraft"/>
		</template>
	</div>
</template>

<script>
	import AddressList from "./AddressList"
	import Composer from "./Composer"
	import MessageHTMLBody from "./MessageHTMLBody"
	import MessagePlainTextBody from "./MessagePlainTextBody"
	import Loading from "./Loading"

	export default {
		name: "Message",
		components: {
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
			};
		},
		computed: {
			htmlUrl () {
				return OC.generateUrl('/apps/mail/api/accounts/{accountId}/folders/{folderId}/messages/{id}/html', {
					accountId: this.$route.params.accountId,
					folderId: this.$route.params.folderId,
					id: this.$route.params.messageId
				})
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

				const accountId = this.$route.params.accountId
				const folderId = this.$route.params.folderId
				const id = this.$route.params.messageId

				this.$store.dispatch(
					'fetchMessage', {
						accountId,
						folderId,
						id
					}).then(message => {
					this.message = message
					this.loading = false
				}).then(() => {
					// TODO: add timeout so that message isn't flagged when only viewed
					// for a few seconds
					if (accountId !== this.$route.params.accountId
						|| folderId !== this.$route.params.folderId
						|| id !== this.$route.params.messageId) {
						console.debug('User navigated away, loaded message won\'t be flagged as seen')
						return
					}

					if (!this.$store.getters.getEnvelope(accountId, folderId, id).flags.unseen) {
						// Already seen -> no change necessary
						return
					}

					return this.$store.dispatch(
						'toggleEnvelopeSeen', {
							accountId,
							folderId,
							id
						})
				}).catch(console.error.bind(this))
			},
			sendReply () {
				console.log('todo: sending reply')
				return new Promise((res, _) => {
					setTimeout(() => res, 1000);
				})
			},
			saveReplyDraft () {
				console.log('todo: saving reply draft')
				return new Promise((res, _) => {
					setTimeout(() => res, 1000)
				})
			}
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
