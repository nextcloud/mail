<!--
  - @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
  - @copyright 2020 Greta Doci <gretadoci@gmail.com>
  -
  - @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
  - @author 2020 Greta Doci <gretadoci@gmail.com>
  -
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
				:title="t('mail', 'Change name')"
				@click="handleClick" />
			<AliasSettings v-if="!account.provisioned" :account="account" />
		</AppSettingsSection>
		<AppSettingsSection :title="t('mail', 'Signature')">
			<p class="settings-hint">
				{{ t('mail', 'A signature is added to the text of new messages and replies.') }}
			</p>
			<SignatureSettings :account="account" />
		</AppSettingsSection>
		<AppSettingsSection :title="t('mail', 'Writing mode')">
			<p class="settings-hint">
				{{ t('mail', 'Preferred writing mode for new messages and replies.') }}
			</p>
			<EditorSettings :account="account" />
		</AppSettingsSection>
		<AppSettingsSection :title=" t('mail', 'Default folders')">
			<p class="settings-hint">
				{{
					t('mail', 'The folders to use for drafts, sent messages and deleted messages.')
				}}
			</p>
			<AccountDefaultsSettings :account="account" />
		</AppSettingsSection>
		<AppSettingsSection :title="t('mail', 'Mail server')">
			<div v-if="!account.provisioned">
				<div id="mail-settings">
					<AccountForm
						:key="account.accountId"
						ref="accountForm"
						:display-name="displayName"
						:email="email"
						:save="onSave"
						:account="account" />
				</div>
			</div>
		</AppSettingsSection>
		<AppSettingsSection :title="t('mail', 'Trusted senders')">
			<TrustedSenders />
		</AppSettingsSection>
	</AppSettingsDialog>
</template>

<script>
import AccountForm from '../components/AccountForm'
import EditorSettings from '../components/EditorSettings'
import AccountDefaultsSettings from '../components/AccountDefaultsSettings'
import Logger from '../logger'
import SignatureSettings from '../components/SignatureSettings'
import AliasSettings from '../components/AliasSettings'
import AppSettingsDialog from '@nextcloud/vue/dist/Components/AppSettingsDialog'
import AppSettingsSection from '@nextcloud/vue/dist/Components/AppSettingsSection'
import TrustedSenders from './TrustedSenders'
export default {
	name: 'AccountSettings',
	components: {
		TrustedSenders,
		AccountForm,
		AliasSettings,
		EditorSettings,
		SignatureSettings,
		AppSettingsDialog,
		AppSettingsSection,
		AccountDefaultsSettings,
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
		open(value) {
			if (value) {
				this.showSettings = true
			}
		},
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
		handleClick() {
			this.$refs.accountForm.$el.scrollIntoView({
				behavior: 'smooth',
			})

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
</style>
