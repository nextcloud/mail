<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="envelope-bulk-actions">
		<transition name="multiselect-header">
			<div v-if="selectMode" key="multiselect-header" class="multiselect-header">
				<div class="action-buttons">
					<NcButton
						v-if="isAtLeastOneSelectedUnread"
						variant="tertiary"
						:title="n('mail', 'Mark {number} read', 'Mark {number} read', selection.length, { number: selection.length })"
						:disabled="updatingSelection"
						@click.prevent="markSelectedRead">
						<EmailRead :size="20" />
					</NcButton>

					<NcButton
						v-if="isAtLeastOneSelectedRead"
						variant="tertiary"
						:title="n('mail', 'Mark {number} unread', 'Mark {number} unread', selection.length, { number: selection.length })"
						:disabled="updatingSelection"
						@click.prevent="markSelectedUnread">
						<EmailUnread :size="20" />
					</NcButton>

					<NcButton
						v-if="isAtLeastOneSelectedUnimportant"
						variant="tertiary"
						:title="n('mail', 'Mark {number} as important', 'Mark {number} as important', selection.length, { number: selection.length })"
						:disabled="updatingSelection"
						@click.prevent="markSelectionImportant">
						<ImportantIcon :size="20" />
					</NcButton>

					<NcButton
						v-if="isAtLeastOneSelectedImportant"
						variant="tertiary"
						:title="n('mail', 'Mark {number} as unimportant', 'Mark {number} as unimportant', selection.length, { number: selection.length })"
						:disabled="updatingSelection"
						@click.prevent="markSelectionUnimportant">
						<ImportantOutlineIcon :size="20" />
					</NcButton>

					<NcButton
						v-if="isAtLeastOneSelectedFavorite"
						variant="tertiary"
						:title="n('mail', 'Unfavorite {number}', 'Unfavorite {number}', selection.length, { number: selection.length })"
						:disabled="updatingSelection"
						@click.prevent="unfavoriteAll">
						<IconUnFavorite :size="20" />
					</NcButton>

					<NcButton
						v-if="isAtLeastOneSelectedUnFavorite"
						variant="tertiary"
						:title="n('mail', 'Favorite {number}', 'Favorite {number}', selection.length, { number: selection.length })"
						:disabled="updatingSelection"
						@click.prevent="favoriteAll">
						<IconFavorite :size="20" />
					</NcButton>

					<NcButton
						variant="tertiary"
						:title="n('mail', 'Unselect {number}', 'Unselect {number}', selection.length, { number: selection.length })"
						:closeAfterClick="true"
						@click.prevent="unselectAll">
						<IconSelect :size="20" />
					</NcButton>
					<NcButton
						variant="tertiary"
						:title="n(
							'mail',
							'Delete {number} thread',
							'Delete {number} threads',
							selection.length,
							{ number: selection.length },
						)"
						:closeAfterClick="true"
						@click.prevent="deleteAllSelected">
						<IconDelete :size="20" />
					</NcButton>
				</div>

				<NcActions class="app-content-list-item-menu" menuAlign="right">
					<NcActionButton
						v-if="isAtLeastOneSelectedNotJunk"
						@click.prevent="markSelectionJunk">
						<template #icon>
							<AlertOctagonIcon :size="20" />
						</template>
						{{ n('mail', 'Mark {number} as spam', 'Mark {number} as spam', selection.length, { number: selection.length }) }}
					</NcActionButton>
					<NcActionButton
						v-if="isAtLeastOneSelectedJunk"
						@click.prevent="markSelectionNotJunk">
						<template #icon>
							<AlertOctagonIcon :size="20" />
						</template>
						{{ n('mail', 'Mark {number} as not spam', 'Mark {number} as not spam', selection.length, { number: selection.length }) }}
					</NcActionButton>
					<NcActionButton :closeAfterClick="true" @click.prevent="onOpenTagModal">
						<template #icon>
							<TagIcon :size="20" />
						</template>
						{{ n('mail', 'Edit tags for {number}', 'Edit tags for {number}', selection.length, { number: selection.length }) }}
					</NcActionButton>
					<NcActionButton v-if="!account.isUnified" :closeAfterClick="true" @click.prevent="onOpenMoveModal">
						<template #icon>
							<OpenInNewIcon :size="20" />
						</template>
						{{ n('mail', 'Move {number} thread', 'Move {number} threads', selection.length, { number: selection.length }) }}
					</NcActionButton>
					<NcActionButton :closeAfterClick="true" @click.prevent="forwardSelectedAsAttachment">
						<template #icon>
							<ShareIcon :size="20" />
						</template>
						{{ n('mail', 'Forward {number} as attachment', 'Forward {number} as attachment', selection.length, { number: selection.length }) }}
					</NcActionButton>
				</NcActions>
			</div>
		</transition>

		<TagModal
			v-if="showTagModal"
			:account="account"
			:envelopes="selectedEnvelopes"
			@close="onCloseTagModal" />

		<MoveModal
			v-if="showMoveModal"
			:account="account"
			:envelopes="selectedEnvelopes"
			:moveThread="true"
			@close="onCloseMoveModal" />
	</div>
