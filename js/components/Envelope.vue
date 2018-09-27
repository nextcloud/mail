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
			 v-on:click="toggleFlagged"
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
			<span v-if="data.flags.hasAttachments"
				  class="icon-public icon-attachment"></span>
			{{data.subject}}
		</div>
		<div class="app-content-list-item-details date">
			<Moment :timestamp="data.dateInt * 1000"/>
			<div class="app-content-list-item-menu">
				<div class="icon-more"
					 v-on:click="togglePopoverMenu"/>
				<div class="popovermenu"
					 :class="{open: this.menuOpened}">
					<PopoverMenu :menu="popoverMenu" />
				</div>
			</div>
		</div>
	</router-link>
</template>

<script>
	import {PopoverMenu, PopoverMenuItem} from 'nextcloud-vue'

	import Avatar from './Avatar'
	import Moment from './Moment'

	export default {
		name: 'Envelope',
		components: {
			PopoverMenuItem,
			Avatar,
			Moment,
			PopoverMenu,
		},
		props: [
			'data',
		],
		data() {
			return {
				menuOpened: false
			}
		},
		computed: {
			sender () {
				if (this.data.from.length === 0) {
					// No sender
					return '?'
				}

				const first = this.data.from[0]
				return first.label || first.email
			},
			popoverMenu() {
				return [
					{
						input: 'checkbox',
						text: t('mail', 'Seen'),
						action: () => console.log('CHECK'),
					}
				]
			}
		},
		methods: {
			toggleFlagged (e) {
				// Don't navigate
				e.preventDefault()

				this.$store.dispatch('toggleEnvelopeFlagged', {
					accountId: this.$route.params.accountId,
					folderId: this.$route.params.folderId,
					id: this.data.id
				})
			},
			togglePopoverMenu (e) {
				this.menuOpened = !this.menuOpened
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
