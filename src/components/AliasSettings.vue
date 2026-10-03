<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div>
		<ul class="aliases-list">
			<!-- Primary alias -->
			<li>
				<AliasForm
					:account="account"
					:alias="accountAlias"
					:enable-update="false"
					:enable-delete="false">
					<NcButton
						v-if="!account.provisioningId"
						variant="tertiary-no-background"
						:aria-label="t('mail', 'Go back')"
						:name="t('mail', 'Change name')"
						@click="$emit('rename-primary-alias')">
						<template #icon>
							<IconRename :size="20" />
						</template>
					</NcButton>
				</AliasForm>
			</li>

			<!-- Secondary aliases -->
			<li v-for="alias in aliases" :key="alias.id">
				<AliasForm
					:account="account"
					:alias="alias"
					:on-update-alias="updateAlias"
					:on-delete="deleteAlias" />
			</li>

			<li v-if="showForm">
				<form id="createAliasForm" @submit.prevent="createAlias">
					<input
						v-model="newName"
						type="text"
						:placeholder="t('mail', 'Name')"
						required>
					<input
						v-model="newAlias"
						type="email"
						:placeholder="t('mail', 'Email address')"
						required>
				</form>
			</li>
		</ul>

		<div v-if="!account.provisioningId" class="mail-button-row">
			<NcButton
				v-if="!showForm"
				variant="primary"
				:aria-label="t('mail', 'Add alias')"
				@click="showForm = true">
				{{ t('mail', 'Add alias') }}
			</NcButton>

			<NcButton
				v-if="showForm"
				type="submit"
				variant="primary"
				form="createAliasForm"
				:aria-label="t('mail', 'Create alias')"
				:disabled="loading">
				<template #icon>
					<NcLoadingIcon v-if="loading" :size="20" />
					<IconCheck v-else :size="20" />
				</template>
				{{ t('mail', 'Create alias') }}
			</NcButton>
			<NcButton
				v-if="showForm"
				variant="tertiary-no-background"
				:aria-label="t('mail', 'Cancel')"
				@click="resetCreate">
				{{ t("mail", "Cancel") }}
			</NcButton>
		</div>

		<div v-if="!account.provisioningId" class="alias-domains-section">
			<NcCheckboxRadioSwitch
				v-model="quickAliasEnabled"
				type="switch">
				{{ t('mail', 'Allow quick alias creation for domains') }}
			</NcCheckboxRadioSwitch>
			<p class="alias-domains-description">
				{{ t('mail', 'Allows adding new aliases directly from received messages for allowed domains.') }}
			</p>
			<div v-if="quickAliasEnabled" class="alias-domains-config">
				<NcSelect
					v-model="aliasDomainsList"
					:options="aliasDomainsList"
					:multiple="true"
					:taggable="true"
					:show-no-options="false"
					:placeholder="t('mail', 'Type domain and press Enter (e.g. example.com)')"
					:aria-label-combobox="t('mail', 'Allowed domains for quick alias creation')"
					@input="onUpdateAliasDomains" />
			</div>
		</div>
	</div>
</template>

<script>
import { NcButton, NcCheckboxRadioSwitch, NcLoadingIcon, NcSelect } from '@nextcloud/vue'
import { mapStores } from 'pinia'
import IconCheck from 'vue-material-design-icons/Check.vue'
import IconRename from 'vue-material-design-icons/PencilOutline.vue'
import AliasForm from './AliasForm.vue'
import logger from '../logger.js'
import useMainStore from '../store/mainStore.js'
import { sortAliases } from '../util/emailAddress.js'

export default {
	name: 'AliasSettings',
	components: {
		AliasForm,
		IconCheck,
		IconRename,
		NcButton,
		NcCheckboxRadioSwitch,
		NcLoadingIcon,
		NcSelect,
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

			quickAliasEnabled: (() => {
				const doms = this.account.aliasDomains
				if (Array.isArray(doms)) {
					return doms.length > 0
				}
				return typeof doms === 'string' && doms.trim().length > 0
			})(),

			aliasDomainsList: (() => {
				const doms = this.account.aliasDomains
				if (Array.isArray(doms)) {
					return [...doms]
				}
				if (typeof doms === 'string') {
					return doms.split(/[,;\s]+/).map((d) => d.trim().toLowerCase()).filter(Boolean)
				}
				return []
			})(),
		}
	},

	computed: {
		...mapStores(useMainStore),
		aliases() {
			return sortAliases(this.account.aliases)
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

	watch: {
		async quickAliasEnabled(val) {
			if (!val) {
				await this.saveAliasDomains([])
			} else if (this.aliasDomainsList.length > 0) {
				await this.saveAliasDomains(this.aliasDomainsList)
			}
		},
	},

	methods: {
		onUpdateAliasDomains(newDomains) {
			if (!Array.isArray(newDomains)) {
				return
			}
			const cleaned = newDomains
				.map((d) => {
					let val = typeof d === 'string' ? d : d?.label || d?.value || ''
					val = val.trim().toLowerCase()
					const atIdx = val.lastIndexOf('@')
					if (atIdx !== -1) {
						val = val.slice(atIdx + 1)
					}
					return val.replace(/^\.+|\.+$/g, '').trim()
				})
				.filter(Boolean)
			const unique = [...new Set(cleaned)]
			this.aliasDomainsList = unique
			this.saveAliasDomains(unique)
		},

		async saveAliasDomains(domains) {
			await this.mainStore.setAccountSetting({
				accountId: this.account.id,
				key: 'aliasDomains',
				value: domains,
			})
			logger.debug('saved aliasDomains for account', {
				accountId: this.account.id,
				aliasDomains: domains,
			})
		},

		async createAlias() {
			this.loading = true

			await this.mainStore.createAlias({
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

		async updateAlias(aliasId, newAlias) {
			const alias = this.aliases.find((alias) => alias.id === aliasId)
			await this.mainStore.updateAlias({
				account: this.account,
				aliasId: alias.id,
				alias: newAlias.alias,
				name: newAlias.name,
				smimeCertificateId: alias.smimeCertificateId,
			})
		},

		async deleteAlias(aliasId) {
			await this.mainStore.deleteAlias({
				account: this.account,
				aliasId,
			})
		},
	},
}
</script>

<style lang="scss" scoped>
input {
	width: 195px;
}

.alias-domains-section {
	margin-top: 24px;
	padding-top: 16px;
	border-top: 1px solid var(--color-border);
	max-width: 500px;
}

.alias-domains-description {
	color: var(--color-text-maxcontrast);
	font-size: 0.85em;
	margin-top: 4px;
	margin-bottom: 12px;
}

.alias-domains-config {
	margin-top: 8px;
}
</style>
