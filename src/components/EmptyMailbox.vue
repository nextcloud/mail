<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcEmptyContent :name="t('mail', 'No messages in this folder')">
		<template #icon>
			<IconMail />
		</template>
	</NcEmptyContent>
</template>

<script>
import IconMail from 'vue-material-design-icons/EmailOutline.vue'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'

export default {
	name: 'EmptyMailbox',
	components: {
		IconMail,
		NcEmptyContent,
	},
}
</script>
