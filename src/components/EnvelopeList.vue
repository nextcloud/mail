<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div>
		<transition name="multiselect-header">
			<div v-if="selectMode" key="multiselect-header" class="multiselect-header">
				<div class="action-buttons">
					<NcButton v-if="isAtLeastOneSelectedUnread"
						type="tertiary"
						:title="n('mail', 'Mark {number} read', 'Mark {number} read', selection.length, { number: selection.length })"
						@click.prevent="markSelectedRead">
						<EmailRead :size="20" />
					</NcButton>

					<NcButton v-if="isAtLeastOneSelectedRead"
						type="tertiary"
						:title="n('mail', 'Mark {number} unread', 'Mark {number} unread', selection.length, { number: selection.length })"
						@click.prevent="markSelectedUnread">
						<EmailUnread :size="20" />
					</NcButton>

					<NcButton v-if="isAtLeastOneSelectedUnimportant"
						type="tertiary"
						:title="n('mail', 'Mark {number} as important', 'Mark {number} as important', selection.length, { number: selection.length })"
						@click.prevent="markSelectionImportant">
						<ImportantIcon :size="20" />
					</NcButton>

					<NcButton v-if="isAtLeastOneSelectedImportant"
						type="tertiary"
						:title="n('mail', 'Mark {number} as unimportant', 'Mark {number} as unimportant', selection.length, { number: selection.length })"
						@click.prevent="markSelectionUnimportant">
						<ImportantOutlineIcon :size="20" />
					</NcButton>

					<NcButton v-if="isAtLeastOneSelectedFavorite"
						type="tertiary"
						:title="n('mail', 'Unfavorite {number}', 'Unfavorite {number}', selection.length, { number: selection.length })"
						@click.prevent="favoriteAll">
						<IconUnFavorite :size="20" />
					</NcButton>

					<NcButton v-if="isAtLeastOneSelectedUnFavorite"
						type="tertiary"
						:title="n('mail', 'Favorite {number}', 'Favorite {number}', selection.length, { number: selection.length })"
						@click.prevent="unFavoriteAll">
						<IconFavorite :size="20" />
					</NcButton>

					<NcButton type="tertiary"
						:title="n('mail', 'Unselect {number}', 'Unselect {number}', selection.length, { number: selection.length })"
						:close-after-click="true"
						@click.prevent="unselectAll">
						<IconSelect :size="20" />
					</NcButton>
					<NcButton type="tertiary"
						:title="n(
							'mail',
							'Delete {number} thread',
							'Delete {number} threads',
							selection.length,
							{ number: selection.length }
						)"
						:close-after-click="true"
						@click.prevent="deleteAllSelected">
						<IconDelete :size="20" />
					</NcButton>
				</div>

				<Actions class="app-content-list-item-menu" menu-align="right">
					<ActionButton v-if="isAtLeastOneSelectedNotJunk"
						@click.prevent="markSelectionJunk">
						<template #icon>
							<AlertOctagonIcon :size="20" />
						</template>
						{{ n('mail', 'Mark {number} as spam', 'Mark {number} as spam', selection.length, { number: selection.length }) }}
					</ActionButton>
					<ActionButton v-if="isAtLeastOneSelectedJunk"
						@click.prevent="markSelectionNotJunk">
						<template #icon>
							<AlertOctagonIcon :size="20" />
						</template>
						{{ n('mail', 'Mark {number} as not spam', 'Mark {number} as not spam', selection.length, { number: selection.length }) }}
					</ActionButton>
					<ActionButton :close-after-click="true" @click.prevent="onOpenTagModal">
						<template #icon>
							<TagIcon :size="20" />
						</template>
						{{ n('mail', 'Edit tags for {number}', 'Edit tags for {number}', selection.length, { number: selection.length }) }}
					</ActionButton>
					<ActionButton v-if="!account.isUnified" :close-after-click="true" @click.prevent="onOpenMoveModal">
						<template #icon>
							<OpenInNewIcon :size="20" />
						</template>
						{{ n('mail', 'Move {number} thread', 'Move {number} threads', selection.length, { number: selection.length }) }}
					</ActionButton>
					<ActionButton :close-after-click="true" @click.prevent="forwardSelectedAsAttachment">
						<template #icon>
							<ShareIcon :size="20" />
						</template>
						{{ n('mail', 'Forward {number} as attachment', 'Forward {number} as attachment', selection.length, { number: selection.length }) }}
					</ActionButton>
				</Actions>
				<MoveModal v-if="showMoveModal"
					:account="account"
					:envelopes="selectedEnvelopes"
					:move-thread="true"
					@close="onCloseMoveModal" />
			</div>
		</transition>

		<transition-group :name="listTransitionName">
			<Envelope v-for="(env, index) in sortedEnvelops"
				:key="env.databaseId"
				:data="env"
				:mailbox="mailbox"
				:selected="selection.includes(env.databaseId)"
				:select-mode="selectMode"
				:has-multiple-accounts="hasMultipleAccounts"
				:selected-envelopes="selectedEnvelopes"
				@delete="$emit('delete', env.databaseId)"
				@update:selected="onEnvelopeSelectToggle(env, index, $event)"
				@select-multiple="onEnvelopeSelectMultiple(env, index)"
				@open:quick-actions-settings="showQuickActionsSettings = true" />
			<div v-if="loadMoreButton && !loadingMore"
				:key="'list-collapse-' + searchQuery"
				class="load-more"
				@click="$emit('load-more')">
				<AddIcon :size="16" />
				{{ loadMoreLabel }}
			</div>
			<div id="load-more-mail-messages" key="loadingMore" :class="{'icon-loading-small': loadingMore}" />
		</transition-group>

		<TagModal v-if="showTagModal"
			:account="account"
			:envelopes="selectedEnvelopes"
			@close="onCloseTagModal" />

		<NcDialog v-if="showQuickActionsSettings"
			:name="t('mail', 'Manage quick actions')"
			@closing="showQuickActionsSettings = false">
			<Settings :account="account" />
		</NcDialog>
	</div>
