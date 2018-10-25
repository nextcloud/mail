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
			saveDraft (data) {
				return new Promise((res, rej) => setTimeout(res, 1200))
					.then(() => console.log('draft saved', data))
			},
			sendMessage (data) {
				return new Promise((res, rej) => setTimeout(res, 1200))
					.then(() => console.log('message sent', data))
			}
		}
	}
</script>
