<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcInputField
		type="email"
		:required="true"
		:model-value="recipient"
		:label-outside="true"
		:aria-label="t('mail', 'Recipient')"
		:placeholder="t('mail', 'name@example.org')"
		@update:model-value="onInput" />
</template>

<script>
import { NcInputField } from '@nextcloud/vue'

export default {
	name: 'ActionForward',
	components: {
		NcInputField,
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
		recipient() {
			return this.action.recipient ?? ''
		},
	},

	methods: {
		onInput(value) {
			this.$emit('update-action', { recipient: value })
		},
	},
}
</script>

<style lang="scss" scoped>
.input-field {
	display: inline-block; /* for flex expand */
}
</style>
