<!--
  - @copyright 2020 Patrick Bender <patrick@bender-it-services.de>
  -
  - @author 2023 Richard Steinmetz <richard@steinmetz.cloud>
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
		<ul class="aliases-list">
			<!-- Primary alias -->
			<li>
				<AliasForm :account="account"
					:alias="accountAlias"
					:enable-update="false"
					:enable-delete="false"
					:on-update-smime-certificate="updateAccountSmimeCertificate">
					<ButtonVue v-if="!account.provisioningId"
						type="tertiary-no-background"
						:title="t('mail', 'Change name')"
						@click="$emit('rename-primary-alias')">
						<template #icon>
							<IconRename :size="20" />
						</template>
					</ButtonVue>
				</AliasForm>
			</li>

			<!-- Secondary aliases -->
			<li v-for="alias in aliases" :key="alias.id">
				<AliasForm :account="account"
					:alias="alias"
					:on-update-alias="updateAlias"
					:on-update-smime-certificate="updateAliasSmimeCertificate"
					:on-delete="deleteAlias" />
			</li>

			<li v-if="showForm">
				<form id="createAliasForm" @submit.prevent="createAlias">
					<input v-model="newName"
						type="text"
						:placeholder="t('mail', 'Name')"
						required>
					<input v-model="newAlias"
						type="email"
						:placeholder="t('mail', 'Email address')"
						required>
				</form>
			</li>
		</ul>

		<div v-if="!account.provisioningId">
			<ButtonVue v-if="!showForm" type="primary" @click="showForm = true">
				{{ t('mail', 'Add alias') }}
			</ButtonVue>

			<ButtonVue v-if="showForm"
				native-type="submit"
				type="primary"
				form="createAliasForm"
				:disabled="loading">
				<template #icon>
					<IconLoading v-if="loading" :size="20" />
					<IconCheck v-else :size="20" />
				</template>
				{{ t('mail', 'Create alias') }}
			</ButtonVue>
			<ButtonVue v-if="showForm"
				type="tertiary-no-background"
				class="button-text"
				@click="resetCreate">
				{{ t("mail", "Cancel") }}
			</ButtonVue>
		</div>
	</div>
</template>

<script>
import { NcButton as ButtonVue, NcLoadingIcon as IconLoading } from '@nextcloud/vue'
import IconCheck from 'vue-material-design-icons/Check'
import IconRename from 'vue-material-design-icons/Pencil'
import logger from '../logger'
import AliasForm from './AliasForm'

export default {
	name: 'AliasSettings',
	components: {
		AliasForm,
		ButtonVue,
		IconLoading,
		IconCheck,
		IconRename,
	},
	props: {
		account: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			newAlias: '',
			newName: this.account.name,
			showForm: false,
			loading: false,
		}
	},
	computed: {
		aliases() {
			return this.account.aliases
		},
		accountAlias() {
			return {
				alias: this.account.emailAddress,
				name: this.account.name,
				provisioned: !!this.account.provisioningId,
				smimeCertificateId: this.account.smimeCertificateId,
			}
		},
	},
	methods: {
		async createAlias() {
			this.loading = true

			await this.$store.dispatch('createAlias', {
				account: this.account,
				alias: this.newAlias,
				name: this.newName,
			})

			logger.debug('created alias', {
				accountId: this.account.id,
				alias: this.newAlias,
				name: this.newName,
			})

			this.resetCreate()
			this.loading = false
		},
		resetCreate() {
			this.newAlias = ''
			this.newName = this.account.name
			this.showForm = false
		},
		async updateAccountSmimeCertificate(aliasId, smimeCertificateId) {
			await this.$store.dispatch('updateAccountSmimeCertificate', {
				account: this.account,
				smimeCertificateId,
			})
		},
		async updateAliasSmimeCertificate(aliasId, smimeCertificateId) {
			const alias = this.aliases.find((alias) => alias.id === aliasId)
			await this.$store.dispatch('updateAlias', {
				account: this.account,
				aliasId,
				alias: alias.alias,
				name: alias.name,
				smimeCertificateId,
			})
		},
		async updateAlias(aliasId, newAlias) {
			const alias = this.aliases.find((alias) => alias.id === aliasId)
			await this.$store.dispatch('updateAlias', {
				account: this.account,
				aliasId: alias.id,
				alias: newAlias.alias,
				name: newAlias.name,
				smimeCertificateId: alias.smimeCertificateId,
			})
		},
		async deleteAlias(aliasId) {
			await this.$store.dispatch('deleteAlias', {
				account: this.account,
				aliasId,
			})
		},
	},
}
</script>

<style lang="scss" scoped>
.primary {
	padding-left: 26px;
	background-position: 6px;
	color: var(--color-main-background);

	&:after {
		left: 14px;
	}
}

.button-text {
	background-color: transparent;
	border: none;
	color: var(--color-text-maxcontrast);
	font-weight: normal;

	&:hover,
	&:focus {
		color: var(--color-main-text);
	}
}

input {
	width: 195px;
}
.button-vue:deep() {
	display: inline-block !important;
	margin-top: 4px !important;
}
</style>
