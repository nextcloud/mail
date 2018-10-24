<template>
	<transition-group name="list"
					  tag="div"
					  class="app-content-list"
					  v-infinite-scroll="loadMore"
					  infinite-scroll-disabled="loading"
					  infinite-scroll-distance="30"
					  v-scroll="onScroll">
		<div id="list-refreshing"
			 :key="'loading'"
			 class="icon-loading-small"
			 :class="{refreshing: refreshing}"/>
		<EmptyFolder v-if="envelopes.length === 0"/>
		<Envelope v-else
				  v-for="env in envelopes"
				  :key="env.id"
				  :data="env"/>
		<div id="load-more-mail-messages"
			 :key="'loadingMore'"
			 :class="{'icon-loading-small': loadingMore}"/>
	</transition-group>
</template>

<script>
	import infiniteScroll from 'vue-infinite-scroll'
	import vuescroll from 'vue-scroll'
	import Vue from 'vue'

	import EmptyFolder from './EmptyFolder'
	import Envelope from './Envelope'

	Vue.use(vuescroll, {throttle: 600})

	export default {
		name: "EnvelopeList",
		computed: {
			envelopes () {
				return this.$store.getters.getEnvelopes(
					this.$route.params.accountId,
					this.$route.params.folderId
				)
			}
		},
		components: {
			Envelope,
			EmptyFolder,
		},
		directives: {
			infiniteScroll,
		},
		data () {
			return {
				loadingMore: false,
				refreshing: false,
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
			},
			onScroll (e, p) {
				if (p.scrollTop === 0 && !this.refreshing) {
					this.refreshing = true

					this.$store.dispatch('syncEnvelopes', {
						accountId: this.$route.params.accountId,
						folderId: this.$route.params.folderId,
					}).catch(console.error.bind(this)).then(() => {
						this.refreshing = false
					})
				}
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

	#list-refreshing {
		overflow-y: hidden;
		min-height: 0;

		transition-property: all;
		transition-duration: .5s;
		transition-timing-function: ease-in;
	}

	#list-refreshing.refreshing {
		min-height: 32px;
	}

	.list-enter-active, .list-leave-active {
		transition: all 1s;
	}
	.list-enter, .list-leave-to {
		opacity: 0;
		height: 0px;
		transform: scaleY(0);
	}
</style>
