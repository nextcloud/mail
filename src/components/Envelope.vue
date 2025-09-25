<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<EnvelopeSkeleton v-draggable-envelope="{
			accountId: data.accountId ? data.accountId : mailbox.accountId,
			mailboxId: data.mailboxId,
			databaseId: data.databaseId,
			draggableLabel,
			selectedEnvelopes,
			isDraggable,
		}"
		class="list-item-style envelope"
		:class="{seen: data.flags.seen, draft, selected: selected}"
		:to="link"
		:exact="true"
		:data-envelope-id="data.databaseId"
		:name="addresses"
		:details="formatted()"
		:one-line="oneLineLayout"
		:is-read="showImportantIconVariant"
		:is-important="isImportant"
		@click.exact="onClick"
		@click.ctrl.exact.prevent="toggleSelected"
		@click.shift.exact.prevent="onSelectMultiple"
		@delete="onDelete"
		@toggle-important="onToggleImportant"
		@toggle-seen="onToggleSeen"
		@update:menuOpen="closeMoreAndSnoozeOptions">
		<template #icon>
			<Star v-if="data.flags.flagged"
				fill-color="#f9cf3d"
				:size="18"
				class="app-content-list-item-star favorite-icon-style"
				:class="{ 'one-line': oneLineLayout, 'favorite-icon-style': !oneLineLayout }"
				:data-starred="data.flags.flagged ? 'true' : 'false'"
				@click.prevent="hasWriteAcl ? onToggleFlagged() : false" />
			<ImportantIcon v-if="isImportant"
				:size="18"
				class="app-content-list-item-star icon-important"
				:class="{ 'important-one-line': oneLineLayout, 'icon-important': !oneLineLayout }"
				data-starred="true" />
			<JunkIcon v-if="data.flags.$junk"
				:size="18"
				class="app-content-list-item-star junk-icon-style"
				:class="{ 'one-line': oneLineLayout, 'junk-icon-style': !oneLineLayout }"
				:data-starred="data.flags.$junk ? 'true' : 'false'"
				@click.prevent="hasWriteAcl ? onToggleJunk() : false" />
			<div class="hovering-status"
				:class="{ 'hover-active': hoveringAvatar && !selected }"
				@mouseenter="hoveringAvatar = true"
				@mouseleave="hoveringAvatar = false"
				@click.stop.exact.prevent="toggleSelected"
				@click.shift.exact.prevent="onSelectMultiple">
				<template v-if="hoveringAvatar || selected">
					<CheckIcon :size="28" class="check-icon" :class="{ 'app-content-list-item-avatar-selected': selected }" />
				</template>
				<template v-else>
					<Avatar :display-name="addresses"
						:email="avatarEmail"
						:fetch-avatar="data.fetchAvatarFromClient"
						:avatar="data.avatar" />
				</template>
			</div>
		</template>
		<template #subname>
			<div class="line-two"
				:class="{ 'one-line': oneLineLayout }">
				<div class="envelope__subtitle">
					<Reply v-if="data.flags.answered"
						class="seen-icon-style"
						:size="18" />
					<IconAttachment v-if="data.flags.hasAttachments === true"
						class="attachment-icon-style"
						:size="18" />
					<span class="envelope__subtitle__subject"
						:class="{'one-line': oneLineLayout }"
						dir="auto">
						<span class="envelope__subtitle__subject__text" :class="{'one-line': oneLineLayout, draft }" v-html="subjectForSubtitle" />
					</span>
				</div>
				<div v-if="data.encrypted || data.previewText"
					class="envelope__preview-text"
					:title="data.summary ? t('mail', 'This summary was AI generated') : null">
					<NcAssistantIcon v-if="data.summary" :size="15" class="envelope__preview-text__icon" />
					{{ isEncrypted ? t('mail', 'Encrypted message') : data.summary ? data.summary.trim() : data.previewText.trim() }}
				</div>
			</div>
		</template>
		<template #indicator>
			<!-- Color dot -->
			<IconBullet v-if="!data.flags.seen"
				:size="20"
				:aria-hidden="false"
				:aria-label="t('mail', 'This message is unread')"
				fill-color="var(--color-primary-element)" />
		</template>
		<template #actions>
			<EnvelopePrimaryActions v-if="!moreActionsOpen && !snoozeOptions" id="primary-actions">
				<ActionButton v-if="hasWriteAcl"
					class="action--primary"
					:close-after-click="true"
					@click.prevent="onToggleFlagged">
					<template #icon>
						<StarOutline v-if="showFavoriteIconVariant"
							:size="24" />
						<Star v-else
							:size="24" />
					</template>
					{{
						data.flags.flagged ? t('mail', 'Unfavorite') : t('mail', 'Favorite')
					}}
				</ActionButton>
				<ActionButton v-if="hasSeenAcl"
					class="action--primary"
					:close-after-click="true"
					@click.prevent="onToggleSeen">
					<template #icon>
						<EmailUnread v-if="showImportantIconVariant"
							:size="24" />
						<EmailRead v-else
							:size="24" />
					</template>
					{{
						data.flags.seen ? t('mail', 'Unread') : t('mail', 'Read')
					}}
				</ActionButton>
				<ActionButton v-if="hasWriteAcl"
					class="action--primary"
					:close-after-click="true"
					@click.prevent="onToggleImportant">
					<template #icon>
						<ImportantIcon v-if="isImportant" :size="24" />
						<ImportantOutlineIcon v-else :size="24" />
					</template>
					{{
						isImportant ? t('mail', 'Unimportant') : t('mail', 'Important')
					}}
				</ActionButton>
			</EnvelopePrimaryActions>
			<template v-if="!moreActionsOpen && !snoozeOptions && !quickActionMenu">
				<ActionText>
					<template #icon>
						<ClockOutlineIcon :size="20" />
					</template>
					{{
						messageLongDate
					}}
				</ActionText>
				<NcActionSeparator />
				<ActionButton :is-menu="true" @click="showQuickActionsMenu">
					<template #icon>
						<IconEmailFast :size="20" />
					</template>
					{{ t('mail', 'Quick actions') }}
				</ActionButton>
				<ActionButton v-if="hasWriteAcl"
					:close-after-click="true"
					@click.prevent="onToggleJunk">
					<template #icon>
						<AlertOctagonIcon :size="20" />
					</template>
					{{
						data.flags.$junk ? t('mail', 'Mark not spam') : t('mail', 'Mark as spam')
					}}
				</ActionButton>
				<ActionButton v-if="hasWriteAcl"
					:close-after-click="true"
					@click.prevent="onOpenTagModal">
					<template #icon>
						<TagIcon :size="20" />
					</template>
					{{ t('mail', 'Edit tags') }}
				</ActionButton>
				<ActionButton v-if="!isSnoozeDisabled && !isSnoozedMailbox"
					:close-after-click="false"
					@click="showSnoozeOptions">
					<template #icon>
						<AlarmIcon :title="t('mail', 'Snooze')"
							:size="20" />
					</template>
					{{
						t('mail', 'Snooze')
					}}
				</ActionButton>
				<ActionButton v-if="!isSnoozeDisabled && isSnoozedMailbox"
					:close-after-click="true"
					@click="onUnSnooze">
					<template #icon>
						<AlarmIcon :title="t('mail', 'Unsnooze')"
							:size="20" />
					</template>
					{{ t('mail', 'Unsnooze') }}
				</ActionButton>
				<ActionButton v-if="hasDeleteAcl"
					:close-after-click="true"
					@click.prevent="onOpenMoveModal">
					<template #icon>
						<OpenInNewIcon :size="20" />
					</template>
					<template v-if="layoutMessageViewThreaded">
						{{ t('mail', 'Move thread') }}
					</template>
					<template v-else>
						{{ t('mail', 'Move Message') }}
					</template>
				</ActionButton>
				<ActionButton v-if="showArchiveButton && hasArchiveAcl"
					:close-after-click="true"
					:disabled="disableArchiveButton"
					@click.prevent="onArchive">
					<template #icon>
						<ArchiveIcon :size="20" />
					</template>
					<template v-if="layoutMessageViewThreaded">
						{{ t('mail', 'Archive thread') }}
					</template>
					<template v-else>
						{{ t('mail', 'Archive message') }}
					</template>
				</ActionButton>
				<ActionButton v-if="hasDeleteAcl"
					:close-after-click="true"
					@click.prevent="onDelete">
					<template #icon>
						<DeleteIcon :size="20" />
					</template>
					<template v-if="layoutMessageViewThreaded">
						{{ t('mail', 'Delete thread') }}
					</template>
					<template v-else>
						{{ t('mail', 'Delete message') }}
					</template>
				</ActionButton>
				<ActionButton :close-after-click="false"
					@click="showMoreActionOptions">
					<template #icon>
						<DotsHorizontalIcon :size="20" />
					</template>
					{{ t('mail', 'More actions') }}
				</ActionButton>
			</template>
			<template v-if="snoozeOptions">
				<ActionButton :close-after-click="false"
					@click="snoozeOptions = false">
					<template #icon>
						<ChevronLeft :size="20" />
					</template>
					{{
						t('mail', 'Back')
					}}
				</ActionButton>

				<NcActionSeparator />

				<ActionButton v-for="option in reminderOptions"
					:key="option.key"
					:aria-label="option.ariaLabel"
					close-after-click
					@click.stop="onSnooze(option.timestamp)">
					{{ option.label }}
				</ActionButton>

				<NcActionSeparator />

				<NcActionInput type="datetime-local"
					is-native-picker
					:value="customSnoozeDateTime"
					:min="new Date()"
					@change="setCustomSnoozeDateTime">
					<template #icon>
						<CalendarClock :size="20" />
					</template>
				</NcActionInput>

				<ActionButton :aria-label="t('mail', 'Set custom snooze')"
					close-after-click
					@click.stop="setCustomSnooze(customSnoozeDateTime)">
					<template #icon>
						<CheckIcon :size="20" />
					</template>
					{{ t('mail', 'Set custom snooze') }}
				</ActionButton>
			</template>
			<template v-if="moreActionsOpen">
				<ActionButton :close-after-click="false"
					@click="moreActionsOpen=false">
					<template #icon>
						<ChevronLeft :size="20" />
					</template>
					{{ t('mail', 'More actions') }}
				</ActionButton>
				<ActionButton :close-after-click="true"
					@click.prevent="onOpenEditAsNew">
					<template #icon>
						<PlusIcon :size="20" />
					</template>
					{{ t('mail', 'Edit as new message') }}
				</ActionButton>
				<ActionButton :close-after-click="true"
					@click.prevent="showEventModal = true">
					<template #icon>
						<IconCreateEvent :size="20" />
					</template>
					{{ t('mail', 'Reply with meeting') }}
				</ActionButton>
				<ActionButton :close-after-click="true"
					@click.prevent="showTaskModal = true">
					<template #icon>
						<TaskIcon :size="20" />
					</template>
					{{ t('mail', 'Create task') }}
				</ActionButton>
				<ActionLink :close-after-click="true"
					:href="exportMessageLink">
					<template #icon>
						<DownloadIcon :size="20" />
					</template>
					{{ t('mail', 'Download message') }}
				</ActionLink>
			</template>
			<template v-if="quickActionMenu">
				<ActionButton :close-after-click="false"
					@click="closeQuickActionsMenu()">
					<template #icon>
						<ChevronLeft :size="20" />
					</template>
					{{ t('mail', 'Back to all actions') }}
				</ActionButton>
				<ActionButton v-for="action in filteredQuickActions"
					:key="action.id"
					:close-after-click="true"
					@click="executeQuickAction(action)">
					<template #icon>
						<Icon :action="action?.icon" />
					</template>
					{{ action.name }}
				</ActionButton>
				<ActionButton :close-after-click="true" @click="$emit('open:quick-actions-settings')">
					<template #icon>
						<CogIcon :size="20" />
					</template>
					{{ t('mail', 'Manage quick actions') }}
				</ActionButton>
			</template>
		</template>
		<template #tags>
			<div v-for="tag in tags"
				:key="tag.id"
				class="tag-group">
				<div class="tag-group__bg"
					:style="{'background-color': tag.color}" />
				<span class="tag-group__label"
					:style="{color: tag.color}">
					{{ translateTagDisplayName(tag) }}
				</span>
			</div>
			<MoveModal v-if="showMoveModal"
				:account="account"
				:envelopes="[data]"
				:move-thread="listViewThreaded"
				@move="onMove"
				@close="onCloseMoveModal" />
			<EventModal v-if="showEventModal"
				:envelope="data"
				@close="showEventModal = false" />
			<TaskModal v-if="showTaskModal"
				:envelope="data"
				@close="showTaskModal = false" />
			<TagModal v-if="showTagModal"
				:account="account"
				:envelopes="[data]"
				@close="onCloseTagModal" />
		</template>
	</EnvelopeSkeleton>
