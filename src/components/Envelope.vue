<template>
  <router-link
    class="app-content-list-item"
    :class="{ unseen: data.flags.unseen }"
    :to="{
      name: 'message',
      params: {
        accountId: this.$route.params.accountId,
        folderId: this.$route.params.folderId,
        messageUid: this.data.uid
      },
      exact: true}"
  >
    <div class="mail-message-account-color"
         v-if="showAccountColor"
         :style="{ 'background-color': accountColor }">
    </div>
    <div
      class="app-content-list-item-star icon-starred"
      :data-starred="data.flags.flagged ? 'true':'false'"
      @click="toggleFlagged"
    />
    <div class="app-content-list-item-icon">
      <Avatar :displayName="sender" />
    </div>
    <div
      class="app-content-list-item-line-one"
      :title="sender"
    >
      {{ sender }}
    </div>
    <div
      class="app-content-list-item-line-two"
      :title="data.subject"
    >
      <span
        v-if="data.flags.answered"
        class="icon-reply"
      />
      <span
        v-if="data.flags.hasAttachments"
        class="icon-public icon-attachment"
      />
      {{ data.subject }}
    </div>
    <div class="app-content-list-item-details date">
      <Moment :timestamp="data.dateInt" />
    </div>
    <Action class="app-content-list-item-menu"
            :actions="actions" />
  </router-link>
</template>

<script>
import { Action, Avatar, PopoverMenu, PopoverMenuItem } from 'nextcloud-vue'
import Moment from './Moment'

import {calculateAccountColor} from '../util/AccountColor'

export default {
	name: 'Envelope',
	components: {
		Action,
		Avatar,
		Moment,
	},
	props: {
		data: {
			type: Object,
            required: true,
        },
        showAccountColor: {
			type: Boolean,
            default: false,
        }
	},
	computed: {
		accountColor() {
			return calculateAccountColor(
				this.$store.getters.getAccount(this.data.accountId).name
            )
        },
		sender() {
			if (this.data.from.length === 0) {
				// No sender
				return '?'
			}

			const first = this.data.from[0]
			return first.label || first.email
		},
		actions() {
			return [
				{
					icon: 'icon-mail',
					text: t('mail', 'Seen'),
					action: () => {
						this.$store.dispatch('toggleEnvelopeSeen', this.data)
					},
				},
				{
					icon: 'icon-delete',
					text: t('mail', 'Delete'),
					action: () => {
						this.$store.dispatch('deleteMessage', this.data)
					}
				}
			]
		},
	},
	methods: {
		toggleFlagged(e) {
			// Don't navigate
			e.preventDefault()

			this.$store.dispatch('toggleEnvelopeFlagged', this.data)
		},
	},
}
</script>

<style scoped>
.mail-message-account-color {
	position: absolute;
	left: 0px;
	width: 2px;
	height: 69px;
	z-index: 1;
}

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
	-ms-filter: 'progid:DXImageTransform.Microsoft.Alpha(Opacity=50)';
	opacity: 0.5;
}

.icon-attachment {
	-ms-filter: 'progid:DXImageTransform.Microsoft.Alpha(Opacity=25)';
	opacity: 0.25;
}
</style>
