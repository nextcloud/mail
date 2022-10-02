<!--
 - @copyright Copyright (c) 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
 -
 - @author Christoph Wurst <christoph@winzerhof-wurst.at>
 -
 - @license AGPL-3.0-or-later
 -
 - This program is free software: you can redistribute it and/or modify
 - it under the terms of the GNU Affero General Public License as
 - published by the Free Software Foundation, either version 3 of the
 - License, or (at your option) any later version.
 -
 - This program is distributed in the hope that it will be useful,
 - but WITHOUT ANY WARRANTY; without even the implied warranty of
 - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 - GNU Affero General Public License for more details.
 -
 - You should have received a copy of the GNU Affero General Public License
 - along with this program. If not, see <http://www.gnu.org/licenses/>.
 -
 -->

<template>
	<router-view />
</template>

<script>
import logger from './logger'
import { matchError } from './errors/match'
import MailboxLockedError from './errors/MailboxLockedError'

export default {
	name: 'App',
	computed: {
		hasMailAccounts() {
			return !!this.$store.getters.accounts.find((account) => !account.isUnified)
		},
	},
	mounted() {
		// Redirect to setup page if no accounts are configured
		if (!this.hasMailAccounts) {
			this.$router.replace({
				name: 'setup',
			})
		}

		this.sync()
	},
	methods: {
		sync() {
			setTimeout(async () => {
				try {
					await this.$store.dispatch('syncInboxes')

					logger.debug("Inboxes sync'ed in background")
				} catch (error) {
					matchError(error, {
						[MailboxLockedError.name](error) {
							logger.info('Background sync failed because a mailbox is locked', { error })
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
