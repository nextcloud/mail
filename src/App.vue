<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<router-view />
</template>

<script>
import { showError } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'

import logger from './logger.js'
import { matchError } from './errors/match.js'
import MailboxLockedError from './errors/MailboxLockedError.js'
import { mapStores, mapState } from 'pinia'
import useMainStore from './store/mainStore.js'
import initAfterAppCreation from './init.js'

export default {
	name: 'App',
	computed: {
		...mapStores(useMainStore),
		...mapState(useMainStore, [
			'isExpiredSession',
		]),
		hasMailAccounts() {
			return !!this.mainStore.getAccounts.find((account) => !account.isUnified)
		},
	},
	watch: {
		isExpiredSession(expired) {
			if (expired) {
				showError(t('mail', 'Your session has expired. The page will be reloaded.'), {
					onRemove: () => {
						this.reload()
					},
				})
			}
		},
	},
	async mounted() {
		initAfterAppCreation()
		// Redirect to setup page if no accounts are configured
		if (!this.hasMailAccounts) {
			this.$router.replace({
				name: 'setup',
			})
		}

		this.sync()
		await this.mainStore.fetchCurrentUserPrincipal()
		await this.mainStore.loadCollections()
		this.mainStore.hasCurrentUserPrincipalAndCollectionsMutation(true)
	},
	methods: {
		reload() {
			window.location.reload()
		},
		sync() {
			setTimeout(async () => {
				try {
					await this.mainStore.syncInboxes()

					logger.debug("Inboxes sync'ed in background")
				} catch (error) {
					matchError(error, {
						[MailboxLockedError.name](error) {
							logger.info('Background sync failed because a folder is locked', { error })
						},
						default(error) {
							logger.error('Background sync failed: ' + error.message, { error })
						},
					})
				} finally {
					// Start over
					this.sync()
				}
			}, 30 * 1000)
		},
	},
}
</script>
