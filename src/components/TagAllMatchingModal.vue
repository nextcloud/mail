<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcDialog :name="t('mail', 'Edit tags of all selected messages')" @closing="$emit('close')">
		<ul class="tag-list">
			<li v-for="tag in tags" :key="tag.id" class="tag-list__item">
				<span class="tag-list__bullet" :style="{ backgroundColor: tag.color }" />
				<span class="tag-list__name">{{ translateTagDisplayName(tag) }}</span>
				<NcButton
					variant="secondary"
					:aria-label="t('mail', 'Add {tag} to all selected messages', { tag: translateTagDisplayName(tag) })"
					:disabled="busy"
					@click="$emit('tag', { imapLabel: tag.imapLabel, value: true })">
					{{ t('mail', 'Add') }}
				</NcButton>
				<NcButton
					variant="tertiary"
					:aria-label="t('mail', 'Remove {tag} from all selected messages', { tag: translateTagDisplayName(tag) })"
					:disabled="busy"
					@click="$emit('tag', { imapLabel: tag.imapLabel, value: false })">
					{{ t('mail', 'Remove') }}
				</NcButton>
			</li>
		</ul>
	</NcDialog>
</template>

<script>
import { NcButton, NcDialog } from '@nextcloud/vue'
import { mapStores } from 'pinia'
import useMainStore from '../store/mainStore.js'
import { translateTagDisplayName } from '../util/tag.js'
import { hiddenTags } from './tags.js'

export default {
	name: 'TagAllMatchingModal',
	components: {
		NcButton,
		NcDialog,
	},

	props: {
		busy: {
			type: Boolean,
			default: false,
		},
	},

	computed: {
		...mapStores(useMainStore),

		tags() {
			return this.mainStore.getTags
				.filter((tag) => tag.imapLabel !== '$label1' && !(tag.displayName.toLowerCase() in hiddenTags))
		},
	},

	methods: {
		translateTagDisplayName,
	},
}
</script>

<style lang="scss" scoped>
.tag-list {
	display: flex;
	flex-direction: column;
	gap: var(--default-grid-baseline);

	&__item {
		display: flex;
		align-items: center;
		gap: calc(2 * var(--default-grid-baseline));
	}

	&__bullet {
		flex-shrink: 0;
		width: calc(3 * var(--default-grid-baseline));
		height: calc(3 * var(--default-grid-baseline));
		border-radius: 50%;
	}

	&__name {
		flex-grow: 1;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}
}
</style>
