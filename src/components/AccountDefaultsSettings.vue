<!--
  - @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
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
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
	<div>
		<p>
			{{ t('mail', 'Drafts are saved in:') }}
		</p>
		<MailboxInlinePicker v-model="draftsMailbox" :account="account" :disabled="saving" />

		<p>
			{{ t('mail', 'Sent messages are saved in:') }}
		</p>

		<MailboxInlinePicker v-model="sentMailbox" :account="account" :disabled="saving" />
		<p>
			{{ t('mail', 'Deleted messages are moved in:') }}
		</p>

		<MailboxInlinePicker v-model="trashMailbox" :account="account" :disabled="saving" />
		<p>
			{{ t('mail', 'Archived messages are moved in:') }}
		</p>

		<MailboxInlinePicker v-model="archiveMailbox" :account="account" :disabled="saving" />
	</div>
</template>

<script>
import logger from '../logger'
import MailboxInlinePicker from './MailboxInlinePicker'

export default {
	name: 'AccountDefaultsSettings',
	components: {
		MailboxInlinePicker,
	},
	props: {
		account: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			saving: false,
		}
	},
	computed: {
		draftsMailbox: {
			get() {
				const mb = this.$store.getters.getMailbox(this.account.draftsMailboxId)
				if (!mb) {
					return
				}
				return mb.databaseId
			},
			async set(draftsMailboxId) {
				logger.debug('setting drafts mailbox to ' + draftsMailboxId)
				this.saving = true
				try {
					await this.$store.dispatch('patchAccount', {
						account: this.account,
						data: {
							draftsMailboxId,
						},
					})
				} catch (error) {
					logger.error('could not set drafts mailbox', {
						error,
					})
				} finally {
					this.saving = false
				}
			},
		},
		sentMailbox: {
			get() {
				const mb = this.$store.getters.getMailbox(this.account.sentMailboxId)
				if (!mb) {
					return
				}
				return mb.databaseId
			},
			async set(sentMailboxId) {
				logger.debug('setting sent mailbox to ' + sentMailboxId)
				this.saving = true
				try {
					await this.$store.dispatch('patchAccount', {
						account: this.account,
						data: {
							sentMailboxId,
						},
					})
				} catch (error) {
					logger.error('could not set sent mailbox', {
						error,
					})
				} finally {
					this.saving = false
				}
			},
		},
		trashMailbox: {
			get() {
				const mb = this.$store.getters.getMailbox(this.account.trashMailboxId)
				if (!mb) {
					return
				}
				return mb.databaseId
			},
			async set(trashMailboxId) {
				logger.debug('setting trash mailbox to ' + trashMailboxId)
				this.saving = true
				try {
					await this.$store.dispatch('patchAccount', {
						account: this.account,
						data: {
							trashMailboxId,
						},
					})
				} catch (error) {
					logger.error('could not set trash mailbox', {
						error,
					})
				} finally {
					this.saving = false
				}
			},
		},
		archiveMailbox: {
			get() {
				const mb = this.$store.getters.getMailbox(this.account.archiveMailboxId)
				if (!mb) {
					return
				}
				return mb.databaseId
			},
			async set(archiveMailboxId) {
				logger.debug('setting archive mailbox to ' + archiveMailboxId)
				this.saving = true
				try {
					await this.$store.dispatch('patchAccount', {
						account: this.account,
						data: {
							archiveMailboxId,
						},
					})
				} catch (error) {
					logger.error('could not set archive mailbox', {
						error,
					})
				} finally {
					this.saving = false
				}
			},
		},
	},
}
</script>

<style lang="scss" scoped>
.button.icon-rename {
	background-color: transparent;
	border: none;
	opacity: 0.3;

	&:hover,
	&:focus {
		opacity: 1;
	}
}
</style>
