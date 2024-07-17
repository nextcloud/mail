<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcTextField id="mail-filter-value"
		:required="true"
		:label="t('mail', 'Flag')"
		:value="flag"
		@update:value="onInput" />
</template>
<script>
import { NcTextField } from '@nextcloud/vue'

export default {
	name: 'MailFilterActionAddflag',
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
		flag() {
			return this.action.flag ?? ''
		},
	},
	methods: {
		onInput(value) {
			this.$emit('update-action', { flag: value })
		},
	},
}
</script>
