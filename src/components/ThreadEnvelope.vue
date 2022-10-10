<!--
  - @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
  - @author 2021 Richard Steinmetz <richard@steinmetz.cloud>
  - @author 2022 Jonas Sulzer <jonas@violoncello.ch>
  -
  - @license AGPL-3.0-or-later
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
	<div class="envelope">
		<div class="envelope--header"
			:class="{'list-item-style' : expanded }">
			<Avatar v-if="envelope.from && envelope.from[0]"
				:email="envelope.from[0].email"
				:display-name="envelope.from[0].label"
				:disable-tooltip="true"
				:size="40" />
			<div
				v-if="isImportant"
				class="app-content-list-item-star icon-important"
				:data-starred="isImportant ? 'true' : 'false'"
				@click.prevent="onToggleImportant"
				v-html="importantSvg" />
			<IconFavorite
				v-if="envelope.flags.flagged"
				fill-color="#f9cf3d"
				:size="18"
				:class="{ 'junk-favorite-position': junkFavoritePosition, 'junk-favorite-position-with-tag-subline': junkFavoritePositionWithTagSubline }"
				class="app-content-list-item-star favorite-icon-style"
				:data-starred="envelope.flags.flagged ? 'true' : 'false'"
				@click.prevent="onToggleFlagged" />
			<JunkIcon
				v-if="envelope.flags.$junk"
				:size="18"
				:class="{ 'junk-favorite-position': junkFavoritePosition, 'junk-favorite-position-with-tag-subline': junkFavoritePositionWithTagSubline }"
				class="app-content-list-item-star junk-icon-style"
				:data-starred="envelope.flags.$junk ? 'true' : 'false'"
				@click.prevent="onToggleJunk" />
			<router-link
				:to="route"
				event=""
				class="left"
				:class="{seen: envelope.flags.seen}"
				@click.native.prevent="$emit('toggle-expand', $event)">
				<div class="sender">
					{{ envelope.from && envelope.from[0] ? envelope.from[0].label : '' }}
				</div>
				<div v-if="showSubline" class="subline">
					<span class="preview">
						{{ envelope.previewText }}
					</span>
				</div>
				<div v-for="tag in tags"
					:key="tag.id"
					class="tag-group">
					<div class="tag-group__bg"
						:style="{'background-color': tag.color}" />
					<span class="tag-group__label"
						:style="{color: tag.color}">{{ tag.displayName }} </span>
				</div>
			</router-link>
			<div class="right">
				<Moment class="timestamp" :timestamp="envelope.dateInt" />
				<template v-if="expanded">
					<ButtonVue
						:class="{ primary: expanded}"
						:title="hasMultipleRecipients ? t('mail', 'Reply all') : t('mail', 'Reply')"
						type="tertiary-no-background"
						@click="onReply">
						<template #icon>
							<ReplyAllIcon v-if="hasMultipleRecipients"
								:size="20" />
							<ReplyIcon v-else
								:size="20" />
						</template>
					</ButtonVue>
					<ButtonVue
						type="tertiary-no-background"
						class="action--primary"
						:title="envelope.flags.flagged ? t('mail', 'Mark as unfavorite') : t('mail', 'Mark as favorite')"
						:close-after-click="true"
						@click.prevent="onToggleFlagged">
						<template #icon>
							<StarOutline v-if="showFavoriteIconVariant"
								:size="20" />
							<IconFavorite v-else
								:size="20" />
						</template>
					</ButtonVue>
					<ButtonVue
						type="tertiary-no-background"
						class="action--primary"
						:title="envelope.flags.seen ? t('mail', 'Mark as unread') : t('mail', 'Mark as read')"
						:close-after-click="true"
						@click.prevent="onToggleSeen">
						<template #icon>
							<EmailRead v-if="showImportantIconVariant"
								:size="20" />
							<EmailUnread v-else
								:size="20" />
						</template>
					</ButtonVue>
					<ButtonVue v-if="showArchiveButton"
						:close-after-click="true"
						type="tertiary-no-background"
						@click.prevent="onArchive">
						<template #icon>
							<ArchiveIcon
								:title="t('mail', 'Archive message')"
								:size="20" />
						</template>
					</ButtonVue>
					<ButtonVue :close-after-click="true"
						type="tertiary-no-background"
						@click.prevent="onDelete">
						<template #icon>
							<DeleteIcon
								:title="t('mail', 'Delete message')"
								:size="20" />
						</template>
					</ButtonVue>
					<MenuEnvelope class="app-content-list-item-menu"
						:envelope="envelope"
						:with-reply="false"
						:with-select="false"
						:with-show-source="true"
						@delete="$emit('delete',envelope.databaseId)" />
				</template>
			</div>
		</div>
		<Loading v-if="loading" />
		<Message v-else-if="message"
			:envelope="envelope"
			:message="message"
			:full-height="fullHeight" />
		<Error v-else-if="error"
			:error="error && error.message ? error.message : t('mail', 'Not found')"
			:message="errorMessage"
			:data="error"
			role="alert" />
	</div>
