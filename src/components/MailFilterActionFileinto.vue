<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<MailboxInlinePicker :account="account" :value="mailbox" @input="action.mailbox = $event" />
</template>
<script>

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
		mailbox() {
			return this.action.mailbox ?? undefined
		},
	},
	method: {
		onInput(event) {

		},
	},
}
</script>
