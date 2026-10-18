<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcSelect
		inputLabel="flag"
		:modelValue="flag"
		:required="true"
		:labelOutside="true"
		:options="flags"
		:clearable="false"
		@update:modelValue="updateAction({ flag: $event })">
		<template #selected-option="{ label }">
			{{ getLabelForFlag(label) }}
		</template>
		<template #option="{ label }">
			{{ getLabelForFlag(label) }}
		</template>
	</NcSelect>
</template>

<script>
import NcSelect from '@nextcloud/vue/components/NcSelect'
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

	emits: ['updateAction'],

	data() {
		return {
			flags: [
				MailFilterSystemFlag.Answered,
				MailFilterSystemFlag.Deleted,
				MailFilterSystemFlag.Draft,
				MailFilterSystemFlag.Flagged,
				MailFilterSystemFlag.Seen,
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
			this.$emit('updateAction', value)
		},

		getLabelForFlag(field) {
			switch (field) {
				case MailFilterSystemFlag.Answered:
					return t('mail', 'Answered')
				case MailFilterSystemFlag.Deleted:
					return t('mail', 'Deleted')
				case MailFilterSystemFlag.Draft:
					return t('mail', 'Draft')
				case MailFilterSystemFlag.Flagged:
					return t('mail', 'Flagged')
				case MailFilterSystemFlag.Seen:
					return t('mail', 'Seen')
			}
			return field
		},
	},
}
</script>
