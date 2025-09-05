<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="action">
		<p v-if="!needsSelection">
			{{ action.name }}
		</p>
		<NcSelect v-else
			:input-label="action.name"
			:options="options"
			label="value"
			:model-value="selectedOption"
			@update:modelValue="update" />
		<NcButton aria-label="delete" variant="tertiary-no-background" @click="$emit('delete')">
			<template #icon>
				<CloseIcon :size="20" />
			</template>
		</NcButton>
	</div>
</template>

<script>
import useMainStore from '../../store/mainStore.js'
import { NcSelect, NcButton } from '@nextcloud/vue'
import { hiddenTags } from '../tags.js'
import CloseIcon from 'vue-material-design-icons/Close.vue'

export default {
	name: 'Action',
	components: {
		NcSelect,
		CloseIcon,
		NcButton,
	},
	props: {
		action: {
			type: Object,
			required: true,
		},
		account: {
			type: Object,
			required: true,
		},
	},
	computed: {
		mainStore() {
			return useMainStore()
		},
		needsSelection() {
			return ['applyTag', 'moveThread'].includes(this.action.name)
		},
		selectedOption() {
			if (this.action.name === 'applyTag') {
				return this.options.find(option => option.id === this.action.tagId) || null
			} else if (this.action.name === 'moveThread') {
				return this.options.find(option => option.id === this.action.mailboxId) || null
			}
			return null
		},
		options() {
			if (this.action.name === 'applyTag') {
				return this.mainStore.getTags.filter((tag) => tag.imapLabel !== '$label1' && !(tag.displayName.toLowerCase() in hiddenTags)).map((tag) => ({
					value: tag.displayName,
					id: tag.id,
				}))
			}
			if (this.action.name === 'moveThread') {
				return this.mainStore.getMailboxes(this.account.accountId).map((mailbox) => ({
					value: mailbox.displayName,
					id: mailbox.databaseId,
				}))
			}
			return []
		},
	},
	methods: {
		update(value) {
			this.$emit('update', { id: value.id, type: this.action.name })
		},
	},
}
</script>
<style scoped>
.action {
	display: flex;
	width: 100%;
	justify-content: space-between;
	align-items: center;
}
</style>
