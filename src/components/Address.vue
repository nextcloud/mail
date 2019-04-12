<template>
	<router-link v-tooltip.bottom="email" :to="newMessageRoute" exact>{{ label }} </router-link>
</template>

<script>
export default {
	name: 'Address',
	props: {
		email: {
			type: String,
			required: true,
		},
		label: {
			type: String,
			required: true,
		},
	},
	computed: {
		newMessageRoute() {
			return {
				name: 'message',
				params: {
					accountId: this.$route.params.accountId,
					folderId: this.$route.params.folderId,
					messageUid: 'new',
				},
				query: {
					to: this.email,
				},
			}
		},
	},
}
</script>
