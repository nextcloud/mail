<template>
	<div class="app-content-details">
		<Composer :to="fromUrl.to"
				  :cc="fromUrl.cc"
				  :bcc="fromUrl.bcc"
				  :subject="fromUrl.subject"
				  :body="fromUrl.body"
				  :draft="saveDraft"
				  :send="sendMessage"/>
	</div>
</template>

<script>
	import _ from 'lodash'

	import Composer from './Composer'
	import {saveDraft, sendMessage} from '../service/MessageService'

	export default {
		name: 'NewMessageDetail',
		components: {
			Composer
		},
		computed: {
			fromUrl() {
				return {
					to: this.stringToRecipients(this.$route.query.to),
					cc: this.stringToRecipients(this.$route.query.cc),
					subject: this.$route.query.subject || '',
					body: this.$route.query.body || '',
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