</template>
<script>
import {
	NcActionButton as ActionButton,
	NcActionLink as ActionLink,
	NcActionSeparator,
	NcActionInput,
	NcActionText as ActionText, NcAssistantIcon,
} from '@nextcloud/vue'
import EnvelopeSkeleton from './EnvelopeSkeleton.vue'
import AlertOctagonIcon from 'vue-material-design-icons/AlertOctagonOutline.vue'
import Avatar from './Avatar.vue'
import IconCreateEvent from 'vue-material-design-icons/CalendarOutline.vue'
import ClockOutlineIcon from 'vue-material-design-icons/ClockOutline.vue'
import CheckIcon from 'vue-material-design-icons/Check.vue'
import ChevronLeft from 'vue-material-design-icons/ChevronLeft.vue'
import DeleteIcon from 'vue-material-design-icons/TrashCanOutline.vue'
import ArchiveIcon from 'vue-material-design-icons/ArchiveArrowDownOutline.vue'
import TaskIcon from 'vue-material-design-icons/CheckboxMarkedCirclePlusOutline.vue'
import CogIcon from 'vue-material-design-icons/CogOutline.vue'
import DotsHorizontalIcon from 'vue-material-design-icons/DotsHorizontal.vue'
import ImportantIcon from 'vue-material-design-icons/LabelVariant.vue'
import ImportantOutlineIcon from 'vue-material-design-icons/LabelVariantOutline.vue'
import IconEmailFast from 'vue-material-design-icons/EmailFastOutline.vue'
import { DraggableEnvelopeDirective } from '../directives/drag-and-drop/draggable-envelope/index.js'
import { buildRecipients as buildReplyRecipients } from '../ReplyBuilder.js'
import { shortRelativeDatetime, messageDateTime } from '../util/shortRelativeDatetime.js'
import { showError, showSuccess, showWarning } from '@nextcloud/dialogs'
import NoTrashMailboxConfiguredError
	from '../errors/NoTrashMailboxConfiguredError.js'
