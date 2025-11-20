<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcSelect
		input-label="field"
		class="condition__value__field_operator__column"
		:value="flag"
		:required="true"
		:label-outside="true"
		:options="flags"
		:clearable="false"
		@input="updateAction({ flag: $event })">
		<template #selected-option="{ label }">
			{{ getLabelForFlag(label) }}
		</template>
		<template #option="{ label }">
			{{ getLabelForFlag(label) }}
		</template>
	</NcSelect>
</template>

<script>
import { NcSelect } from '@nextcloud/vue'
import { MailFilterSystemFlag } from '../../models/mailFilter.ts'

export default {
	name: 'ActionAddSystemFlag',
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

	data() {
		return {
			flags: [
				MailFilterSystemFlag.Seen,
				MailFilterSystemFlag.Answered,
				MailFilterSystemFlag.Flagged,
				MailFilterSystemFlag.Deleted,
				MailFilterSystemFlag.Draft,
			],
		}
	},

	computed: {
		flag() {
			return this.action.flag ?? ''
		},
	},

	methods: {
		updateAction(value) {
			this.$emit('update-action', value)
		},

		getLabelForFlag(field) {
			switch (field) {
				case MailFilterSystemFlag.Seen:
					return t('mail', 'Seen')
				case MailFilterSystemFlag.Answered:
					return t('mail', 'Answered')
				case MailFilterSystemFlag.Flagged:
					return t('mail', 'Flagged')
				case MailFilterSystemFlag.Deleted:
					return t('mail', 'Deleted')
				case MailFilterSystemFlag.Draft:
					return t('mail', 'Draft')
			}
			return field
		},
	},
}
</script>

<style lang="scss" scoped>
.input-field {
	display: inline-block; /* for flex expand */
}
</style>
