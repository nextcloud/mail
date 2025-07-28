<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<AppNavigationItem :name="title" @click="toggleCollapse" />
</template>

<script>
import { NcAppNavigationItem as AppNavigationItem } from '@nextcloud/vue'
import logger from '../logger.js'
import useMainStore from '../store/mainStore.js'
import { mapStores } from 'pinia'

export default {
	name: 'NavigationAccountExpandCollapse',
	components: {
		AppNavigationItem,
	},
	props: {
		account: {
			type: Object,
			required: true,
		},
	},
	computed: {
		...mapStores(useMainStore),
		id() {
			return 'collapse-' + this.account.id
		},
		title() {
			if (this.account.collapsed && this.account.showSubscribedOnly) {
				return t('mail', 'Show all subscribed folders')
			} else if (this.account.collapsed && !this.account.showSubscribedOnly) {
				return t('mail', 'Show all folders')
			}
			return t('mail', 'Collapse folders')
		},
	},
	methods: {
		async toggleCollapse() {
			logger.debug('toggling collapsed mailboxes for account ' + this.account.id)
			try {
				await this.mainStore.toggleAccountCollapsedMutation(this.account.id)
				await this.mainStore.setAccountSetting({
					accountId: this.account.id,
					key: 'collapsed',
					value: this.account.collapsed,
				})
			} catch (error) {
				logger.error('could not update account settings', {
					error,
				})
			}
		},
	},
}
</script>

<style lang="scss" scoped>
:deep(.app-navigation-entry__title) {
	color: var(--color-text-maxcontrast);
}
</style>
