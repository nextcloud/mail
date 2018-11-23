<template>
	<router-link :to="newMessageRoute"
				 exact
				 v-tooltip.bottom="label">{{ label }}
	</router-link>
</template>

<script>
	export default {
		name: "Address",
		props: [
			'email',
			'label',
		],
		computed: {
			newMessageRoute () {
				return {
					name: 'message',
					params: {
						accountId: this.$route.params.accountId,
						folderId: this.$route.params.folderId,
						messageUid: 'new'
					}, query: {
						to: this.email
					}
				}
			}
		}
	}
</script>
