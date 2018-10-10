<template>
	<div class="app-content-details">
		<Composer :draft="saveDraft"
				  :send="sendMessage"/>
	</div>
</template>

<script>
	import Composer from "./Composer";

	export default {
		name: "NewMessageDetail",
		components: {
			Composer
		},
		methods: {
			saveDraft () {
				console.info('saving new draft');
				return new Promise((res, rej) => setTimeout(res, 2000))
			},
			sendMessage () {
				console.info('sending message');
				return new Promise((res, rej) => setTimeout(res, 2000))
			}
		}
	}
</script>