</template>

<script>
import { showError } from '@nextcloud/dialogs'
import { mapStores } from 'pinia'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcButton from '@nextcloud/vue/components/NcButton'
import AlertOctagonIcon from 'vue-material-design-icons/AlertOctagonOutline.vue'
import IconSelect from 'vue-material-design-icons/CloseThick.vue'
import EmailRead from 'vue-material-design-icons/EmailOpenOutline.vue'
import EmailUnread from 'vue-material-design-icons/EmailOutline.vue'
import ImportantIcon from 'vue-material-design-icons/LabelVariant.vue'
import ImportantOutlineIcon from 'vue-material-design-icons/LabelVariantOutline.vue'
import OpenInNewIcon from 'vue-material-design-icons/OpenInNew.vue'
import ShareIcon from 'vue-material-design-icons/ShareOutline.vue'
import IconFavorite from 'vue-material-design-icons/Star.vue'
import IconUnFavorite from 'vue-material-design-icons/StarOutline.vue'
import TagIcon from 'vue-material-design-icons/TagOutline.vue'
import IconDelete from 'vue-material-design-icons/TrashCanOutline.vue'
import MoveModal from './MoveModal.vue'
import TagModal from './TagModal.vue'
import { matchError } from '../errors/match.js'
import NoTrashMailboxConfiguredError
	from '../errors/NoTrashMailboxConfiguredError.js'
import logger from '../logger.js'
import useMainStore from '../store/mainStore.js'

