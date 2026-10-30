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
import { SYNC_INTERVAL } from './store/constants.js'
import useMainStore from './store/mainStore.js'

export default {
	name: 'App',
	data() {
		return {
			syncing: false,
			addressListsRefreshedAt: Date.now(),
			catchingUp: false,
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

		alwaysShowExternalImages() {
			return this.mainStore.getPreference('always-show-external-images', 'false') === 'true'
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

		async alwaysShowExternalImages(alwaysShow) {
			if (alwaysShow) {
				return
			}

			// The trusted senders weren't refreshed while they had no effect
			try {
				await this.mainStore.fetchTrustedSenders({ stale: true })
			} catch (error) {
				logger.warn('Could not refresh the trusted senders', { error })
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
		this.pollAddressLists()
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

		pollAddressLists() {
			const scheduledAt = Date.now()
			setTimeout(async () => {
				try {
					// Hidden tabs catch up once they are visible again, which makes this refresh redundant
					if (document.visibilityState === 'visible' && this.addressListsRefreshedAt <= scheduledAt) {
						await this.refreshAddressLists()
					}
				} catch (error) {
					logger.error('Could not refresh the trusted senders or internal addresses', { error })
				} finally {
					// Only now, so a slow server answers a refresh before the next one replaces it
					this.pollAddressLists()
				}
			}, SYNC_INTERVAL)
		},

		async onVisibilityChange() {
			// A catch-up that is still loading brings the lists up to date already
			if (document.visibilityState !== 'visible' || !this.addressListsAreDue() || this.catchingUp) {
				return
			}

			this.catchingUp = true
			// A message opened right away waits for the current list before it loads images
			await this.refreshAddressLists({ stale: true })
			this.catchingUp = false
		},

		addressListsAreDue() {
			return Date.now() - this.addressListsRefreshedAt >= SYNC_INTERVAL
		},

		async refreshAddressLists({ stale = false } = {}) {
			this.addressListsRefreshedAt = Date.now()
			const refreshes = []
			if (!this.alwaysShowExternalImages) {
				refreshes.push(this.mainStore.fetchTrustedSenders({ stale }))
			}
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
