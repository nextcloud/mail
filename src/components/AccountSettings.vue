<!--
  - @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
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
	<AppSettingsDialog
		:open.sync="showSettings">
		<AppSettingsSection
			:title="t('mail', 'Account settings')">
				<strong>{{ displayName }}</strong> &lt;{{ email }}&gt;
				<a
					v-if="!account.provisioned"
					class="button icon-rename"
					href="#account-form"
					:title="t('mail', 'Change name')" />
		</AppSettingsSection>
		<AppSettingsSection :title="t('mail', 'Alias settings')">
			<AliasSettings v-if="!account.provisioned" :account="account" />
		</AppSettingsSection>
		<AppSettingsSection :title="t('mail', 'Signature settings')">
			<SignatureSettings :account="account" />
		</AppSettingsSection>
		<AppSettingsSection :title="t('mail', 'Editor settings')">
			<EditorSettings :account="account" />
			<div v-if="!account.provisioned" class="section">
				<h2>{{ t('mail', 'Mail server') }}</h2>
				<div id="mail-settings">
					<AccountForm
						:key="account.accountId"
						:display-name="displayName"
						:email="email"
						:save="onSave"
						:account="account" />
				</div>
			</div>
		</AppSettingsSection>
	</AppSettingsDialog>
</template>

<script>
import AccountForm from '../components/AccountForm'
import EditorSettings from '../components/EditorSettings'
import Logger from '../logger'
import SignatureSettings from '../components/SignatureSettings'
import AliasSettings from '../components/AliasSettings'
import AppSettingsDialog from '@nextcloud/vue/dist/Components/AppSettingsDialog'
import AppSettingsSection from '@nextcloud/vue/dist/Components/AppSettingsSection'
import { subscribe, unsubscribe } from '@nextcloud/event-bus'

export default {
	name: 'AccountSettings',
	components: {
		AccountForm,
		AliasSettings,
		EditorSettings,
		SignatureSettings,
		AppSettingsDialog,
		AppSettingsSection,
	},
	data() {
		return {
			showSettings: false,
			account: undefined,
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
	mounted() {
		subscribe('show-settings', this.handleShowSettings)
	},
	beforeDestroy() {
		unsubscribe('show-settings', this.handleShowSettings)
	},
	methods: {
		onSave(data) {
			Logger.log('saving data', { data })
			return this.$store
				.dispatch('updateAccount', {
					...data,
					accountId: this.$route.params.accountId,
				})
				.then((account) => account)
				.catch((error) => {
					Logger.error('account update failed:', { error })

					throw error
				})
		},
		handleShowSettings(payload) {
			this.account = payload.account
			this.showSettings = true
		},
	},
}
</script>

<style lang="scss" scoped>
::v-deep .modal-container {
	display: block;
	overflow: scroll;
	transition: transform 300ms ease;
	border-radius: var(--border-radius-large);
	box-shadow: 0 0 40px rgba(0,0,0,0.2);
	padding: 30px 70px 20px;
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
</style>