</template>

<script>
import { NcActions as Actions, NcActionButton as ActionButton, NcButton, NcDialog } from '@nextcloud/vue'
import { showError } from '@nextcloud/dialogs'
import Envelope from './Envelope.vue'
import IconDelete from 'vue-material-design-icons/TrashCanOutline.vue'
import ImportantOutlineIcon from 'vue-material-design-icons/LabelVariantOutline.vue'
import IconUnFavorite from 'vue-material-design-icons/StarOutline.vue'
import IconSelect from 'vue-material-design-icons/CloseThick.vue'
import AddIcon from 'vue-material-design-icons/Plus.vue'
import IconFavorite from 'vue-material-design-icons/Star.vue'
import logger from '../logger.js'
import MoveModal from './MoveModal.vue'
import { matchError } from '../errors/match.js'
import NoTrashMailboxConfiguredError
	from '../errors/NoTrashMailboxConfiguredError.js'
import { differenceWith } from 'ramda'
import dragEventBus from '../directives/drag-and-drop/util/dragEventBus.js'
import OpenInNewIcon from 'vue-material-design-icons/OpenInNew.vue'
import ShareIcon from 'vue-material-design-icons/ShareOutline.vue'
import AlertOctagonIcon from 'vue-material-design-icons/AlertOctagonOutline.vue'
import TagIcon from 'vue-material-design-icons/TagOutline.vue'
import TagModal from './TagModal.vue'
import Settings from '../components/quickActions/Settings.vue'
import EmailRead from 'vue-material-design-icons/EmailOpenOutline.vue'
import EmailUnread from 'vue-material-design-icons/EmailOutline.vue'
import useMainStore from '../store/mainStore.js'
import { mapStores } from 'pinia'

