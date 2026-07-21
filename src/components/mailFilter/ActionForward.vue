<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcTextField
		:required="true"
		:model-value="recipient"
		:label-outside="true"
		:placeholder="t('mail', 'Enter recipient')"
		@update:value="onInput" />
</template>

<script>
import { NcTextField } from '@nextcloud/vue'

export default {
	name: 'ActionForward',
	components: {
		NcTextField,
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
