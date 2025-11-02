<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div>
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
import useMainStore from '../store/mainStore.js'

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

		selectedEnvelopes() {
			return this.sortedEnvelops.filter((env) => this.selection.includes(env.databaseId))
		},

		hasMultipleAccounts() {
			const mailboxIds = this.sortedEnvelops.map((envelope) => envelope.mailboxId)
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

		onCloseTagModal() {
			this.showTagModal = false
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