export default {
	name: 'EnvelopeList',
	components: {
		IconUnFavorite,
		EmailUnread,
		EmailRead,
		Actions,
		AddIcon,
		NcButton,
		NcDialog,
		ActionButton,
		Envelope,
		IconDelete,
		ImportantOutlineIcon,
		IconFavorite,
		IconSelect,
		MoveModal,
		OpenInNewIcon,
		ShareIcon,
		AlertOctagonIcon,
		TagIcon,
		TagModal,
		Settings,
	},
	props: {
		account: {
			type: Object,
			required: true,
		},
		loadMoreLabel: {
			type: String,
			default: t('mail', 'Load more'),
		},
		mailbox: {
			type: Object,
			required: true,
		},
		envelopes: {
			type: Array,
			required: true,
		},
		searchQuery: {
			type: String,
			required: false,
			default: undefined,
		},
		loadingMore: {
			type: Boolean,
			required: true,
		},
		loadMoreButton: {
			type: Boolean,
			required: false,
			default: false,
		},
		skipTransition: {
			type: Boolean,
			default: false,
		},
	},
	data() {
		return {
			selection: [],
			showMoveModal: false,
			showTagModal: false,
			lastToggledIndex: undefined,
			defaultView: false,
			showQuickActionsSettings: false,
		}
	},
	computed: {
		...mapStores(useMainStore),
		sortOrder() {
			return this.mainStore.getPreference('sort-order', 'newest')
		},
		sortedEnvelops() {
			if (this.sortOrder === 'oldest') {
				return [...this.envelopes].sort((a, b) => {
					return a.dateInt < b.dateInt ? -1 : 1
				})
			}
			return [...this.envelopes]
		},
		selectMode() {
			// returns true when in selection mode (where the user selects several emails at once)
			return this.selection.length > 0
		},
		isAtLeastOneSelectedRead() {
			return this.selectedEnvelopes.some((env) => env.flags.seen === true)
		},
		isAtLeastOneSelectedUnread() {
			return this.selectedEnvelopes.some((env) => env.flags.seen === false)
		},
		isAtLeastOneSelectedImportant() {
			// returns true if at least one selected message is marked as important
			return this.selectedEnvelopes.some((env) => {
				return this.mainStore
					.getEnvelopeTags(env.databaseId)
					.some((tag) => tag.imapLabel === '$label1')
			})
		},
		isAtLeastOneSelectedUnimportant() {
			// returns true if at least one selected message is not marked as important
			return this.selectedEnvelopes.some((env) => {
				return !this.mainStore
					.getEnvelopeTags(env.databaseId)
					.some((tag) => tag.imapLabel === '$label1')
			})
		},
		isAtLeastOneSelectedJunk() {
			// returns true if at least one selected message is marked as junk
			return this.selectedEnvelopes.some((env) => {
				return env.flags.$junk
			})
		},
		isAtLeastOneSelectedNotJunk() {
			// returns true if at least one selected message is not marked as not junk
			return this.selectedEnvelopes.some((env) => {
				return !env.flags.$junk
			})
		},
		isAtLeastOneSelectedFavorite() {
			return this.selectedEnvelopes.some((env) => env.flags.flagged)
		},
		isAtLeastOneSelectedUnFavorite() {
			return this.selectedEnvelopes.some((env) => !env.flags.flagged)
		},
		selectedEnvelopes() {
			return this.sortedEnvelops.filter((env) => this.selection.includes(env.databaseId))
		},
		hasMultipleAccounts() {
			const mailboxIds = this.sortedEnvelops.map(envelope => envelope.mailboxId)
			return Array.from(new Set(mailboxIds)).length > 1
		},
		listTransitionName() {
			return this.skipTransition ? 'disabled' : 'list'
		},
	},
	watch: {
		sortedEnvelops(newVal, oldVal) {
			// Unselect vanished envelopes
			const newIds = newVal.map((env) => env.databaseId)
			this.selection = this.selection.filter((id) => newIds.includes(id))
			differenceWith((a, b) => a.databaseId === b.databaseId, oldVal, newVal)
				.forEach((env) => {
					env.flags.selected = false
				})
		},
	},
	mounted() {
		dragEventBus.on('envelopes-dropped', this.unselectAll)
	},
	beforeDestroy() {
		dragEventBus.off('envelopes-dropped', this.unselectAll)
	},
	methods: {
		isEnvelopeSelected(idx) {
			if (this.selection.length === 0) {
				return false
			}

			return this.selection.includes(idx)
		},
		markSelectedRead() {
			this.selectedEnvelopes.forEach((envelope) => {
				this.mainStore.toggleEnvelopeSeen({
					envelope,
					seen: true,
				})
			})
			this.unselectAll()
		},
		markSelectedUnread() {
			this.selectedEnvelopes.forEach((envelope) => {
				this.mainStore.toggleEnvelopeSeen({
					envelope,
					seen: false,
				})
			})
			this.unselectAll()
		},
		markSelectionImportant() {
			this.selectedEnvelopes.forEach((envelope) => {
				this.mainStore.markEnvelopeImportantOrUnimportant({
					envelope,
					addTag: true,
				})
			})
			this.unselectAll()
		},
		markSelectionUnimportant() {
			this.selectedEnvelopes.forEach((envelope) => {
				this.mainStore.markEnvelopeImportantOrUnimportant({
					envelope,
					addTag: false,
				})
			})
			this.unselectAll()
		},
		async markSelectionJunk() {
			for (const envelope of this.selectedEnvelopes) {
				if (!envelope.flags.$junk) {
					await this.mainStore.toggleEnvelopeJunk({
						envelope,
						removeEnvelope: await this.mainStore.moveEnvelopeToJunk(envelope),
					})
				}
			}
			this.unselectAll()
		},
		async markSelectionNotJunk() {
			for (const envelope of this.selectedEnvelopes) {
				if (envelope.flags.$junk) {
					await this.mainStore.toggleEnvelopeJunk({
						envelope,
						removeEnvelope: await this.mainStore.moveEnvelopeToJunk(envelope),
					})
				}
			}
			this.unselectAll()
		},
		favoriteAll() {
			const favFlag = !this.isAtLeastOneSelectedUnFavorite
			this.selectedEnvelopes.forEach((envelope) => {
				this.mainStore.markEnvelopeFavoriteOrUnfavorite({
					envelope,
					favFlag,
				})
			})
			this.unselectAll()
		},
		unFavoriteAll() {
			const favFlag = !this.isAtLeastOneSelectedFavorite
			this.selectedEnvelopes.forEach((envelope) => {
				this.mainStore.markEnvelopeFavoriteOrUnfavorite({
					envelope,
					favFlag,
				})
			})
			this.unselectAll()
		},
		async deleteAllSelected() {
			let nextEnvelopeToNavigate
			let isAllSelected

			if (this.selectedEnvelopes.length === this.sortedEnvelops.length) {
				isAllSelected = true
			} else {
				const indexSelectedEnvelope = this.selectedEnvelopes.findIndex((selectedEnvelope) =>
					selectedEnvelope.databaseId === this.$route.params.threadId)

				// one of threads is selected
				if (indexSelectedEnvelope !== -1) {
					const lastSelectedEnvelope = this.selectedEnvelopes[this.selectedEnvelopes.length - 1]
					const diff = this.sortedEnvelops.filter(envelope => envelope === lastSelectedEnvelope || !this.selectedEnvelopes.includes(envelope))
					const lastIndex = diff.indexOf(lastSelectedEnvelope)
					nextEnvelopeToNavigate = diff[lastIndex === 0 ? 1 : lastIndex - 1]
				}
			}

			await Promise.all(this.selectedEnvelopes.map(async (envelope) => {
				logger.info(`deleting thread ${envelope.threadRootId}`)
				await this.mainStore.deleteThread({
					envelope,
				})
			})).catch(async error => {
				showError(await matchError(error, {
					[NoTrashMailboxConfiguredError.getName()]() {
						return t('mail', 'No trash folder configured')
					},
					default(error) {
						logger.error('could not delete message', error)
						return t('mail', 'Could not delete message')
					},
				}))
			})
			if (nextEnvelopeToNavigate) {
				await this.$router.push({
					name: 'message',
					params: {
						mailboxId: this.$route.params.mailboxId,
						threadId: nextEnvelopeToNavigate.databaseId,
					},
				})

				// Get new messages
				await this.mainStore.fetchNextEnvelopes({
					mailboxId: this.mailbox.databaseId,
					query: this.searchQuery,
					quantity: this.selectedEnvelopes.length,
				})
			} else if (isAllSelected) {
				await this.$router.push({
					name: 'mailbox',
					params: {
						mailboxId: this.$route.params.mailboxId,
					},
				})
			}
			this.unselectAll()
		},
		setEnvelopeSelected(envelope, selected) {
			const alreadySelected = this.selection.includes(envelope.databaseId)
			if (selected && !alreadySelected) {
				envelope.flags.selected = true
				this.selection.push(envelope.databaseId)
			} else if (!selected && alreadySelected) {
				envelope.flags.selected = false
				this.selection.splice(this.selection.indexOf(envelope.databaseId), 1)
			}
		},
		onEnvelopeSelectToggle(envelope, index, selected) {
			this.lastToggledIndex = index
			this.setEnvelopeSelected(envelope, selected)
		},
		onEnvelopeSelectMultiple(envelope, index) {
			const lastToggledIndex = this.lastToggledIndex
				?? this.findSelectionIndex(parseInt(this.$route.params.threadId))
				?? undefined
			if (lastToggledIndex === undefined) {
				return
			}

			const start = Math.min(lastToggledIndex, index)
			const end = Math.max(lastToggledIndex, index)
			const selected = this.selection.includes(envelope.databaseId)
			for (let i = start; i <= end; i++) {
				this.setEnvelopeSelected(this.sortedEnvelops[i], !selected)
			}
			this.lastToggledIndex = index
		},
		unselectAll() {
			this.sortedEnvelops.forEach((env) => {
				env.flags.selected = false
			})
			this.selection = []
		},
		onOpenMoveModal() {
			this.showMoveModal = true
		},
		onOpenTagModal() {
			this.showTagModal = true
		},
		onCloseTagModal() {
			this.showTagModal = false
		},
		async forwardSelectedAsAttachment() {
			await this.mainStore.startComposerSession({
				forwardedMessages: [...this.selection],
			})
			this.unselectAll()
		},
		onCloseMoveModal() {
			this.showMoveModal = false
			this.unselectAll()
		},
		/**
		 * Find the envelope list index of a given envelope's database id.
		 *
		 * @param {number} databaseId of the given envelope
		 * @return {number|undefined} Index or undefined if not found in the envelope list
		 */
		findSelectionIndex(databaseId) {
			for (const [index, envelope] of this.sortedEnvelops.entries()) {
				if (envelope.databaseId === databaseId) {
					return index
				}
			}

			return undefined
		},
	},
}
</script>

