<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcSelect
		:model-value="value"
		:options="mailboxes"
		:reduce="(option) => option.id"
		:clearable="false"
		:disabled="disabled"
		:aria-label-combobox="t('mail', 'Select a mailbox')"
		label="label"
		@update:model-value="$emit('input', $event)">
		<template #option="option">
			<NcEllipsisedOption
				class="mailbox-option"
				:style="{ '--mailbox-depth': option.depth }"
				:name="option.label" />
		</template>
	</NcSelect>
</template>

<script>
import { NcEllipsisedOption, NcSelect } from '@nextcloud/vue'
import { mapStores } from 'pinia'
import useMainStore from '../store/mainStore.js'
import { mailboxHasRights } from '../util/acl.js'

export default {
	name: 'MailboxInlinePicker',
	components: {
		NcEllipsisedOption,
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

		value: {
			type: Number,
			default: undefined,
		},
	},

	computed: {
		...mapStores(useMainStore),
		mailboxes() {
			return this.getMailboxes()
		},
	},

	methods: {
		getMailboxes(mailboxId, depth = 0) {
			const mailboxes = mailboxId
				? this.mainStore.getSubMailboxes(mailboxId)
				: this.mainStore.getMailboxes(this.account.accountId)

			return mailboxes
				.filter((mailbox) => mailboxHasRights(mailbox, 'i'))
				.flatMap((mailbox) => [
					{
						id: mailbox.databaseId,
						label: mailbox.displayName,
						depth,
					},
					...this.getMailboxes(mailbox.databaseId, depth + 1),
				])
		},
	},
}
</script>

<style lang="scss" scoped>
.mailbox-option {
	padding-inline-start: calc(var(--mailbox-depth) * var(--default-grid-baseline) * 3);
}
</style>
