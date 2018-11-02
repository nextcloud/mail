<template>
	<div class="app-content-details">
		<Composer :draft="saveDraft"
				  :send="sendMessage"/>
	</div>
</template>

<script>
	import Composer from './Composer'
	import {saveDraft, sendMessage} from '../service/MessageService'

	export default {
		name: 'NewMessageDetail',
		components: {
			Composer
		},
		methods: {
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