export default {
	name: 'EnvelopeBulkActions',
	components: {
		IconUnFavorite,
		EmailUnread,
		EmailRead,
		NcActions,
		NcButton,
		NcActionButton,
		IconDelete,
		ImportantIcon,
		ImportantOutlineIcon,
		IconFavorite,
		IconSelect,
		MoveModal,
		OpenInNewIcon,
		ShareIcon,
		AlertOctagonIcon,
		TagIcon,
		TagModal,
	},

	props: {
		account: {
			type: Object,
			required: true,
		},

		mailbox: {
			type: Object,
			required: true,
		},

		searchQuery: {
			type: String,
			required: false,
			default: undefined,
		},

		envelopes: {
			type: Array,
			required: true,
		},

		selection: {
			type: Array,
			required: true,
		},
	},

	emits: ['update:selection'],

	data() {
		return {
			showMoveModal: false,
			showTagModal: false,
			updatingSelection: false,
		}
	},

	computed: {
		...mapStores(useMainStore),
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
				return this.mainStore.isEnvelopeJunk(env)
			})
		},

		isAtLeastOneSelectedNotJunk() {
			// returns true if at least one selected message is not marked as not junk
			return this.selectedEnvelopes.some((env) => {
				return !this.mainStore.isEnvelopeJunk(env)
			})
		},

		isAtLeastOneSelectedFavorite() {
			return this.selectedEnvelopes.some((env) => env.flags.flagged)
		},

		isAtLeastOneSelectedUnFavorite() {
			return this.selectedEnvelopes.some((env) => !env.flags.flagged)
		},

		selectedEnvelopes() {
			return this.envelopes.filter((env) => this.selection.includes(env.databaseId))
		},
	},

	methods: {
		async applyToSelection(action) {
			if (this.updatingSelection) {
				return
			}

			this.updatingSelection = true
			try {
				for (const envelope of this.selectedEnvelopes) {
					await action(envelope)
				}
			} catch (error) {
				logger.error('could not update the selected messages', { error })
			} finally {
				this.updatingSelection = false
			}
			this.unselectAll()
		},

		async markSelectedRead() {
			await this.applyToSelection((envelope) => this.mainStore.toggleEnvelopeSeen({
				envelope,
				seen: true,
			}))
		},

		async markSelectedUnread() {
			await this.applyToSelection((envelope) => this.mainStore.toggleEnvelopeSeen({
				envelope,
				seen: false,
			}))
		},

		async markSelectionImportant() {
			await this.applyToSelection((envelope) => this.mainStore.markEnvelopeImportantOrUnimportant({
				envelope,
				addTag: true,
			}))
		},

		async markSelectionUnimportant() {
			await this.applyToSelection((envelope) => this.mainStore.markEnvelopeImportantOrUnimportant({
				envelope,
				addTag: false,
			}))
		},

		async markSelectionJunk() {
			for (const envelope of this.selectedEnvelopes) {
				if (!this.mainStore.isEnvelopeJunk(envelope)) {
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
				if (this.mainStore.isEnvelopeJunk(envelope)) {
					await this.mainStore.toggleEnvelopeJunk({
						envelope,
						removeEnvelope: await this.mainStore.moveEnvelopeToJunk(envelope),
					})
				}
			}
			this.unselectAll()
		},

		async unfavoriteAll() {
			await this.applyToSelection((envelope) => this.mainStore.markEnvelopeFavoriteOrUnfavorite({
				envelope,
				favFlag: false,
			}))
		},

		async favoriteAll() {
			await this.applyToSelection((envelope) => this.mainStore.markEnvelopeFavoriteOrUnfavorite({
				envelope,
				favFlag: true,
			}))
		},

		async deleteAllSelected() {
			let nextEnvelopeToNavigate
			let isAllSelected

			if (this.selectedEnvelopes.length === this.envelopes.length) {
				isAllSelected = true
			} else {
				const indexSelectedEnvelope = this.selectedEnvelopes.findIndex((selectedEnvelope) => selectedEnvelope.databaseId === parseInt(this.$route.params.threadId, 10))

				// one of threads is selected
				if (indexSelectedEnvelope !== -1) {
					const lastSelectedEnvelope = this.selectedEnvelopes[this.selectedEnvelopes.length - 1]
					const diff = this.envelopes.filter((envelope) => envelope === lastSelectedEnvelope || !this.selectedEnvelopes.includes(envelope))
					const lastIndex = diff.indexOf(lastSelectedEnvelope)
					nextEnvelopeToNavigate = diff[lastIndex === 0 ? 1 : lastIndex - 1]
				}
			}

			try {
				for (const envelope of this.selectedEnvelopes) {
					logger.info(`deleting thread ${envelope.threadRootId}`)
					await this.mainStore.deleteThread({
						envelope,
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

		unselectAll() {
			this.$emit('update:selection', [])
		},
	},
}
</script>

<style lang="scss" scoped>
.envelope-bulk-actions {
	position: sticky;
	top: 0;
	z-index: 100;
}

.multiselect-header {
	display: flex;
	flex-direction: row;
	align-items: center;
	justify-content: center;
	background-color: var(--color-main-background-translucent);
	height: 48px;
	.action-buttons {
		display: flex;
	}
}

.multiselect-header-enter-active,
.multiselect-header-leave-active {
	transition: all calc(var(--animation-slow) / 2);
}

.multiselect-header-enter-from,
.multiselect-header-leave-to {
	opacity: 0;
	height: 0;
	transform: scaleY(0);
}
</style>
