<template>
	<router-link class="app-content-list-item" :class="{seen: data.flags.seen, draft, selected: selected}" :to="link">
		<div
			v-if="mailbox.isUnified"
			class="mail-message-account-color"
			:style="{'background-color': accountColor}" />
		<div
			v-if="data.flags.flagged"
			class="app-content-list-item-star icon-starred"
			:data-starred="data.flags.flagged ? 'true' : 'false'"
			@click.prevent="onToggleFlagged" />
		<div
			v-if="data.flags.important"
			class="app-content-list-item-star icon-important"
			:data-starred="data.flags.important ? 'true' : 'false'"
			@click.prevent="onToggleImportant"
			v-html="importantSvg" />
		<div
			v-if="data.flags.junk"
			class="app-content-list-item-star icon-junk"
			:data-starred="data.flags.junk ? 'true' : 'false'"
			@click.prevent="onToggleJunk" />
		<div class="app-content-list-item-icon">
			<Avatar :display-name="addresses" :email="avatarEmail" />
			<p v-if="selectMode" class="app-content-list-item-select-checkbox">
				<input :id="`select-checkbox-${data.uid}`"
					class="checkbox"
					type="checkbox"
					:checked="selected">
				<label
					:for="`select-checkbox-${data.uid}`"
					@click.exact.prevent="toggleSelected"
					@click.shift.prevent="onSelectMultiple" />
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
		<Actions class="app-content-list-item-menu"
			menu-align="right"
			event=""
			@click.native.prevent>
			<ActionButton icon="icon-important"
				:close-after-click="true"
				@click.prevent="onToggleImportant">
				{{
					data.flags.important ? t('mail', 'Mark unimportant') : t('mail', 'Mark important')
				}}
			</ActionButton>
			<ActionButton icon="icon-starred"
				:close-after-click="true"
				@click.prevent="onToggleFlagged">
				{{
					data.flags.flagged ? t('mail', 'Mark unfavorite') : t('mail', 'Mark favorite')
				}}
			</ActionButton>
			<ActionButton icon="icon-mail"
				:close-after-click="true"
				@click.prevent="onToggleSeen">
				{{
					data.flags.seen ? t('mail', 'Mark unread') : t('mail', 'Mark read')
				}}
			</ActionButton>
			<ActionButton icon="icon-junk"
				:close-after-click="true"
				@click.prevent="onToggleJunk">
				{{
					data.flags.junk ? t('mail', 'Mark not spam') : t('mail', 'Mark as spam')
				}}
			</ActionButton>
			<ActionButton icon="icon-checkmark"
				:close-after-click="true"
				@click.prevent="toggleSelected">
				{{
					selected ? t('mail', 'Unselect') : t('mail', 'Select')
				}}
			</ActionButton>
			<ActionButton icon="icon-external"
				:close-after-click="true"
				@click.prevent="onOpenMoveModal">
				{{ t('mail', 'Move') }}
			</ActionButton>
			<ActionRouter icon="icon-add"
				:to="{
					name: 'message',
					params: {
						mailboxId: $route.params.mailboxId,
						threadId: 'asNew',
						filter: $route.params.filter,
					},
					query: {
						messageId: data.databaseId,
					},
				}">
				{{ t('mail', 'Edit as new message') }}
			</ActionRouter>
			<ActionButton icon="icon-delete"
				:close-after-click="true"
				@click.prevent="onDelete">
				{{ t('mail', 'Delete') }}
			</ActionButton>
		</Actions>
		<MoveModal v-if="showMoveModal"
			:account="account"
			:envelopes="[data]"
			@close="onCloseMoveModal" />
	</router-link>
</template>

<script>
import Actions from '@nextcloud/vue/dist/Components/Actions'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import ActionRouter from '@nextcloud/vue/dist/Components/ActionRouter'
import Moment from './Moment'
import MoveModal from './MoveModal'
import importantSvg from '../../img/important.svg'
import { showError } from '@nextcloud/dialogs'

