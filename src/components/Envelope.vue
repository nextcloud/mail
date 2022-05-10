<template>
	<ListItem
		v-draggable-envelope="{
			accountId: data.accountId ? data.accountId : mailbox.accountId,
			mailboxId: data.mailboxId,
			envelopeId: data.databaseId,
			draggableLabel,
			selectedEnvelopes,
		}"
		class="list-item-style"
		:class="{seen: data.flags.seen, draft, selected: selected}"
		:to="link"
		:data-envelope-id="data.databaseId"
		:title="addresses"
		:details="formatted()"
		@click="onClick">
		<template #icon>
			<div
				v-if="mailbox.isUnified && hasMultipleAccounts"
				class="mail-message-account-color"
				:style="{'background-color': accountColor}" />
			<div
				v-if="data.flags.flagged"
				class="app-content-list-item-star icon-starred"
				:data-starred="data.flags.flagged ? 'true' : 'false'"
				@click.prevent="onToggleFlagged" />
			<div
				v-if="isImportant"
				class="app-content-list-item-star svg icon-important"
				:data-starred="isImportant ? 'true' : 'false'"
				@click.prevent="onToggleImportant"
				v-html="importantSvg" />
			<div
				v-if="data.flags.$junk"
				class="app-content-list-item-star icon-junk"
				:data-starred="data.flags.$junk ? 'true' : 'false'"
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
		</template>
		<template #subtitle>
			<span v-if="data.flags.answered" class="icon-reply" />
			<span v-if="data.flags.hasAttachments === true" class="icon-public icon-attachment" />
			<span v-if="draft" class="draft">
				<em>{{ t('mail', 'Draft: ') }}</em>
			</span>
			{{ data.subject }}
		</template>
		<template #actions>
			<EnvelopePrimaryActions v-if="!moreActionsOpen">
				<ActionButton icon="icon-starred"
					class="action--primary"
					:close-after-click="true"
					@click.prevent="onToggleFlagged">
					{{
						data.flags.flagged ? t('mail', 'Unfavorite') : t('mail', 'Favorite')
					}}
				</ActionButton>
				<ActionButton icon="icon-mail"
					class="action--primary"
					:close-after-click="true"
					@click.prevent="onToggleSeen">
					{{
						data.flags.seen ? t('mail', 'Unread') : t('mail', 'Read')
					}}
				</ActionButton>
				<ActionButton icon="icon-important"
					class="action--primary"
					:close-after-click="true"
					@click.prevent="onToggleImportant">
					{{
						isImportant ? t('mail', 'Unimportant') : t('mail', 'Important')
					}}
				</ActionButton>
			</EnvelopePrimaryActions>
			<template v-if="!moreActionsOpen">
				<ActionButton icon="icon-junk"
					:close-after-click="true"
					@click.prevent="onToggleJunk">
					{{
						data.flags.$junk ? t('mail', 'Mark not spam') : t('mail', 'Mark as spam')
					}}
				</ActionButton>
				<ActionButton icon="icon-checkmark"
					:close-after-click="true"
					@click.prevent="toggleSelected">
					{{
						selected ? t('mail', 'Unselect') : t('mail', 'Select')
					}}
				</ActionButton>
				<ActionButton
					icon="icon-tag"
					:close-after-click="true"
					@click.prevent="onOpenTagModal">
					{{ t('mail', 'Edit tags') }}
				</ActionButton>
				<ActionButton icon="icon-external"
					:close-after-click="true"
					@click.prevent="onOpenMoveModal">
					{{ t('mail', 'Move thread') }}
				</ActionButton>
				<ActionButton icon="icon-more"
					:close-after-click="false"
					@click="moreActionsOpen=true">
					{{ t('mail', 'More actions') }}
				</ActionButton>
				<ActionButton icon="icon-delete"
					:close-after-click="true"
					@click.prevent="onDelete">
					{{ t('mail', 'Delete thread') }}
				</ActionButton>
			</template>
			<template v-if="moreActionsOpen">
				<ActionButton :close-after-click="false"
					@click="moreActionsOpen=false">
					<template #icon>
						<ChevronLeft
							:title="t('mail', 'More actions')"
							:size="20" />
						{{ t('mail', 'More actions') }}
					</template>
				</ActionButton>
				<ActionButton icon="icon-add"
					:close-after-click="true"
					@click.prevent="onOpenEditAsNew">
					{{ t('mail', 'Edit as new message') }}
				</ActionButton>
				<ActionButton icon="icon-calendar-dark"
					:close-after-click="true"
					@click.prevent="showEventModal = true">
					{{ t('mail', 'Create event') }}
				</ActionButton>
			</template>
		</template>
		<template #extra>
			<div
				v-if="mailbox.isUnified && hasMultipleAccounts"
				class="mail-message-account-color"
				:style="{'background-color': accountColor}" />
			<div v-for="tag in tags"
				:key="tag.id"
				class="tag-group">
				<div class="tag-group__bg"
					:style="{'background-color': tag.color}" />
				<span class="tag-group__label"
					:style="{color: tag.color}">{{ tag.displayName }} </span>
			</div>
			<MoveModal v-if="showMoveModal"
				:account="account"
				:envelopes="[data]"
				:move-thread="true"
				@move="onMove"
				@close="onCloseMoveModal" />
			<EventModal v-if="showEventModal"
				:envelope="data"
				@close="showEventModal = false" />
			<TagModal
				v-if="showTagModal"
				:account="account"
				:envelope="data"
				@close="onCloseTagModal" />
		</template>
	</ListItem>
