<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<MailboxInlinePicker :account="account" :value="mailbox" @input="onInput" />
</template>
<script>

import { mapStores } from 'pinia'
import useMainStore from '../../store/mainStore.js'
import MailboxInlinePicker from '../MailboxInlinePicker.vue'

export default {
	name: 'ActionFileinto',
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
		...mapStores(useMainStore),
		mailbox() {
			return this.getMailboxDatabaseIdByName(this.action?.mailbox)
		},
	},
	methods: {
		onInput(value) {
			this.$emit('update-action', { mailbox: this.getMailboxNameByDatabaseId(value) })
		},
		getMailboxDatabaseIdByName(name) {
			return this.mainStore.getMailboxesAndSubmailboxesByAccountId(this.account.id).find((mailbox) => mailbox.name === name)?.databaseId
		},
		getMailboxNameByDatabaseId(databaseId) {
			return this.mainStore.getMailbox(databaseId)?.name
		},
	},
}
</script>
