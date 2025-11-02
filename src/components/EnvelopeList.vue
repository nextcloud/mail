<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div>
		<transition name="multiselect-header">
			<div v-if="selectMode" key="multiselect-header" class="multiselect-header">
				<div class="action-buttons">
					<NcButton
						v-if="isAtLeastOneSelectedUnread"
						variant="tertiary"
						:title="n('mail', 'Mark {number} read', 'Mark {number} read', selection.length, { number: selection.length })"
						@click.prevent="markSelectedRead">
						<EmailRead :size="20" />
					</NcButton>

					<NcButton
						v-if="isAtLeastOneSelectedRead"
						variant="tertiary"
						:title="n('mail', 'Mark {number} unread', 'Mark {number} unread', selection.length, { number: selection.length })"
						@click.prevent="markSelectedUnread">
						<EmailUnread :size="20" />
					</NcButton>

					<NcButton
						v-if="isAtLeastOneSelectedUnimportant"
						variant="tertiary"
						:title="n('mail', 'Mark {number} as important', 'Mark {number} as important', selection.length, { number: selection.length })"
						@click.prevent="markSelectionImportant">
						<ImportantIcon :size="20" />
					</NcButton>

					<NcButton
						v-if="isAtLeastOneSelectedImportant"
						variant="tertiary"
						:title="n('mail', 'Mark {number} as unimportant', 'Mark {number} as unimportant', selection.length, { number: selection.length })"
						@click.prevent="markSelectionUnimportant">
						<ImportantOutlineIcon :size="20" />
					</NcButton>

					<NcButton
						v-if="isAtLeastOneSelectedFavorite"
						variant="tertiary"
						:title="n('mail', 'Unfavorite {number}', 'Unfavorite {number}', selection.length, { number: selection.length })"
						@click.prevent="favoriteAll">
						<IconUnFavorite :size="20" />
					</NcButton>

					<NcButton
						v-if="isAtLeastOneSelectedUnFavorite"
						variant="tertiary"
						:title="n('mail', 'Favorite {number}', 'Favorite {number}', selection.length, { number: selection.length })"
						@click.prevent="unFavoriteAll">
						<IconFavorite :size="20" />
					</NcButton>

					<NcButton
						variant="tertiary"
						:title="n('mail', 'Unselect {number}', 'Unselect {number}', selection.length, { number: selection.length })"
						:close-after-click="true"
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
						:close-after-click="true"
						@click.prevent="deleteAllSelected">
						<IconDelete :size="20" />
					</NcButton>
				</div>

				<Actions class="app-content-list-item-menu" menu-align="right">
					<ActionButton
						v-if="isAtLeastOneSelectedNotJunk"
						@click.prevent="markSelectionJunk">
						<template #icon>
							<AlertOctagonIcon :size="20" />
						</template>
						{{ n('mail', 'Mark {number} as spam', 'Mark {number} as spam', selection.length, { number: selection.length }) }}
					</ActionButton>
					<ActionButton
						v-if="isAtLeastOneSelectedJunk"
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
				<MoveModal
					v-if="showMoveModal"
					:account="account"
					:envelopes="selectedEnvelopes"
					:move-thread="true"
					@close="onCloseMoveModal" />
			</div>
		</transition>

		<transition-group :name="listTransitionName">
			<Envelope
				v-for="(env, index) in sortedEnvelops"
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
			<div
				v-if="loadMoreButton && !loadingMore"
				:key="'list-collapse-' + searchQuery"
				class="load-more"
				@click="$emit('load-more')">
				<AddIcon :size="16" />
				{{ loadMoreLabel }}
			</div>
			<div id="load-more-mail-messages" key="loadingMore" :class="{ 'icon-loading-small': loadingMore }" />
		</transition-group>

		<TagModal
			v-if="showTagModal"
			:account="account"
			:envelopes="selectedEnvelopes"
			@close="onCloseTagModal" />

		<NcDialog
			v-if="showQuickActionsSettings"
			:name="t('mail', 'Manage quick actions')"
			@closing="showQuickActionsSettings = false">
			<Settings :account="account" />
		</NcDialog>
	</div>
</template>

<script>
import { showError } from '@nextcloud/dialogs'
import { NcActionButton as ActionButton, NcActions as Actions, NcButton, NcDialog } from '@nextcloud/vue'
import { mapStores } from 'pinia'
import { differenceWith } from 'ramda'
import AlertOctagonIcon from 'vue-material-design-icons/AlertOctagonOutline.vue'
import IconSelect from 'vue-material-design-icons/CloseThick.vue'
import EmailRead from 'vue-material-design-icons/EmailOpenOutline.vue'
import EmailUnread from 'vue-material-design-icons/EmailOutline.vue'
import ImportantOutlineIcon from 'vue-material-design-icons/LabelVariantOutline.vue'
import OpenInNewIcon from 'vue-material-design-icons/OpenInNew.vue'
import AddIcon from 'vue-material-design-icons/Plus.vue'
import ShareIcon from 'vue-material-design-icons/ShareOutline.vue'
import IconFavorite from 'vue-material-design-icons/Star.vue'
import IconUnFavorite from 'vue-material-design-icons/StarOutline.vue'
import TagIcon from 'vue-material-design-icons/TagOutline.vue'
import IconDelete from 'vue-material-design-icons/TrashCanOutline.vue'
import Settings from '../components/quickActions/Settings.vue'
import Envelope from './Envelope.vue'
import MoveModal from './MoveModal.vue'
import TagModal from './TagModal.vue'
import dragEventBus from '../directives/drag-and-drop/util/dragEventBus.js'
import { matchError } from '../errors/match.js'
import NoTrashMailboxConfiguredError
	from '../errors/NoTrashMailboxConfiguredError.js'
import logger from '../logger.js'

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

		}
	},

	computed: {

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
