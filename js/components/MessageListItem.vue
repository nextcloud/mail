<template>
	<router-link class="app-content-list-item" :to="{
			name: 'message',
			params: {
							accountId: this.$route.params.accountId,
							folderId: this.$route.params.folderId,
							messageId: this.data.id
			},
			exact: true}">
		<div class="app-content-list-item-icon">
			<Avatar :label="data.from"/>
		</div>
		<div class="app-content-list-item-line-one"
			 :title="data.from">
			{{data.from}}
		</div>
		<div class="app-content-list-item-line-two"
			 :title="data.subject">
			{{data.subject}}
		</div>
		<div class="app-content-list-item-details date">
			<Moment timestamp="1536048354000"/>
		</div>
	</router-link>
</template>

<script>
	import Avatar from "./Avatar";
	import Moment from "./Moment";

	export default {
		name: "MessageListItem",
		components: {
			Avatar,
			Moment
		},
		props: [
			'data',
		],
		methods: {
			elem () {
				return {
					is: 'router-link',
					tag: 'div',
					to: {
						name: 'message',

					}
				}
			}
		}
	}
</script>
