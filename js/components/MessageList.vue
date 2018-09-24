<template>
	<div class="app-content-list"
		 v-infinite-scroll="loadMore"
		 infinite-scroll-disabled="loading"
		 infinite-scroll-distance="30">
		<EmptyFolder v-if="envelopes.length === 0"/>
		<MessageListItem v-else
						 v-for="env in envelopes"
						 :key="env.id"
						 :data="env"/>
		<div id="load-more-mail-messages"
			 :class="{'icon-loading-small': loadingMore}"/>
	</div>
</template>

<script>
	import infiniteScroll from 'vue-infinite-scroll';

	import EmptyFolder from './EmptyFolder'
	import MessageListItem from './MessageListItem'

	export default {
		name: "MessageList",
		computed: {
			envelopes () {
				return this.$store.getters.getEnvelopes(
					this.$route.params.accountId,
					this.$route.params.folderId
				)
			}
		},
		components: {
			MessageListItem,
			EmptyFolder,
		},
		directives: {
			infiniteScroll,
		},
		data () {
			return {
				loadingMore: false,
			}
		},
		methods: {
			loadMore () {
				this.loadingMore = true

				this.$store.dispatch('fetchNextEnvelopePage', {
					accountId: this.$route.params.accountId,
					folderId: this.$route.params.folderId,
				}).catch(console.error.bind(this)).then(() => {
					this.loadingMore = false
				})
			}
		}
	}
</script>

<style scoped>
	#load-more-mail-messages {
		margin: 10px auto;
		padding: 10px;
		margin-top: 50px;
		margin-bottom: 200px;
	}

	/* TODO: put this in core icons.css as general rule for buttons with icons */
	#load-more-mail-messages.icon-loading-small {
		padding-left: 32px;
		background-position: 9px center;
	}
</style>
