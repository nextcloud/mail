<template>
	<router-link :class="{
			'app-content-list-item': true,
			'unseen': data.flags.unseen
		}" :to="{
			name: 'message',
			params: {
				accountId: this.$route.params.accountId,
				folderId: this.$route.params.folderId,
				messageId: this.data.id
			},
			exact: true}">
		<div class="app-content-list-item-star icon-starred"
			 :data-starred="data.flags.flagged ? 'true':'false'"></div>
		<div class="app-content-list-item-icon">
			<Avatar :label="sender"/>
		</div>
		<div class="app-content-list-item-line-one"
			 :title="sender">
			{{sender}}
		</div>
		<div class="app-content-list-item-line-two"
			 :title="data.subject">
			<span v-if="data.flags.answered" class="icon-reply"></span>
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
		computed: {
			sender () {
				if (this.data.from.length === 0) {
					// No sender
					return '?'
				}

				const first = this.data.from[0]
				return first.label || first.email
			}
		}
	}
</script>

<style scoped>
	.app-content-list-item.unseen {
		font-weight: bold;
	}

	.icon-reply,
	.icon-attachment {
		display: inline-block;
		vertical-align: text-top;
	}

	.icon-reply {
		background-image: url('../../img/reply.svg');
		-ms-filter: "progid:DXImageTransform.Microsoft.Alpha(Opacity=50)";
		opacity: .5;
	}

	.icon-attachment {
		-ms-filter: "progid:DXImageTransform.Microsoft.Alpha(Opacity=25)";
		opacity: .25;
	}
</style>
