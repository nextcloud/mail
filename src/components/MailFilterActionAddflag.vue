<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcTextField id="mail-filter-value"
		:required="true"
		:label="t('mail', 'Flag')"
		:value.sync="action.flag" />
</template>
<script>
import { NcButton, NcSelect, NcTextField } from '@nextcloud/vue'
import DeleteIcon from 'vue-material-design-icons/Delete.vue'

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
}
</script>
