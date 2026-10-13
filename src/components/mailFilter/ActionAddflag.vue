<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcTextField
		:required="true"
		:modelValue="flag"
		:labelOutside="true"
		:placeholder="t('mail', 'Enter flag')"
		@update:modelValue="onInput" />
</template>

<script>
import NcTextField from '@nextcloud/vue/components/NcTextField'

export default {
	name: 'ActionAddflag',
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

	emits: ['updateAction'],

	computed: {
		flag() {
			return this.action.flag ?? ''
		},
	},

	methods: {
		onInput(value) {
			this.$emit('updateAction', { flag: value })
		},
	},
}
</script>

<style lang="scss" scoped>
.input-field {
	display: inline-block; /* for flex expand */
}
</style>