<style lang="scss" scoped>
div {
	// So we can align the loading spinner in the Priority inbox
	position: relative;
}

.load-more {
	text-align: center;
	margin-top: 10px;
	cursor: pointer;
	margin-inline-start: 28px;
	color: var(--color-text-maxcontrast);
	display: inline-flex;
	gap: 12px;
	.plus-icon{
		transform: translateX(-8px);
	}
}

.multiselect-header {
	display: flex;
	flex-direction: row;
	align-items: center;
	justify-content: center;
	background-color: var(--color-main-background-translucent);
	position: sticky;
	top: 0;
	height: 48px;
	z-index: 100;
	.action-buttons {
		display: flex;
	}
}

#load-more-mail-messages {
	background-position: 9px center;
}

.multiselect-header-enter-active,
.multiselect-header-leave-active,
.list-enter-active,
.list-leave-active {
	transition: all calc(var(--animation-slow) / 2);
}

.multiselect-header-enter,
.multiselect-header-leave-to,
.list-enter,
.list-leave-to {
	opacity: 0;
	height: 0;
	transform: scaleY(0);
}

#action-label {
	vertical-align: middle;
}
@media only screen and (min-width: 600px) {
	#action-label {
		display: block;
	}
}

:deep(.button-vue--text-only) {
	padding: 0 !important;
}
</style>
