<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<AppContentDetails class="app-content">
		<NcEmptyContent class="app-content__empty"
			:name="t('mail', 'Welcome to {productName} Mail', { productName }, null, {escape: false})"
			:description="t('mail', 'Start writing a message by clicking below or select an existing message to display its contents')">
			<template #icon>
				<IconMail :size="65" />
			</template>
			<template #action>
				<NewMessageButtonHeader />
			</template>
		</NcEmptyContent>
	</AppContentDetails>
</template>

<script>
import { NcAppContentDetails as AppContentDetails, NcEmptyContent } from '@nextcloud/vue'
import IconMail from 'vue-material-design-icons/Email.vue'
import NewMessageButtonHeader from './NewMessageButtonHeader.vue'

export default {
	name: 'NoMessageSelected',
	components: {
		NewMessageButtonHeader,
		AppContentDetails,
		NcEmptyContent,
		IconMail,
	},

	computed: {
		productName() {
			return window?.OC?.theme?.name ?? 'Nextcloud'
		},
	},
}
</script>
<style lang="scss" scoped>
:deep(.refresh__button) {
	display: none !important;
}
.header {
	display: grid !important;
	justify-content: center !important;
}
.app-content {
	display: flex;
	height: 100%;
	justify-content: center;
	&__empty {
		flex-grow: unset;
	}
}
</style>