</template>
<script>
import ListItem from '@nextcloud/vue/dist/Components/ListItem'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import Avatar from './Avatar'
import { calculateAccountColor } from '../util/AccountColor'
import ChevronLeft from 'vue-material-design-icons/ChevronLeft'
import moment from '@nextcloud/moment'
import importantSvg from '../../img/important.svg'
import { DraggableEnvelopeDirective } from '../directives/drag-and-drop/draggable-envelope'
import { buildRecipients as buildReplyRecipients } from '../ReplyBuilder'
import { showError } from '@nextcloud/dialogs'
import NoTrashMailboxConfiguredError
	from '../errors/NoTrashMailboxConfiguredError'
import logger from '../logger'
import { matchError } from '../errors/match'
import MoveModal from './MoveModal'
import TagModal from './TagModal'
import EventModal from './EventModal'
import EnvelopePrimaryActions from './EnvelopePrimaryActions'

export default {
	name: 'Envelope',
	components: {
		EnvelopePrimaryActions,
		EventModal,
		ListItem,
		Avatar,
		ActionButton,
		ChevronLeft,
		MoveModal,
		TagModal,
	},
	directives: {
		draggableEnvelope: DraggableEnvelopeDirective,
	},
	props: {
		withReply: {
			// "Reply" action should only appear in envelopes from the envelope list
			// (Because in thread envelopes, this action is already set as primary button of this menu)
			type: Boolean,
			default: true,
		},
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
		selectedEnvelopes: {
			type: Array,
			required: false,
			default: () => [],
		},
		hasMultipleAccounts: {
			type: Boolean,
			default: false,
		},
	},
	data() {
		return {
			importantSvg,
			showMoveModal: false,
			showEventModal: false,
			showTagModal: false,
			moreActionsOpen: false,
		}
	},
	computed: {
		hasMultipleRecipients() {
			if (!this.account) {
				console.error('account is undefined', {
					accountId: this.data.accountId,
				})
			}
			const recipients = buildReplyRecipients(this.envelope, {
				label: this.account.name,
				email: this.account.emailAddress,
			})
			return recipients.to.concat(recipients.cc).length > 1
		},
		accountColor() {
			const account = this.$store.getters.getAccount(this.data.accountId)
			return calculateAccountColor(account?.emailAddress ?? '')
		},
		draft() {
			return this.data.flags.draft
		},
		account() {
			const accountId = this.data.accountId
			return this.$store.getters.getAccount(accountId)
		},
		link() {
			if (this.draft) {
				return undefined
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
			return this.data.from[0]?.label ?? this.data.from[0]?.email ?? '?'
		},
		avatarEmail() {
			// Show first recipients' avatar in a sent mailbox (or undefined when sent to Bcc only)
			if (this.mailbox.specialRole === 'sent') {
				const recipients = [this.data.to, this.data.cc].flat().map(function(recipient) {
					return recipient.email
				})
				return recipients.length > 0 ? recipients[0] : ''
			}

			// Show sender avatar in other mailbox types
			if (this.data.from.length > 0) {
				return this.data.from[0].email
			} else {
				return ''
			}
		},
		isImportant() {
			return this.$store.getters
				.getEnvelopeTags(this.data.databaseId)
				.some((tag) => tag.imapLabel === '$label1')
		},
		tags() {
			return this.$store.getters.getEnvelopeTags(this.data.databaseId).filter((tag) => tag.imapLabel && tag.imapLabel !== '$label1')
		},
		draggableLabel() {
			let label = this.data.subject
			const sender = this.data.from[0]?.label ?? this.data.from[0]?.email
			if (sender) {
				label += ` (${sender})`
			}
			return label
		},
	},
	methods: {
		setSelected(value) {
			if (this.selected !== value) {
				this.$emit('update:selected', value)
			}
		},
		formatted() {
			return moment.unix(this.data.dateInt).fromNow()
		},
		unselect() {
			if (this.selected) {
				this.$emit('updated:selected', false)
			}
		},
		toggleSelected() {
			this.$emit('update:selected', !this.selected)
		},
		onClick() {
			if (this.draft) {
				this.$store.dispatch('showMessageComposer', {
					data: {
						...this.data,
						draftId: this.data.databaseId,
					},
					templateMessageId: this.data.databaseId,
				})
			}
		},
		onSelectMultiple() {
			this.$emit('select-multiple')
		},
		onToggleImportant() {
			this.$store.dispatch('toggleEnvelopeImportant', this.data)
		},
		onToggleFlagged() {
			this.$store.dispatch('toggleEnvelopeFlagged', this.data)
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
			this.$emit('delete', this.data.databaseId)

			try {
				await this.$store.dispatch('deleteThread', {
					envelope: this.data,
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
		async onOpenEditAsNew() {
			await this.$store.dispatch('showMessageComposer', {
				templateMessageId: this.data.databaseId,
				data: this.data,
			})
		},
		onOpenMoveModal() {
			this.showMoveModal = true
		},
		onOpenEventModal() {
			this.showEventModal = true
		},
		onMove() {
			this.$emit('move')
		},
		onCloseMoveModal() {
			this.showMoveModal = false
		},
		onOpenTagModal() {
			this.showTagModal = true
		},
		onCloseTagModal() {
			this.showTagModal = false
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
	.list-item:hover &,
	.list-item:focus &,
	.list-item.active & {
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

.app-content-list-item-select-checkbox {
	display: inline-block;
	vertical-align: middle;
	position: absolute;
	left: 33px;
	top: 35px;
	z-index: 50; // same as icon-starred
}

.list-item-style:not(.seen) {
	font-weight: bold;
}
.list-item-style.selected {
	background-color: var(--color-background-dark);
}
.icon-junk {
	opacity: .2;
	display: flex;
	top: 6px;
	left: 34px;
	background-size: 16px;
	height: 20px;
	width: 20px;
	margin: 0;
	padding: 0;
	position: absolute;
	z-index: 2;
}
list-item-style.draft .app-content-list-item-line-two {
	font-style: italic;
}
.list-item-style.active {
	background-color: var(--color-primary-light);
	border-radius: 16px;
}

.icon-reply,
.icon-attachment {
	display: inline-block;
	vertical-align: text-top;
}

.icon-reply {
	-ms-filter: 'progid:DXImageTransform.Microsoft.Alpha(Opacity=25)';
	opacity: 0.25;
}

.icon-attachment {
	-ms-filter: 'progid:DXImageTransform.Microsoft.Alpha(Opacity=25)';
	opacity: 0.25;
}

	// Fix layout of messages in list until we move to component

.app-content-list .list-item {
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
::v-deep .list-item__extra {
	margin-left: 41px !important;
}
.tag-group__label {
	margin: 0 7px;
	z-index: 2;
	font-size: calc(var(--default-font-size) * 0.8);
	font-weight: bold;
	padding-left: 2px;
	padding-right: 2px;
}
.tag-group__bg {
	position: absolute;
	width: 100%;
	height: 100%;
	top: 0;
	left: 0;
	opacity: 15%;
}
.tag-group {
	display: inline-block;
	border: 1px solid transparent;
	border-radius: var(--border-radius-pill);
	position: relative;
	margin: 0 1px;
	overflow: hidden;
	left: 4px;
}
::v-deep.list-item__wrapper {
	list-style: none;
}
.app-content-list-item-star.icon-starred {
	display: block;
}
::v-deep.icon-important.app-content-list-item-star {
	position: absolute;
	top: 7px;
	z-index: 1;
}
.app-content-list-item-star.icon-starred {
	display: inline-block;
	position: absolute;
	margin-bottom: 32px;
	margin-left: 28px;
	cursor: pointer;
	z-index: 1;
}
::v-deep .svg svg{
	height: 16px;
	width: 16px;
}
</style>
