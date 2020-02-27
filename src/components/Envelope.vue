<template>
	<router-link class="app-content-list-item" :class="{unseen: data.flags.unseen, draft}" :to="link">
		<div
			v-if="folder.isUnified"
			class="mail-message-account-color"
			:style="{'background-color': accountColor}"
		></div>
		<div
			v-if="data.flags.flagged"
			class="app-content-list-item-star icon-starred"
			:data-starred="data.flags.flagged ? 'true' : 'false'"
			@click.prevent="onToggleFlagged"
		></div>
		<div class="app-content-list-item-icon">
			<Avatar :display-name="addresses" :email="avatarEmail" />
		</div>
		<div class="app-content-list-item-line-one" :title="addresses">
			{{ addresses }}
		</div>
		<div class="app-content-list-item-line-two" :title="data.subject">
			<span v-if="data.flags.answered" class="icon-reply" />
			<span v-if="data.flags.hasAttachments === true" class="icon-public icon-attachment" />
			<span v-if="draft" class="draft">
				<em>{{ t('mail', 'Draft: ') }}</em>
			</span>
			{{ data.subject }}
		</div>
		<div class="app-content-list-item-details date">
			<Moment :timestamp="data.dateInt" />
		</div>
		<Actions class="app-content-list-item-menu" menu-align="right">
			<ActionButton icon="icon-starred" @click.prevent="onToggleFlagged">{{
				data.flags.flagged ? t('mail', 'Unfavorite') : t('mail', 'Favorite')
			}}</ActionButton>
			<ActionButton icon="icon-mail" @click.prevent="onToggleSeen">{{
				data.flags.unseen ? t('mail', 'Mark read') : t('mail', 'Mark unread')
			}}</ActionButton>
			<ActionButton icon="icon-delete" @click.prevent="onDelete">{{ t('mail', 'Delete') }}</ActionButton>
		</Actions>
	</router-link>
</template>

<script>
import Actions from '@nextcloud/vue/dist/Components/Actions'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import Moment from './Moment'

import Avatar from './Avatar'
import {calculateAccountColor} from '../util/AccountColor'

export default {
	name: 'Envelope',
	components: {
		Actions,
		ActionButton,
		Avatar,
		Moment,
	},
	props: {
		data: {
			type: Object,
			required: true,
		},
		folder: {
			type: Object,
			required: true,
		},
	},
	computed: {
		accountColor() {
			return calculateAccountColor(this.$store.getters.getAccount(this.data.accountId).emailAddress)
		},
		draft() {
			return this.data.flags.draft
		},
		link() {
			if (this.draft) {
				// TODO: does not work with a unified drafts folder
				//       the query should also contain the account and folder
				//       id for that to work
				return {
					name: 'message',
					params: {
						accountId: this.$route.params.accountId,
						folderId: this.$route.params.folderId,
						messageUid: 'new',
						draftUid: this.data.uid,
					},
					exact: true,
				}
			} else {
				return {
					name: 'message',
					params: {
						accountId: this.$route.params.accountId,
						folderId: this.$route.params.folderId,
						messageUid: this.data.uid,
					},
					exact: true,
				}
			}
		},
		addresses() {
			// Show recipients' label/address in a sent folder
			if (this.folder.specialRole === 'sent') {
				let recipients = [this.data.to, this.data.cc].flat().map(function(recipient) {
					return recipient.label ? recipient.label : recipient.email
				})
				return recipients.length > 0 ? recipients.join(', ') : t('mail', 'Blind copy recipients only')
			}
			// Show sender label/address in other folder types
			return this.data.from.length === 0 ? '?' : this.data.from[0].label || this.data.from[0].email
		},
		avatarEmail() {
			// Show first recipients' avatar in a sent folder (or undefined when sent to Bcc only)
			if (this.folder.specialRole === 'sent') {
				let recipients = [this.data.to, this.data.cc].flat().map(function(recipient) {
					return recipient.email
				})
				return recipients.length > 0 ? recipients[0] : undefined
			}

			// Show sender avatar in other folder types
			if (this.data.from.length > 0) {
				return this.data.from[0].email
			} else {
				return undefined
			}
		},
	},
	methods: {
		onToggleFlagged() {
			this.$store.dispatch('toggleEnvelopeFlagged', this.data)
		},
		onToggleSeen() {
			this.$store.dispatch('toggleEnvelopeSeen', this.data)
		},
		onDelete() {
			this.$emit('delete', {envelope: this.data})
			this.$store.dispatch('deleteMessage', this.data)
		},
	},
}
</script>

<style lang="scss" scoped>
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
.app-content-list-item.draft .app-content-list-item-line-two {
	font-style: italic;
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

// Fix layout of messages in list until we move to component

.app-content-list-item-line-two,
.app-content-list-item-menu {
	margin-top: -8px;
}

.app-content-list-item-menu {
	margin-right: -2px;
}
</style>
