<!--
  - @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @license GNU AGPL version 3 or any later version
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
	<div class="section">
		<h2>{{ t('mail', 'Defaults') }}</h2>
		<p class="settings-hint">
			{{
				t('mail', 'Here you can select where Nextcloud Mail stores your drafts as well as sent and deleted messages.')
			}}
		</p>
		<table>
			<tr>
				<td>
					{{ t('mail', 'Draft messages are saved to:') }}
				</td>
				<td>
					<MailboxInlinePicker v-model="draftsMailbox" :account="account" :disabled="saving" />
				</td>
			</tr>
			<tr>
				<td>
					{{ t('mail', 'Sent messages are saved to:') }}
				</td>
				<td>
					<MailboxInlinePicker v-model="sentMailbox" :account="account" :disabled="saving" />
				</td>
			</tr>
			<tr>
				<td>
					{{ t('mail', 'Deleted messages are moved to:') }}
				</td>
				<td>
					<MailboxInlinePicker v-model="trashMailbox" :account="account" :disabled="saving" />
				</td>
			</tr>
		</table>
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