import Avatar from './Avatar'
import { calculateAccountColor } from '../util/AccountColor'
import { matchError } from '../errors/match'
import NoTrashMailboxConfiguredError
	from '../errors/NoTrashMailboxConfiguredError'
import logger from '../logger'

export default {
	name: 'Envelope',
	components: {
		Actions,
		ActionButton,
		ActionRouter,
		Avatar,
		Moment,
		MoveModal,
	},
	props: {
		data: {
			type: Object,
			required: true,
		},
		mailbox: {
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
			showMoveModal: false,
		}
	},
	computed: {
		account() {
			return this.$store.getters.getAccount(this.data.accountId)
		},
		accountColor() {
			return calculateAccountColor(this.account?.emailAddress ?? '')
		},
		draft() {
			return this.data.flags.draft
		},
		link() {
			if (this.draft) {
				// TODO: does not work with a unified drafts mailbox
				//       the query should also contain the account and mailbox
				//       id for that to work
				return {
					name: 'message',
					params: {
						mailboxId: this.$route.params.mailboxId,
						filter: this.$route.params.filter ? this.$route.params.filter : undefined,
						threadId: 'new',
						draftId: this.data.databaseId,
					},
					exact: true,
				}
			} else {
				return {
					name: 'message',
					params: {
						mailboxId: this.$route.params.mailboxId,
						filter: this.$route.params.filter ? this.$route.params.filter : undefined,
						threadId: this.data.databaseId,
					},
					exact: true,
				}
			}
		},
		addresses() {
			// Show recipients' label/address in a sent mailbox
			if (this.mailbox.specialRole === 'sent') {
				const recipients = [this.data.to, this.data.cc].flat().map(function(recipient) {
					return recipient.label ? recipient.label : recipient.email
				})
				return recipients.length > 0 ? recipients.join(', ') : t('mail', 'Blind copy recipients only')
			}
			// Show sender label/address in other mailbox types
			return this.data.from.length === 0 ? '?' : this.data.from[0].label || this.data.from[0].email
		},
		avatarEmail() {
			// Show first recipients' avatar in a sent mailbox (or undefined when sent to Bcc only)
			if (this.mailbox.specialRole === 'sent') {
				const recipients = [this.data.to, this.data.cc].flat().map(function(recipient) {
					return recipient.email
				})
				return recipients.length > 0 ? recipients[0] : undefined
			}

			// Show sender avatar in other mailbox types
			if (this.data.from.length > 0) {
				return this.data.from[0].email
			} else {
				return undefined
			}
		},
	},
	methods: {
		setSelected(value) {
			if (this.selected !== value) {
				this.$emit('update:selected', value)
			}
		},
		toggleSelected() {
			this.$emit('update:selected', !this.selected)
		},
		onSelectMultiple() {
			this.$emit('select-multiple')
		},
		onToggleFlagged() {
			this.$store.dispatch('toggleEnvelopeFlagged', this.data)
		},
		onToggleImportant() {
			this.$store.dispatch('toggleEnvelopeImportant', this.data)
		},
		onToggleSeen() {
			this.$store.dispatch('toggleEnvelopeSeen', { envelope: this.data })
		},
		onToggleJunk() {
			this.$store.dispatch('toggleEnvelopeJunk', this.data)
		},
		async onDelete() {
			// Remove from selection first
			this.setSelected(false)

			// Delete
			this.$emit('delete')
			try {
				await this.$store.dispatch('deleteMessage', {
					id: this.data.databaseId,
				})
			} catch (error) {
				showError(await matchError(error, {
					[NoTrashMailboxConfiguredError.getName()]() {
						return t('mail', 'No trash mailbox configured')
					},
					default(error) {
						logger.error('could not delete message', error)
						return t('mail', 'Could not delete message')
					},
				}))
			}
		},
		onOpenMoveModal() {
			this.setSelected(false)
			this.showMoveModal = true
		},
		onCloseMoveModal() {
			this.showMoveModal = false
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
