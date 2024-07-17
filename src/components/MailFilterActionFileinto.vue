<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<MailboxInlinePicker :account="account" :value="action.mailbox" />
</template>
<script>
import { NcActionButton, NcButton, NcSelect, NcTextField } from '@nextcloud/vue'
import DeleteIcon from 'vue-material-design-icons/Delete.vue'
import MailboxInlinePicker from './MailboxInlinePicker.vue'

export default {
	name: 'MailFilterActionFileinto',
	components: {
		MailboxInlinePicker,
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
		mailboxes() {
			return this.$store.getters.getMailboxes(this.accountId)
		},
	},
}
</script>
