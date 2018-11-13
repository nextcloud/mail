<template>
	<div class="app-content-details">
		<Composer :fromAccount="composerData.accountId"
				  :to="composerData.to"
				  :cc="composerData.cc"
				  :bcc="composerData.bcc"
				  :subject="composerData.subject"
				  :body="composerData.body"
				  :draft="saveDraft"
				  :send="sendMessage"/>
	</div>
</template>

<script>
	import _ from 'lodash'
	import {buildFowardSubject, buildReplyBody} from '../ReplyBuilder'

	import Composer from './Composer'
	import {saveDraft, sendMessage} from '../service/MessageService'

	export default {
		name: 'NewMessageDetail',
		components: {
			Composer
		},
		computed: {
			composerData () {
				if (!_.isUndefined(this.$route.query.uid)) {
					// Fowarded message

					const message = this.$store.getters.getMessageByUid(this.$route.query.uid)
					console.debug('forwaring message', message)

					return {
						to: [],
						cc: [],
						subject: buildFowardSubject(message.subject),
						body: buildReplyBody(
							message.bodyText,
							message.from[0],
							message.dateInt,
						)
					}
				} else {
					// New or mailto: message

					let accountId
					// Only preselect an account when we're not in a unified mailbox
					if (this.$route.params.accountId !== 0
						&& this.$route.params.accountId !== '0') {
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
			}
		},
		methods: {
			stringToRecipients (str) {
				if (_.isUndefined(str)) {
					return []
				}

				return [
					{
						label: str,
						email: str,
					}
				]
			},
			saveDraft (data) {
				return saveDraft(data.account, data)
					.then(({uid}) => uid)
			},
			sendMessage (data) {
				return sendMessage(data.account, data)
			}
		}
	}
</script>
