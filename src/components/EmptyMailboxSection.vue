<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcEmptyContent class="empty-content" :name="t('mail', 'No messages')">
		<template #icon>
			<IconMail />
		</template>
	</NcEmptyContent>
</template>

<script>
import IconMail from 'vue-material-design-icons/EmailOutline.vue'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
export default {
	name: 'EmptyMailboxSection',
	components: {
		IconMail,
		NcEmptyContent,
	},
}
</script>
<style lang="scss" scoped>
.empty-content {
	height: 100%;
	display: flex;
}
</style>
