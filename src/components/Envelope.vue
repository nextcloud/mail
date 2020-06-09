<template>
	<router-link class="app-content-list-item" :class="{seen: data.flags.seen, draft, selected: selected}" :to="link">
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
		<div
			v-if="data.flags.important"
			class="app-content-list-item-star icon-important"
			:data-starred="data.flags.important ? 'true' : 'false'"
			@click.prevent="onToggleImportant"
			v-html="importantSvg"
		></div>
		<div
			v-if="data.flags.junk"
			class="app-content-list-item-star icon-junk"
			:data-starred="data.flags.junk ? 'true' : 'false'"
			@click.prevent="onToggleJunk"
		></div>
		<div class="app-content-list-item-icon">
			<Avatar :display-name="addresses" :email="avatarEmail" />
			<p v-if="selectMode" class="app-content-list-item-select-checkbox">
				<input :id="`select-checkbox-${data.uid}`" class="checkbox" type="checkbox" :checked="selected" />
				<label :for="`select-checkbox-${data.uid}`" @click.prevent="toggleSelected" />
			</p>
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
			<ActionButton icon="icon-important" @click.prevent="onToggleImportant">{{
				data.flags.important ? t('mail', 'Mark unimportant') : t('mail', 'Mark important')
			}}</ActionButton>
			<ActionButton icon="icon-mail" @click.prevent="onToggleSeen">{{
				data.flags.seen ? t('mail', 'Mark unread') : t('mail', 'Mark read')
			}}</ActionButton>
			<ActionButton icon="icon-junk" @click.prevent="onToggleJunk">{{
				data.flags.junk ? t('mail', 'Mark not spam') : t('mail', 'Mark as spam')
			}}</ActionButton>
			<ActionButton icon="icon-checkmark" :close-after-click="true" @click.prevent="onSelect">{{
				selected ? t('mail', 'Unselect') : t('mail', 'Select')
			}}</ActionButton>
			<ActionButton icon="icon-delete" @click.prevent="onDelete">{{ t('mail', 'Delete') }}</ActionButton>
		</Actions>
	</router-link>
</template>

<script>
import Actions from '@nextcloud/vue/dist/Components/Actions'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import Moment from './Moment'
import importantSvg from '../../img/important.svg'

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
		selectMode: {
			type: Boolean,
			default: false,
		},
		selected: {
			type: Boolean,
			default: false,
		},
	},
	data() {
		return {
			importantSvg,
		}
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
						filter: this.$route.params.filter ? this.$route.params.filter : undefined,
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
						filter: this.$route.params.filter ? this.$route.params.filter : undefined,
						messageUid: this.data.uid,
					},
					exact: true,
				}
			}
		},
		addresses() {
			// Show recipients' label/address in a sent folder
			if (this.folder.specialRole === 'sent') {
				let recipients = [this.data.to, this.data.cc].flat().map(function (recipient) {
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
				let recipients = [this.data.to, this.data.cc].flat().map(function (recipient) {
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
		onSelect() {
			this.$emit('update:selected', true)
		},
		toggleSelected() {
			this.$emit('update:selected', !this.selected)
		},
		onToggleFlagged() {
			this.$store.dispatch('toggleEnvelopeFlagged', this.data)
		},
		onToggleImportant() {
			this.$store.dispatch('toggleEnvelopeImportant', this.data)
		},
		onToggleSeen() {
			this.$store.dispatch('toggleEnvelopeSeen', this.data)
		},
		onToggleJunk() {
			this.$store.dispatch('toggleEnvelopeJunk', this.data)
		},
		onDelete() {
			this.$emit('delete')
			this.$store.dispatch('deleteMessage', {
				accountId: this.data.accountId,
				folderId: this.data.folderId,
				id: this.data.id,
			})
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

.icon-important {
	::v-deep path {
		fill: #ffcc00;
		stroke: var(--color-main-background);
	}
	.app-content-list-item:hover &,
	.app-content-list-item:focus &,
	.app-content-list-item.active & {
		::v-deep path {
			stroke: var(--color-background-dark);
		}
	}

	// In message list, but not the one in the action menu
	&.app-content-list-item-star {
		background-image: none;
		left: 7px;
		top: 13px;
		opacity: 1;

		&:hover,
		&:focus {
			opacity: 0.5;
		}
	}
}

.app-content-list-item .app-content-list-item-select-checkbox {
	display: inline-block;
	vertical-align: middle;
	position: absolute;
	left: 22px;
	top: 20px;
	z-index: 50; // same as icon-starred
}

.app-content-list-item:not(.seen) {
	font-weight: bold;
}
.app-content-list-item.selected {
	background-color: var(--color-background-dark);
	font-weight: bold;
}
.app-content-list-item-star.junk {
	background-image: var(--icon-junk-000);
	opacity: 1;
}
.app-content-list-item.draft .app-content-list-item-line-two {
	font-style: italic;
}
.app-content-list-item.active {
	background-color: var(--color-primary-light);
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

.app-content-list .app-content-list-item {
	padding-right: 0;

	.app-content-list-item-line-two {
		padding-right: 0;
		margin-top: -8px;
	}

	.app-content-list-item-menu {
		margin-right: -2px;
		margin-top: -8px;

		::v-deep .action-item__menu {
			right: 7px !important;

			.action-item__menu_arrow {
				right: 6px !important;
			}
		}
	}

	.app-content-list-item-details {
		padding-right: 7px;
	}
}
</style>
