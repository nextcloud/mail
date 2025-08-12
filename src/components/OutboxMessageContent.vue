<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcEmptyContent :name="t('mail', 'Pending or not sent messages will show up here')" class="empty-content">
		<template #icon>
			<IconMail />
		</template>
	</NcEmptyContent>
</template>

<script>
import IconMail from 'vue-material-design-icons/EmailOutline.vue'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'

export default {
	name: 'OutboxMessageContent',
	components: {
		IconMail,
		NcEmptyContent,
	},
}
</script>

<style scoped>
.empty-content{
	height: 100%;
	display: flex;
}
</style>
