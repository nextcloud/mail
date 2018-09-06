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
				return Promise.resolve();
			},
			sendMessage () {
				console.info('sending message');
				console.info('sending message');
				return Promise.resolve();
			}
		}
	}
</script>