</template>
<script>
import Avatar from './Avatar'
import { NcButton as ButtonVue } from '@nextcloud/vue'
import Error from './Error'
import importantSvg from '../../img/important.svg'
import IconFavorite from 'vue-material-design-icons/Star'
import JunkIcon from './icons/JunkIcon'
import Loading from './Loading'
import logger from '../logger'
import Message from './Message'
import MenuEnvelope from './MenuEnvelope'
import Moment from './Moment'
import ReplyIcon from 'vue-material-design-icons/Reply'
import ReplyAllIcon from 'vue-material-design-icons/ReplyAll'
import StarOutline from 'vue-material-design-icons/StarOutline'
import DeleteIcon from 'vue-material-design-icons/Delete'
import ArchiveIcon from 'vue-material-design-icons/PackageDown'
import EmailUnread from 'vue-material-design-icons/Email'
import EmailRead from 'vue-material-design-icons/EmailOpen'
import { buildRecipients as buildReplyRecipients } from '../ReplyBuilder'
import { hiddenTags } from './tags.js'
import { showError } from '@nextcloud/dialogs'
import { matchError } from '../errors/match'
import NoTrashMailboxConfiguredError from '../errors/NoTrashMailboxConfiguredError'

export default {
	name: 'ThreadEnvelope',
	components: {
		Avatar,
		ButtonVue,
		Error,
		IconFavorite,
		JunkIcon,
		Loading,
		MenuEnvelope,
		Moment,
		Message,
		ReplyIcon,
		ReplyAllIcon,
		StarOutline,
		EmailRead,
		EmailUnread,
		DeleteIcon,
		ArchiveIcon,
	},
	props: {
		envelope: {
			required: true,
			type: Object,
		},
		mailboxId: {
			required: false,
			type: [
				String,
				Number,
			],
			default: undefined,
		},
		expanded: {
			required: false,
			type: Boolean,
			default: false,
		},
		fullHeight: {
			required: false,
			type: Boolean,
			default: false,
		},
		withSelect: {
			// "Select" action should only appear in envelopes from the envelope list
			type: Boolean,
			default: true,
		},
	},
	data() {
		return {
			loading: false,
			error: undefined,
			message: undefined,
			importantSvg,
			seenTimer: undefined,
		}
	},
	computed: {
		account() {
			return this.$store.getters.getAccount(this.envelope.accountId)
		},
		hasMultipleRecipients() {
			if (!this.account) {
				console.error('account is undefined', {
					accountId: this.envelope.accountId,
				})
			}
			const recipients = buildReplyRecipients(this.envelope, {
				label: this.account.name,
				email: this.account.emailAddress,
			})
			return recipients.to.concat(recipients.cc).length > 1
		},
		route() {
			return {
				name: 'message',
				params: {
					mailboxId: this.mailboxId || this.envelope.mailboxId,
					threadId: this.envelope.databaseId,
				},
			}
		},
		isImportant() {
			return this.$store.getters
				.getEnvelopeTags(this.envelope.databaseId)
				.find((tag) => tag.imapLabel === '$label1')
		},
		tags() {
			return this.$store.getters.getEnvelopeTags(this.envelope.databaseId).filter(
				(tag) => tag.imapLabel !== '$label1' && !(tag.displayName.toLowerCase() in hiddenTags)
			)
		},
		showSubline() {
			return !this.expanded && !!this.envelope.previewText
		},
		showArchiveButton() {
			return this.account.archiveMailboxId !== null
		},
		junkFavoritePosition() {
			return this.showSubline && this.tags.length > 0
		},
		junkFavoritePositionWithTagSubline() {
			return !this.showSubline && this.tags.length > 0
		},
		showFavoriteIconVariant() {
			return this.envelope.flags.flagged
		},
		showImportantIconVariant() {
			return this.envelope.flags.seen
		},
	},
	watch: {
		expanded(expanded) {
			if (expanded) {
				this.fetchMessage()
			} else {
				this.message = undefined
			}
		},
	},
	async mounted() {
		if (this.expanded) {
			await this.fetchMessage()

			// Only one envelope is expanded at the time of mounting so we can
			// assume that this is the relevant envelope to be scrolled to.
			this.$nextTick(() => this.scrollToCurrentEnvelope())
		}
	},
	beforeDestroy() {
		if (this.seenTimer !== undefined) {
			logger.info('Navigating away before seenTimer delay, will not mark message as seen/read')
			clearTimeout(this.seenTimer)
		}
	},
	methods: {
		async fetchMessage() {
			this.loading = true

			logger.debug(`fetching thread message ${this.envelope.databaseId}`)

			try {
				this.message = await this.$store.dispatch('fetchMessage', this.envelope.databaseId)
				logger.debug(`message ${this.envelope.databaseId} fetched`, { message: this.message })

				if (!this.envelope.flags.seen) {
					logger.info('Starting timer to mark message as seen/read')
					this.seenTimer = setTimeout(() => {
						this.$store.dispatch('toggleEnvelopeSeen', { envelope: this.envelope })
						this.seenTimer = undefined
					}, 2000)
				}

				this.loading = false
			} catch (error) {
				logger.error('Could not fetch message', { error })
			}

			// Fetch itineraries if they haven't been included in the message data
			if (this.message && !this.message.itineraries) {
				await this.fetchItineraries()
			}
		},
		async fetchItineraries() {
			// Sanity check before actually making the request
			if (!this.message.hasHtmlBody && this.message.attachments.length === 0) {
				return
			}

			logger.debug(`Fetching itineraries for message ${this.envelope.databaseId}`)

			try {
				const itineraries = await this.$store.dispatch('fetchItineraries', this.envelope.databaseId)
				logger.debug(`Itineraries of message ${this.envelope.databaseId} fetched`, { itineraries })
			} catch (error) {
				logger.error(`Could not fetch itineraries of message ${this.envelope.databaseId}`, { error })
			}
		},
		scrollToCurrentEnvelope() {
			// Account for global navigation bar and thread header
			const globalHeader = document.querySelector('#header').clientHeight
			const threadHeader = document.querySelector('#mail-thread-header').clientHeight
			const top = this.$el.getBoundingClientRect().top - globalHeader - threadHeader
			window.scrollTo({ top })
		},
		onReply() {
			this.$store.dispatch('showMessageComposer', {
				reply: {
					mode: this.hasMultipleRecipients ? 'replyAll' : 'reply',
					data: this.envelope,
				},
			})
		},
		onToggleImportant() {
			this.$store.dispatch('toggleEnvelopeImportant', this.envelope)
		},
		onToggleFlagged() {
			this.$store.dispatch('toggleEnvelopeFlagged', this.envelope)
		},
		onToggleJunk() {
			this.$store.dispatch('toggleEnvelopeJunk', this.envelope)
		},
		onToggleSeen() {
			this.$store.dispatch('toggleEnvelopeSeen', { envelope: this.envelope })
		},
		async onDelete() {
			// Remove from selection first
			if (this.withSelect) {
				this.$emit('unselect')
			}

			// Delete
			this.$emit('delete', this.envelope.databaseId)

			logger.info(`deleting message ${this.envelope.databaseId}`)

			try {
				await this.$store.dispatch('deleteMessage', {
					id: this.envelope.databaseId,
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
		async onArchive() {
			// Remove from selection first
			if (this.withSelect) {
				this.$emit('unselect')
			}

			// Archive
			this.$emit('archive', this.envelope.databaseId)

			logger.info(`archiving message ${this.envelope.databaseId}`)

			try {
				await this.$store.dispatch('moveMessage', {
					id: this.envelope.databaseId,
					destMailboxId: this.account.archiveMailboxId,
				})
			} catch (error) {
				logger.error('could not archive message', error)
				return t('mail', 'Could not archive message')
			}
		},
	},
}
</script>

<style lang="scss" scoped>
	.sender {
		margin-left: 8px;
	}

	.right {
		display: flex;
		flex-direction: row;
		align-items: center;
		justify-content: flex-end;
		margin-left: 10px;
		height: 44px;

		.app-content-list-item-menu {
			margin-left: 4px;
		}

		.timestamp {
			margin-right: 10px;
			color: var(--color-text-maxcontrast);
			white-space: nowrap;
			margin-bottom: 0;
		}
	}
	.button {
		color: var(--color-main-background);
		&:not(.active):not(.primary) {
			display: none;

			&.primary {
				background-color: var(--color-primary);
				opacity: 1;
				margin-bottom: 0;

			}
		}
	}
	.subline {
		margin-left: 8px;
		color: var(--color-text-maxcontrast);
		cursor: default;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}

	.envelope {
		display: flex;
		flex-direction: column;
		border: 2px solid var(--color-border);
		border-radius: 16px;
		margin-left: 10px;
		margin-right: 10px;
		background-color: var(--color-main-background);
		padding-bottom: 28px;

		& + .envelope {
			margin-top: -28px;
		}

		&:last-of-type {
			margin-bottom: 10px;
			padding-bottom: 0;
		}
	}

	.envelope--header {
		position: relative;
		display: flex;
		align-items: center;
		padding: 10px;
		border-radius: var(--border-radius);
		min-height: 68px; /* prevents jumping between open/collapsed */
	}
	.left {
		flex-grow: 1;
		min-width: 0; /* https://css-tricks.com/flexbox-truncated-text/ */
	}
	.icon-important {
		:deep(path) {
			fill: #ffcc00;
			stroke: var(--color-main-background);
			cursor: pointer;
		}

		&.app-content-list-item-star {
			background-image: none;
			position: absolute;
			opacity: 1;
			width: 16px;
			height: 16px;
			margin-left: -1px;
			display: flex;
			top: 12px;

			&:hover,
			&:focus {
				opacity: 0.5;
			}
		}
	}
	.app-content-list-item-star.favorite-icon-style {
		display: inline-block;
		position: absolute;
		top: 10px;
		left: 36px;
		cursor: pointer;
		stroke: var(--color-main-background);
		stroke-width: 2;
		&:hover {
			opacity: .5;
		}
	}
	.app-content-list-item-star.junk-icon-style {
		display: inline-block;
		position: absolute;
		top: 10px;
		left: 36px;
		cursor: pointer;
		opacity: .2;
		&:hover {
			opacity: .1;
		}
	}
	.left:not(.seen) {
		font-weight: bold;
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
	.envelope--header.list-item-style {
		border-radius: 16px;
	}
	.junk-favorite-position-with-tag-subline {
		margin-bottom: 14px !important;
	}
	.junk-favorite-position {
		margin-bottom: 36px !important;
	}
</style>
