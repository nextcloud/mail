<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<MailboxInlinePicker v-model="selectedMailbox" :account="account" />
</template>
<script>

import { mapStores } from 'pinia'
import useMainStore from '../../store/mainStore.js'
import MailboxInlinePicker from '../MailboxInlinePicker.vue'
import logger from '../../logger.js'

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
	data() {
		return {
			currentSelectedMailboxId: null,
		}
	},
	computed: {
		...mapStores(useMainStore),
		mailbox() {
			return this.action.mailbox ?? undefined
		},
		selectedMailbox: {
			get() {
				return this.currentSelectedMailboxId
			},
			set(selectedMailboxId) {
				logger.debug('Selected mailbox set to', selectedMailboxId)
				this.currentSelectedMailboxId = selectedMailboxId
			},
		},
	},
	methods: {
		onInput(value) {
			this.$emit('update-action', { mailbox: value })
		},
	},
}
</script>
