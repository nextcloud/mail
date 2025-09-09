<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div>
		<ul class="aliases-list">
			<!-- Primary alias -->
			<li>
				<AliasForm :account="account"
					:alias="accountAlias"
					:enable-update="false"
					:enable-delete="false">
					<ButtonVue v-if="!account.provisioningId"
						type="tertiary-no-background"
						:aria-label="t('mail', 'Go back')"
						:name="t('mail', 'Change name')"
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

		<div v-if="!account.provisioningId" class="aliases-controls">
			<ButtonVue v-if="!showForm"
				type="primary"
				:aria-label="t('mail', 'Add alias')"
				@click="showForm = true">
				{{ t('mail', 'Add alias') }}
			</ButtonVue>

			<ButtonVue v-if="showForm"
				native-type="submit"
				type="primary"
				form="createAliasForm"
				:aria-label="t('mail', 'Create alias')"
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
				:aria-label="t('mail', 'Cancel')"
				@click="resetCreate">
				{{ t("mail", "Cancel") }}
			</ButtonVue>
		</div>
	</div>
</template>

<script>
import { NcButton as ButtonVue, NcLoadingIcon as IconLoading } from '@nextcloud/vue'
import IconCheck from 'vue-material-design-icons/Check.vue'
import IconRename from 'vue-material-design-icons/PencilOutline.vue'
import logger from '../logger.js'
import AliasForm from './AliasForm.vue'
import useMainStore from '../store/mainStore.js'
import { mapStores } from 'pinia'

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
		...mapStores(useMainStore),
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
.primary {
	padding-inline-start: 26px;
	background-position: 6px;
	color: var(--color-main-background);

	&:after {
		inset-inline-start: 14px;
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

.aliases-controls {
	display: flex;
}

input {
	width: 195px;
}

.button-vue:deep() {
	display: inline-block !important;
	margin-top: 4px !important;
}
</style>
