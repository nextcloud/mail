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
		id() {
			return 'collapse-' + this.account.id
		},
		title() {
			if (this.account.collapsed && this.account.showSubscribedOnly) {
				return t('mail', 'Show all subscribed mailboxes')
			} else if (this.account.collapsed && !this.account.showSubscribedOnly) {
				return t('mail', 'Show all mailboxes')
			}
			return t('mail', 'Collapse mailboxes')
		},
	},
	methods: {
		async toggleCollapse() {
			logger.debug('toggling collapsed mailboxes for account ' + this.account.id)
			try {
				await this.$store.commit('toggleAccountCollapsed', this.account.id)
				await this.$store
					.dispatch('setAccountSetting', {
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
