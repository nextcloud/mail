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
				<div id="mail-content">
					<MessageHTMLBody v-if="message.hasHtmlBody"/>
					<MessagePlainTextBody v-else
										  :body="message.body"
										  :signature="message.signature"/>
				</div>
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
				this.$store.dispatch(
					'fetchMessage', {
						accountId: this.$route.params.accountId,
						folderId: this.$route.params.folderId,
						folderId: this.$route.params.messageId,
					}).then(message => {
					this.message = message
					this.loading = false
				})
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
