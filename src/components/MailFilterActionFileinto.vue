<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcSelect ref="select"
		:value="mailbox"
		:options="mailboxes"
		@input="onInput" />
</template>
<script>

import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'
import { mailboxHasRights } from '../util/acl.js'
export default {
	name: 'MailFilterActionFileinto',
	components: {
		NcSelect,
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
		mailboxes() {
			const mailboxes = this.$store.getters.getMailboxes(this.account.accountId)
				.filter(mailbox => mailboxHasRights(mailbox, 'i'))

			return mailboxes.map((mailbox) => {
				return mailbox.displayName
			})
		},
	},
	methods: {
		onInput(value) {
			this.$emit('update-action', { mailbox: value })
		},
	},
}
</script>
