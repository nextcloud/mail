<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcSelect
		:input-label="label"
		:options="mailboxes"
		label="value"
		:model-value="selectedOption"
		:disabled="disabled"
		@update:modelValue="update" />
</template>

<script>
import { NcSelect } from '@nextcloud/vue'
import useMainStore from '../store/mainStore.js'

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

		label: {
			type: String,
			default: '',
		},

		value: {
			type: Number,
			default: undefined,
		},

		disabled: {
			type: Boolean,
			default: false,
		},
	},

	computed: {
		mainStore() {
			return useMainStore()
		},

		mailboxes() {
			return this.getMailboxes()
		},

		selectedOption() {
			return this.mailboxes.find((option) => option.id === this.value) || null
		},
	},

	watch: {
		selected(val) {
			if (val !== this.value) {
				this.$emit('input', val)
				this.selected = val
			}
		},
	},

	methods: {
		getMailboxes() {
			const mailboxes = []
			for (const mailbox of this.mainStore.getRecursiveMailboxIterator(this.account.accountId)) {
				mailboxes.push({
					value: mailbox.name,
					id: mailbox.databaseId,
				})
			}
			return mailboxes
		},

		update(value) {
			this.$emit('update', value ? value.id : 0)
		},
	},
}
</script>
