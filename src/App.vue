<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<router-view />
</template>

<script>
import { showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { mapState, mapStores } from 'pinia'
import MailboxLockedError from './errors/MailboxLockedError.js'
import { matchError } from './errors/match.js'
import initAfterAppCreation from './init.js'
import logger from './logger.js'
import { ADDRESS_LISTS_TIMEOUT, SYNC_INTERVAL } from './store/constants.js'
import useMainStore from './store/mainStore.js'
import { wait } from './util/wait.js'

export default {
	name: 'App',
	data() {
		return {
			syncing: false,
			addressListsRefreshedAt: Date.now(),
		}
	},

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

		document.addEventListener('visibilitychange', this.onVisibilityChange)
		this.sync()
		await this.mainStore.fetchCurrentUserPrincipal()
		await this.mainStore.loadCollections()
		this.mainStore.hasCurrentUserPrincipalAndCollectionsMutation(true)
	},

	beforeUnmount() {
		document.removeEventListener('visibilitychange', this.onVisibilityChange)
	},

	methods: {
		reload() {
			window.location.reload()
		},

		sync() {
			setTimeout(async () => {
				// Don't sync a previous sync is still running
				if (this.syncing) {
					this.sync()
					return
				}

				this.syncing = true
				try {
					// Before the inbox sync, so new mail meets changes from other devices; hidden tabs catch up later
					if (document.visibilityState === 'visible' && this.addressListsAreDue()) {
						await Promise.race([this.refreshAddressLists(), wait(ADDRESS_LISTS_TIMEOUT)])
					}
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
					this.syncing = false
					// Start over
					this.sync()
				}
			}, SYNC_INTERVAL)
		},

		onVisibilityChange() {
			if (document.visibilityState !== 'visible' || !this.addressListsAreDue()) {
				return
			}

			// A message opened right away waits for the current list before it loads images
			this.refreshAddressLists({ stale: true })
		},

		addressListsAreDue() {
			return Date.now() - this.addressListsRefreshedAt >= SYNC_INTERVAL
		},

		async refreshAddressLists({ stale = false } = {}) {
			this.addressListsRefreshedAt = Date.now()
			const refreshes = [this.mainStore.fetchTrustedSenders({ stale })]
			if (this.mainStore.getPreference('internal-addresses', 'false') === 'true') {
				refreshes.push(this.mainStore.fetchInternalAddresses())
			}

			const results = await Promise.allSettled(refreshes)
			results
				.filter((result) => result.status === 'rejected')
				.forEach((result) => logger.warn('Could not refresh the trusted senders or internal addresses', { error: result.reason }))
		},
	},
}
</script>
