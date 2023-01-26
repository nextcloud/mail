<!--
  - @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
  - @copyright 2020 Greta Doci <gretadoci@gmail.com>
  -
  - @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
  - @author 2020 Greta Doci <gretadoci@gmail.com>
  - @author 2022 Richard Steinmetz <richard@steinmetz.cloud>
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
	<AppSettingsDialog
		id="app-settings-dialog"
		:open.sync="showSettings"
		:show-navigation="true">
		<AppSettingsSection
			id="account-settings"
			:title="t('mail', 'Account settings')">
			<AliasSettings :account="account" @rename-primary-alias="scrollToAccountSettings" />
		</AppSettingsSection>
		<AppSettingsSection id="signature" :title="t('mail', 'Signature')">
			<p class="settings-hint">
				{{ t('mail', 'A signature is added to the text of new messages and replies.') }}
			</p>
			<SignatureSettings :account="account" />
		</AppSettingsSection>
		<AppSettingsSection id="writing-mode" :title="t('mail', 'Writing mode')">
			<p class="settings-hint">
				{{ t('mail', 'Preferred writing mode for new messages and replies.') }}
			</p>
			<EditorSettings :account="account" />
		</AppSettingsSection>
		<AppSettingsSection id="default-folders" :title=" t('mail', 'Default folders')">
			<p class="settings-hint">
				{{
					t('mail', 'The folders to use for drafts, sent messages, deleted messages and archived messages.')
				}}
			</p>
			<AccountDefaultsSettings :account="account" />
		</AppSettingsSection>
		<AppSettingsSection
			v-if="account"
			id="out-of-office-replies"
			:title="t('mail', 'Autoresponder')">
			<p class="settings-hint">
				{{ t('mail', 'Automated reply to incoming messages. If someone sends you several messages, this automated reply will be sent at most once every 4 days.') }}
			</p>
			<OutOfOfficeForm v-if="account.sieveEnabled" :account="account" />
			<p v-else>
				{{ t('mail', 'Please connect to a sieve server first.') }}
			</p>
		</AppSettingsSection>
		<AppSettingsSection v-if="account && account.sieveEnabled"
			id="sieve-filter"
			:title="t('mail', 'Sieve filter rules')">
			<div id="sieve-filter">
				<SieveFilterForm
					:key="account.accountId"
					ref="sieveFilterForm"
					:account="account" />
			</div>
		</AppSettingsSection>
		<AppSettingsSection id="trusted-sender" :title="t('mail', 'Trusted senders')">
			<TrustedSenders />
		</AppSettingsSection>
		<AppSettingsSection v-if="account && !account.provisioningId"
			id="mail-server"
			:title="t('mail', 'Mail server')">
			<div id="mail-settings">
				<AccountForm
					:key="account.accountId"
					ref="accountForm"
					:display-name="displayName"
					:email="email"
					:account="account" />
			</div>
		</AppSettingsSection>
		<AppSettingsSection v-if="account && !account.provisioningId"
			id="sieve-settings"
			:title="t('mail', 'Sieve filter server')">
			<div id="sieve-settings">
				<SieveAccountForm
					:key="account.accountId"
					ref="sieveAccountForm"
					:account="account" />
			</div>
		</AppSettingsSection>
	</AppSettingsDialog>
</template>

<script>
import AccountForm from '../components/AccountForm'
import EditorSettings from '../components/EditorSettings'
import AccountDefaultsSettings from '../components/AccountDefaultsSettings'
import SignatureSettings from '../components/SignatureSettings'
import AliasSettings from '../components/AliasSettings'
import { NcAppSettingsDialog as AppSettingsDialog, NcAppSettingsSection as AppSettingsSection } from '@nextcloud/vue'
import TrustedSenders from './TrustedSenders'
import SieveAccountForm from './SieveAccountForm'
import SieveFilterForm from './SieveFilterForm'
import OutOfOfficeForm from './OutOfOfficeForm'

export default {
	name: 'AccountSettings',
	components: {
		SieveAccountForm,
		SieveFilterForm,
		TrustedSenders,
		AccountForm,
		AliasSettings,
		EditorSettings,
		SignatureSettings,
		AppSettingsDialog,
		AppSettingsSection,
		AccountDefaultsSettings,
		OutOfOfficeForm,
	},
	props: {
		account: {
			required: true,
			type: Object,
		},
		open: {
			required: true,
			type: Boolean,
		},
	},
	data() {
		return {
			showSettings: false,
		}
	},
	computed: {
		menu() {
			return this.buildMenu()
		},
		displayName() {
			return this.account.name
		},
		email() {
			return this.account.emailAddress
		},
	},
	watch: {
		showSettings(value) {
			if (!value) {
				this.$emit('update:open', value)
			}
		},
		async open(value) {
			if (value) {
				await this.onOpen()
			}
		},
	},
	methods: {
		scrollToAccountSettings() {
			this.$refs.accountForm.$el.scrollIntoView({
				behavior: 'smooth',
			})
		},
		async onOpen() {
			this.showSettings = true
			await this.$store.dispatch('fetchActiveSieveScript', {
				accountId: this.account.id,
			})
		},
	},
}
</script>

<style lang="scss" scoped>
.alias-item {
	display: flex;
	justify-content: space-between;
}

.button.icon-rename {
	background-image: var(--icon-rename-000);
	background-color: var(--color-main-background);
	border: none;
	opacity: 0.7;
	&:hover,
	&:focus {
		opacity: 1;
	}
}
.settings-hint {
	margin-top: -12px;
	margin-bottom: 6px;
	color: var(--color-text-maxcontrast);
}
h2 {
	font-weight: bold;
	font-size: 20px;
	margin-bottom: 12px;
	margin-left: -30px;
	line-height: 30px;
	color: var(--color-text-light);
}
.app-settings-section {
margin-bottom: 45px;
}

// Fix weird modal glitches on Firefox when toggling autoresponder
:deep(.modal-container),
:deep(.app-settings__wrapper) {
	position: unset !important;
}
</style>
