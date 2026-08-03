<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcSelect
		:model-value="modelValue"
		:options="mailboxOptions"
		:clearable="false"
		:searchable="true"
		:disabled="disabled"
		label="label"
		:reduce="option => option.id"
		@update:model-value="$emit('update:modelValue', $event)" />
</template>

<script>
import { NcSelect } from '@nextcloud/vue'
import { mapStores } from 'pinia'
import useMainStore from '../store/mainStore.js'
import { mailboxHasRights } from '../util/acl.js'

export default {
	name: 'MailboxInlinePicker',
	components: {
		NcSelect,
	},

	props: {
		account: {
			type: Object,
			required: true,
		},

		disabled: {
			type: Boolean,
			default: false,
		},

		modelValue: {
			type: Number,
			default: undefined,
		},
	},

	emits: ['update:modelValue'],

	computed: {
		...mapStores(useMainStore),
		mailboxOptions() {
			return this.flattenMailboxes()
		},
	},

	methods: {
		flattenMailboxes(mailboxId, depth = 0) {
			const mailboxes = mailboxId
				? this.mainStore.getSubMailboxes(mailboxId)
				: this.mainStore.getMailboxes(this.account.accountId)
			return mailboxes
				.filter((mailbox) => mailboxHasRights(mailbox, 'i'))
				.flatMap((mailbox) => [
					{
						id: mailbox.databaseId,
						label: '  '.repeat(depth) + mailbox.displayName,
					},
					...this.flattenMailboxes(mailbox.databaseId, depth + 1),
				])
		},
	},
}
</script>