import logger from '../logger.js'
import { matchError } from '../errors/match.js'
import MoveModal from './MoveModal.vue'
import OpenInNewIcon from 'vue-material-design-icons/OpenInNew.vue'
import StarOutline from 'vue-material-design-icons/StarOutline.vue'
import Star from 'vue-material-design-icons/Star.vue'
import Reply from 'vue-material-design-icons/ReplyOutline.vue'
import EmailRead from 'vue-material-design-icons/EmailOpenOutline.vue'
import EmailUnread from 'vue-material-design-icons/EmailOutline.vue'
import IconAttachment from 'vue-material-design-icons/Paperclip.vue'
import IconBullet from 'vue-material-design-icons/CheckboxBlankCircle.vue'
import JunkIcon from './icons/JunkIcon.vue'
import PlusIcon from 'vue-material-design-icons/Plus.vue'
import TagIcon from 'vue-material-design-icons/TagOutline.vue'
import TagModal from './TagModal.vue'
import EventModal from './EventModal.vue'
import TaskModal from './TaskModal.vue'
import EnvelopePrimaryActions from './EnvelopePrimaryActions.vue'
import escapeHtml from 'escape-html'
import { hiddenTags } from './tags.js'
import { generateUrl } from '@nextcloud/router'
import { isPgpText } from '../crypto/pgp.js'
import { mailboxHasRights } from '../util/acl.js'
import DownloadIcon from 'vue-material-design-icons/TrayArrowDown.vue'
import CalendarClock from 'vue-material-design-icons/CalendarClockOutline.vue'
import AlarmIcon from 'vue-material-design-icons/Alarm.vue'
import moment from '@nextcloud/moment'
import { mapState, mapStores } from 'pinia'
import useMainStore from '../store/mainStore.js'
import { FOLLOW_UP_TAG_LABEL } from '../store/constants.js'
import { translateTagDisplayName } from '../util/tag.js'
import { findAllStepsForAction } from '../service/QuickActionsService.js'
import Icon from './quickActions/Icon.vue'
import { isRTL } from '@nextcloud/l10n'

