<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcEmptyContent class="empty-content" :name="t('mail', 'No messages')">
		<template #icon>
			<IconMail :size="65" />
		</template>
	</NcEmptyContent>
</template>

<script>
import IconMail from 'vue-material-design-icons/Email.vue'
import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'
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
