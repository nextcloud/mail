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
				:selectMode="selectMode"
				:hasMultipleAccounts="hasMultipleAccounts"
				:selectedEnvelopes="selectedEnvelopes"
				:compactMode="compactMode"
				:dateGrouped="dateGrouped"
				@delete="$emit('delete', env.databaseId)"
				@update:selected="onEnvelopeSelectToggle(env, $event)"
				@selectMultiple="onEnvelopeSelectMultiple(env, index)"
				@open:quickActionsSettings="showQuickActionsSettings = true" />
			<div
				v-if="loadMoreButton && !loadingMore"
				:key="'list-collapse-' + searchQuery"
				class="load-more"
				@click="$emit('loadMore')">
				<AddIcon :size="16" />
				{{ loadMoreLabel }}
			</div>
			<div id="load-more-mail-messages" key="loadingMore" :class="{ 'icon-loading-small': loadingMore }" />
		</transition-group>

		<NcDialog
			v-if="showQuickActionsSettings"
			:name="t('mail', 'Manage quick actions')"
			@closing="showQuickActionsSettings = false">
			<Settings :account="account" />
		</NcDialog>
	</div>
</template>

<script>
import { mapStores } from 'pinia'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import AddIcon from 'vue-material-design-icons/Plus.vue'
import Settings from '../components/quickActions/Settings.vue'
import Envelope from './Envelope.vue'
import useMainStore from '../store/mainStore.js'
import { sortEnvelopes } from '../util/sortEnvelopes.js'

export default {
	name: 'EnvelopeList',
	components: {
		AddIcon,
		NcDialog,
		Envelope,
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

		compactMode: {
			type: Boolean,
			default: false,
		},

		dateGrouped: {
			type: Boolean,
			default: false,
		},

		selection: {
			type: Array,
			default: () => [],
		},

		flatIndex: {
			type: Number,
			default: 0,
		},
	},

	emits: ['delete', 'loadMore', 'select', 'selectRange'],

	data() {
		return {
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
			return sortEnvelopes(this.envelopes, this.sortOrder)
		},

		selectMode() {
			// returns true when in selection mode (where the user selects several emails at once)
			return this.selection.length > 0
		},

		selectedEnvelopes() {
			return this.selection
				.map((id) => this.mainStore.getEnvelope(id))
				.filter((envelope) => envelope !== undefined)
		},

		hasMultipleAccounts() {
			const mailboxIds = this.sortedEnvelops.map((envelope) => envelope.mailboxId)
			return Array.from(new Set(mailboxIds)).length > 1
		},

		listTransitionName() {
			return this.skipTransition ? 'disabled' : 'list'
		},
	},

	methods: {
		isEnvelopeSelected(idx) {
			if (this.selection.length === 0) {
				return false
			}

			return this.selection.includes(idx)
		},

		onEnvelopeSelectToggle(envelope, selected) {
			this.$emit('select', envelope.databaseId, selected)
		},

		onEnvelopeSelectMultiple(envelope, index) {
			const deselect = this.selection.includes(envelope.databaseId)
			this.$emit('selectRange', this.flatIndex + index, deselect)
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

#load-more-mail-messages {
	background-position: 9px center;
}

.list-enter-active,
.list-leave-active {
	transition: all calc(var(--animation-slow) / 2);
}

.list-enter-from,
.list-leave-to {
	opacity: 0;
	height: 0;
	transform: scaleY(0);
}
</style>