export default {
	name: 'Envelope',
	components: {
		AlertOctagonIcon,
		Avatar,
		IconCreateEvent,
		CheckIcon,
		ChevronLeft,
		DeleteIcon,
		ArchiveIcon,
		TaskIcon,
		DotsHorizontalIcon,
		EnvelopePrimaryActions,
		EventModal,
		ImportantIcon,
		ImportantOutlineIcon,
		TaskModal,
		EnvelopeSkeleton,
		JunkIcon,
		ActionButton,
		MoveModal,
		OpenInNewIcon,
		PlusIcon,
		TagIcon,
		TagModal,
		Star,
		StarOutline,
		EmailRead,
		EmailUnread,
		IconAttachment,
		IconBullet,
		Reply,
		ActionLink,
		ActionText,
		DownloadIcon,
		ClockOutlineIcon,
		NcActionSeparator,
		NcActionInput,
		CalendarClock,
		AlarmIcon,
		NcAssistantIcon,
		CogIcon,
		IconEmailFast,
		Icon,
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
			showMoveModal: false,
			showEventModal: false,
			showTaskModal: false,
			showTagModal: false,
			moreActionsOpen: false,
			snoozeOptions: false,
			quickActionMenu: false,
			customSnoozeDateTime: new Date(moment().add(2, 'hours').minute(0).second(0).valueOf()),
			overwriteOneLineMobile: false,
			hoveringAvatar: false,
			filteredQuickActions: [],
			quickActionLoading: false,
		}
	},
	computed: {
		...mapStores(useMainStore),
		...mapState(useMainStore, [
			'isSnoozeDisabled',
		]),
		isRTL() {
			return isRTL()
		},
		messageLongDate() {
			return messageDateTime(new Date(this.data.dateInt))
		},
		oneLineLayout() {
			return this.overwriteOneLineMobile ? false : this.mainStore.getPreference('layout-mode', 'vertical-split') === 'no-split'
		},
		layoutMessageViewThreaded() {
			return this.mainStore.getPreference('layout-message-view', 'threaded') === 'threaded'
		},
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
		draft() {
			return this.data.flags.draft
		},
		account() {
			const accountId = this.data.accountId
			return this.mainStore.getAccount(accountId)
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
				}
			}
		},
		addresses() {
			// Show recipients' label/address in a sent mailbox
			if (this.mailbox.specialRole === 'sent' || this.account.sentMailboxId === this.mailbox.databaseId) {
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
		showArchiveButton() {
			return this.account.archiveMailboxId !== null
		},
		disableArchiveButton() {
			return this.account.archiveMailboxId !== null
				&& this.account.archiveMailboxId === this.mailbox.databaseId
		},
		showFavoriteIconVariant() {
			return !this.data.flags.flagged
		},
		showImportantIconVariant() {
			return this.data.flags.seen
		},
		isEncrypted() {
			return this.data.encrypted // S/MIME
				|| (this.data.previewText && isPgpText(this.data.previewText)) // PGP/Mailvelope
		},
		isImportant() {
			return this.mainStore
				.getEnvelopeTags(this.data.databaseId)
				.some((tag) => tag.imapLabel === '$label1')
		},
		tags() {
			let tags = this.mainStore.getEnvelopeTags(this.data.databaseId).filter(
				(tag) => tag.imapLabel && tag.imapLabel !== '$label1' && !(tag.displayName.toLowerCase() in hiddenTags),
			)

			// Don't show follow-up tag in unified mailbox as it has its own section at the top
			if (this.mailbox.isUnified) {
				tags = tags.filter((tag) => tag.imapLabel !== FOLLOW_UP_TAG_LABEL)
			}

			return tags
		},
		draggableLabel() {
			let label = this.data.subject
			const sender = this.data.from[0]?.label ?? this.data.from[0]?.email
			if (sender) {
				label += ` (${sender})`
			}
			return label
		},
		isDraggable() {
			return mailboxHasRights(this.mailbox, 'te')
		},
		/**
		 * Subject of envelope or "No Subject".
		 *
		 * @return {string}
		 */
		subjectForSubtitle() {
			const subject = this.data.subject || this.t('mail', 'No subject')
			if (this.draft) {
				return this.t('mail', '{markup-start}Draft:{markup-end} {subject}', {
					'markup-start': '<em>',
					'markup-end': '</em>',
					subject: escapeHtml(subject),
				}, {
					escape: false,
				})
			}
			return escapeHtml(subject)
		},
		storeActions() {
			return this.mainStore.getQuickActions()
		},
		/**
		 * Link to download the whole message (.eml).
		 *
		 * @return {string}
		 */
		exportMessageLink() {
			return generateUrl('/apps/mail/api/messages/{id}/export', {
				id: this.data.databaseId,
			})
		},
		hasSeenAcl() {
			return mailboxHasRights(this.mailbox, 's')
		},
		hasArchiveAcl() {
			const hasDeleteSourceAcl = () => {
				return mailboxHasRights(this.mailbox, 'te')
			}
			const hasCreateDestinationAcl = () => {
				return mailboxHasRights(this.archiveMailbox, 'i')

			}
			return hasDeleteSourceAcl() && hasCreateDestinationAcl()
		},
		hasDeleteAcl() {
			return mailboxHasRights(this.mailbox, 'te')
		},
		hasWriteAcl() {
			return mailboxHasRights(this.mailbox, 'w')
		},
		archiveMailbox() {
			return this.mainStore.getMailbox(this.account.archiveMailboxId)
		},
		isSnoozedMailbox() {
			return this.mailbox.databaseId === this.account.snoozeMailboxId
		},
		reminderOptions() {
			const currentDateTime = moment()

			// Same day 18:00 PM (hidden if after 17:00 PM now)
			const laterTodayTime = (currentDateTime.hour() < 17)
				? moment().hour(18)
				: null

			// Tomorrow 08:00 AM
			const tomorrowTime = moment().add(1, 'days').hour(8)

			// Saturday 08:00 AM (hidden if Friday, Saturday or Sunday now)
			const thisWeekendTime = (currentDateTime.day() > 0 && currentDateTime.day() < 5)
				? moment().day(6).hour(8)
				: null

			// Next Monday 08:00 AM (hidden if Sunday now)
			const nextWeekTime = (currentDateTime.day() !== 0)
				? moment().add(1, 'weeks').day(1).hour(8)
				: null

			return [
				{
					key: 'laterToday',
					timestamp: this.getTimestamp(laterTodayTime),
					label: t('spreed', 'Later today – {timeLocale}', { timeLocale: laterTodayTime?.format('LT') }),
					ariaLabel: t('spreed', 'Set reminder for later today'),
				},
				{
					key: 'tomorrow',
					timestamp: this.getTimestamp(tomorrowTime),
					label: t('spreed', 'Tomorrow – {timeLocale}', { timeLocale: tomorrowTime?.format('ddd LT') }),
					ariaLabel: t('spreed', 'Set reminder for tomorrow'),
				},
				{
					key: 'thisWeekend',
					timestamp: this.getTimestamp(thisWeekendTime),
					label: t('spreed', 'This weekend – {timeLocale}', { timeLocale: thisWeekendTime?.format('ddd LT') }),
					ariaLabel: t('spreed', 'Set reminder for this weekend'),
				},
				{
					key: 'nextWeek',
					timestamp: this.getTimestamp(nextWeekTime),
					label: t('spreed', 'Next week – {timeLocale}', { timeLocale: nextWeekTime?.format('ddd LT') }),
					ariaLabel: t('spreed', 'Set reminder for next week'),
				},
			].filter(option => option.timestamp !== null)
		},
	},
	 watch: {
		storeActions() {
			this.filterAndEnrichQuickActions()
		},
	},
	async mounted() {
		this.onWindowResize()
		window.addEventListener('resize', this.onWindowResize)
		if (this.filteredQuickActions.length === 0) {
			await this.filterAndEnrichQuickActions()
		}
	},
	methods: {
		translateTagDisplayName,
		setSelected(value) {
			if (this.selected !== value) {
				this.$emit('update:selected', value)
			}
		},
		formatted() {
			return shortRelativeDatetime(new Date(this.data.dateInt * 1000))
		},
		async filterAndEnrichQuickActions() {
			this.filteredQuickActions = []
			const quickActions = this.mainStore.getQuickActions().filter(action => action.accountId === this.data.accountId)
			for (const action of quickActions) {
				const steps = await findAllStepsForAction(action.id)
				const check = steps.every(step => {
					if (['markAsSpam', 'applyTag', 'markAsImportant', 'markAsFavorite'].includes(step.type) && !this.hasWriteAcl) {
						return false
					}
					if (['markAsRead', 'markAsUnread'].includes(step.type) && !this.hasSeenAcl) {
						return false
					}
					if (['moveThread', 'deleteThread'].includes(step.type) && !this.hasDeleteAcl) {
						return false
					}
					return true
				})
				if (check) {
					this.filteredQuickActions.push({
						...action,
						steps,
						icon: steps[0]?.name,
					})
				}
			}
		},
		async executeQuickAction(action) {
			this.closeQuickActionsMenu()
			this.quickActionLoading = true
			try {
				for (const step of action.steps) {
					switch (step.name) {
					case 'markAsSpam':
						await this.onToggleJunk()
						break
					case 'applyTag':
						if (step?.tagId) {
							await this.setTag(step.tagId)
						} else {
							// usually happens when the tag was deleted in the meantime
							showWarning(t('mail', 'Could not apply tag, configured tag not found'))
						}
						break
					case 'markAsImportant':
						if (!this.isImportant) {
							this.onToggleImportant()
						}
						break
					case 'markAsFavorite':
						if (!this.data.flags.flagged) {
							this.onToggleFlagged()
						}
						break
					case 'markAsRead':
						if (!this.data.flags.seen) {
							this.onToggleSeen()
						}
						break
					case 'markAsUnread':
						if (this.data.flags.seen) {
							this.onToggleSeen()
						}
						break
					case 'moveThread':
						if (step.mailboxId) {
							await this.moveThread(step.mailboxId)
						} else {
							// usually happens when the mailbox was deleted in the meantime
							showWarning(t('mail', 'Could not move thread, destination mailbox not found'))
						}
						break
					case 'deleteThread':
						this.onDelete()
						break
					default:
						logger.warn(`Unknown quick action step type: ${step.type}`)
					}
				}
			} catch (error) {
				logger.error('Could not execute quick action', error)
				showError(t('mail', 'Could not execute quick action'))
				this.quickActionLoading = false
				return
			}
			showSuccess(t('mail', 'Quick action executed'))
			this.quickActionLoading = false

		},
		async setTag(tagId) {
			const tag = this.mainStore.getTag(tagId)
			const threadEnvelopes = this.layoutMessageViewThreaded
				? this.mainStore.getEnvelopesByThreadRootId(this.data.accountId, this.data.threadRootId)
				: [this.data]
			if (!tag) {
				showWarning(t('mail', 'Could not apply tag, configured tag not found'))
				return
			}
			for (const envelope of threadEnvelopes) {
				await this.mainStore.addEnvelopeTag({ envelope, imapLabel: tag.imapLabel })
			}
		},
		unselect() {
			if (this.selected) {
				this.$emit('update:selected', false)
			}
		},
		toggleSelected() {
			this.$emit('update:selected', !this.selected)
		},
		async onClick(event) {
			if (!event.ctrlKey && this.draft && !event.defaultPrevented) {
				await this.mainStore.startComposerSession({
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
			this.mainStore.toggleEnvelopeImportant(this.data)
		},
		onToggleFlagged() {
			this.mainStore.toggleEnvelopeFlagged(this.data)
		},
		onToggleSeen() {
			this.mainStore.toggleEnvelopeSeen({ envelope: this.data })
		},
		async onToggleJunk() {
			const removeEnvelope = await this.mainStore.moveEnvelopeToJunk(this.data)

			if (this.isImportant) {
				await this.mainStore.toggleEnvelopeImportant(this.data)
			}

			if (!this.data.flags.seen) {
				await this.mainStore.toggleEnvelopeSeen({ envelope: this.data })
			}

			/**
			 * moveEnvelopeToJunk returns true if the envelope should be moved to a different mailbox.
			 *
			 * Our backend (MessageMapper.move) implemented move as copy and delete.
			 * The message is copied to another mailbox and gets a new UID; the message in the current folder is deleted.
			 *
			 * Trigger the delete event here to open the next envelope and remove the current envelope from the list.
			 * The delete event bubbles up to Mailbox.onDelete to the actual implementation.
			 *
			 * In Mailbox.onDelete, fetchNextEnvelopes requires the current envelope to find the next envelope.
			 * Therefore, it must run before removing the envelope.
			 */

			if (removeEnvelope) {
				await this.$emit('delete', this.data.databaseId)
			}

			await this.mainStore.toggleEnvelopeJunk({
				envelope: this.data,
				removeEnvelope,
			})
		},
		async onDelete() {
			// Remove from selection first
			this.setSelected(false)
			// Delete
			this.$emit('delete', this.data.databaseId)

			try {
				if (this.layoutMessageViewThreaded) {
					await this.mainStore.deleteThread({
						envelope: this.data,
					})
				} else {
					await this.mainStore.deleteMessage({
						id: this.data.databaseId,
					})
				}
			} catch (error) {
				showError(await matchError(error, {
					[NoTrashMailboxConfiguredError.getName()]() {
						return t('mail', 'No trash folder configured')
					},
					default(error) {
						logger.error('could not delete message', error)
						return t('mail', 'Could not delete message')
					},
				}))
			}
		},
		showMoreActionOptions() {
			this.snoozeOptions = false
			this.moreActionsOpen = true
		},
		showSnoozeOptions() {
			this.snoozeOptions = true
			this.moreActionsOpen = false
		},
		closeMoreAndSnoozeOptions() {
			this.snoozeOptions = false
			this.moreActionsOpen = false
		},
		showQuickActionsMenu() {
			this.snoozeOptions = false
			this.moreActionsOpen = false
			this.quickActionMenu = true
		},
		closeQuickActionsMenu() {
			this.quickActionMenu = false
		},
		async onArchive() {
			// Remove from selection first
			this.setSelected(false)
			// Archive
			this.$emit('archive', this.data.databaseId)

			try {
				if (this.layoutMessageViewThreaded) {
					await this.mainStore.moveThread({
						envelope: this.data,
						destMailboxId: this.account.archiveMailboxId,
					})
				} else {
					await this.mainStore.moveMessage({
						id: this.data.databaseId,
						destMailboxId: this.account.archiveMailboxId,
					})
				}
			} catch (error) {
				logger.error('could not archive message', error)
				showError(t('mail', 'Could not archive message'))
			}
		},
		async onSnooze(timestamp) {
			// Remove from selection first
			this.setSelected(false)

			if (!this.account.snoozeMailboxId) {
				await this.mainStore.createAndSetSnoozeMailbox(this.account)
			}

			try {
				if (this.layoutMessageViewThreaded) {
					await this.mainStore.snoozeThread({
						envelope: this.data,
						unixTimestamp: timestamp / 1000,
						destMailboxId: this.account.snoozeMailboxId,
					})
				} else {
					await this.mainStore.snoozeMessage({
						id: this.data.databaseId,
						unixTimestamp: timestamp / 1000,
						destMailboxId: this.account.snoozeMailboxId,
					})
				}
				showSuccess(t('mail', 'Thread was snoozed'))
			} catch (error) {
				logger.error('could not snooze thread', error)
				showError(t('mail', 'Could not snooze thread'))
			}
		},
		async onUnSnooze() {
			// Remove from selection first
			this.setSelected(false)

			try {
				if (this.layoutMessageViewThreaded) {
					await this.mainStore.unSnoozeThread({
						envelope: this.data,
					})
				} else {
					await this.mainStore.unSnoozeMessage({
						id: this.data.databaseId,
					})
				}
				showSuccess(t('mail', 'Thread was unsnoozed'))
			} catch (error) {
				logger.error('Could not unsnooze thread', error)
				showError(t('mail', 'Could not unsnooze thread'))
			}
		},
		async onOpenEditAsNew() {
			await this.mainStore.startComposerSession({
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
		async moveThread(destMailboxId) {
			if (this.layoutMessageViewThreaded) {
				await this.mainStore.moveThread({
					envelope: this.data,
					destMailboxId,
				})
			} else {
				await this.mainStore.moveMessage({
					id: this.data.databaseId,
					destMailboxId,
				})
			}
			this.onMove()

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
		getTimestamp(momentObject) {
			return momentObject?.minute(0).second(0).millisecond(0).valueOf() || null
		},
		setCustomSnoozeDateTime(event) {
			this.customSnoozeDateTime = new Date(event.target.value)
		},
		setCustomSnooze() {
			this.onSnooze(this.customSnoozeDateTime.valueOf())
		},
		onWindowResize() {
			const widthOutput = window.innerWidth

			if (widthOutput <= 700) {
				this.overwriteOneLineMobile = true
			} else {
				this.overwriteOneLineMobile = false
			}
		},
	},
}
</script>
<style lang="scss" scoped>
.mail-message-account-color {
	position: absolute;
	inset-inline-start: 0px;
	width: 2px;
	height: 69px;
	z-index: 1;
}

.envelope {
	.app-content-list-item-icon {
		height: 40px; // To prevent some unexpected spacing below the avatar
	}

	&__subtitle {
		display: flex;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
		align-items: center;
		&__subject {
			flex: 1;
			overflow: hidden;
			text-overflow: ellipsis;
			white-space: nowrap;
			line-height: var(--default-line-height);
			&__text {
				&.draft {
					line-height: 130%;
					/* deep because there is no data attribute for the em rendered from JS output */
					:deep(em) {
						font-style: italic;
					}
				}
			}
		}
	}
	&__preview-text {
		color: var(--color-text-maxcontrast);
		overflow: hidden;
		font-weight: initial;
		max-height: calc(var(--default-font-size) * var(--default-line-height) * 2);

		/* Weird CSS hacks to make text ellipsize without white-space: nowrap */
		display: -webkit-box;
		-webkit-line-clamp: 2;
		-webkit-box-orient: vertical;

		.material-design-icon {
			display: inline;

			position: relative;
			top: 2px;
		}
		&__icon {
			display: inline;
		}
	}
}

.list-item__wrapper--active {
	div, :deep(.list-item-content__inner__details__details) {
		color: var(--color-primary-element-text) !important;
	}
}

.icon-important {
	:deep(path) {
		fill: #ffcc00;
		stroke: var(--color-main-background);
		stroke-width: 2;
	}
	.list-item:hover &,
	.list-item:focus &,
	.list-item.active & {
		:deep(path) {
			stroke: var(--color-background-dark);
		}
	}

	// In message list, but not the one in the action menu
	&.app-content-list-item-star {
		background-image: none;
		inset-inline-start: 1px;
		top: 8px;
		opacity: 1;
	}
}

.important-one-line.app-content-list-item-star:deep() {
	top: 4px !important;
	inset-inline-start: 2px;
}

.app-content-list-item-select-checkbox {
	display: inline-block;
	vertical-align: middle;
	position: absolute;
	inset-inline-start: 33px;
	top: 35px;
	z-index: 50; // same as icon-starred
}

.list-item-style:not(.seen) {
	font-weight: bold;
}

.junk-icon-style {
	opacity: .2;
	display: flex;
	top: 32px;
	inset-inline-start: 32px;
	background-size: 16px;
	height: 20px;
	width: 20px;
	margin: 0;
	padding: 0;
	position: absolute;
	z-index: 2;
	&:hover {
		opacity: .1;
	}
}

.one-line.junk-icon-style {
	top: 36px;
}

.icon-attachment {
	-ms-filter: 'progid:DXImageTransform.Microsoft.Alpha(Opacity=25)';
	opacity: 0.25;
}

:deep(.action--primary) {
	.material-design-icon {
		margin-bottom: -14px;
	}
}

.tag-group__label {
	margin: 0 7px;
	z-index: 2;
	font-size: calc(var(--default-font-size) * 0.8);
	font-weight: bold;
	padding-inline: 2px;
	white-space: nowrap;
}

.tag-group__bg {
	position: absolute;
	width: 100%;
	height: 100%;
	top: 0;
	inset-inline-start: 0;
	opacity: 15%;
}

.tag-group {
	display: inline-block;
	border-radius: var(--border-radius-pill);
	position: relative;
	margin-inline-end: 1px;
	overflow: hidden;
	text-overflow: ellipsis;
}

.list-item__wrapper:deep() {
	list-style: none;
}

.icon-important.app-content-list-item-star:deep() {
	position: absolute;
	top: 3px;
	z-index: 1;
	stroke: var(--color-main-background);
	stroke-width: 2;
}

.app-content-list-item-star.favorite-icon-style {
	display: inline-block;
	position: absolute;
	top: 3px;
	inset-inline-start: 30px;
	cursor: pointer;
	stroke: var(--color-main-background);
	stroke-width: 2;
	z-index: 1;
	&:hover {
		opacity: .4;
	}
}

.one-line.favorite-icon-style {
	top: 3px;
	inset-inline-start: 31px;
}

.seen-icon-style,
.attachment-icon-style  {
	opacity: .6;
	display: inline-flex;
	align-items: center;
	margin-inline-end: 5px;
}

:deep(.list-item__anchor) {
	margin-top: 6px;
	margin-bottom: 6px;
}

:deep(.line-two__subtitle) {
	display: flex;
	flex-basis: 100%;
	padding-inline-start: 40px;
	width: 450px;
}

:deep(.line-one__title) {
	flex-direction: row;
	display: flex;
	width: 200px;
}

.line-two.one-line {
	display: flex;
	overflow: hidden;
	align-items: center;
	text-overflow: ellipsis;
	white-space: nowrap;
}

.quick-actions-button{
	width: 100%;
	display: flex;
	justify-content: space-between;
	align-items: center;
}

.envelope__subtitle__subject.one-line {
	display: flex;
	align-items: center;
	height: calc(var(--default-font-size) * var(--default-line-height));

	&::after {
		content: '\00B7';
		margin: 12px;
	}
}

.envelope__subtitle__subject__text.one-line {
	max-width: 300px;
	display: inline-block;
	text-overflow: ellipsis;
	overflow: hidden;
}

.app-content-list-item-avatar-selected {
	background-color: var(--color-primary-element);
	color: var(--color-primary-light);
	border-radius: 32px;
	&:hover {
		background-color: var(--color-primary-element);
		color: var(--color-primary-light);
		border-radius: 32px;
	}
}

.hover-active {
	&:hover {
		color: var(--color-primary-hover);
		background-color: var(--color-primary-light-hover);
		border-radius: 32px;
	}
}

.hovering-status {
	// Needs to be the same height as the check-icon and the avatar to prevent automatic resizing
	// and height differences between hover state and normal state
	height: calc(var(--default-grid-baseline) * 10);
}

.check-icon {
	border-radius: 32px;
	width: calc(var(--default-grid-baseline) * 10);
	height: calc(var(--default-grid-baseline) * 10);
	display: flex;
	align-items: center;
	justify-content: center;
}

</style>
