<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div>
		<div v-for="domain in sortedDomains"
			:key="domain.address"
			class="address">
			{{ domain.address }}
			<p class="address__type">
				({{ t('mail', 'domain') }})
			</p>
			<ButtonVue type="tertiary"
				class="button"
				:aria-label="t('mail', 'Remove')"
				@click="removeInternalAddress(domain)">
				{{ t('mail','Remove') }}
			</ButtonVue>
		</div>
		<div v-for="email in sortedEmails"
			:key="email.address"
			class="address">
			{{ email.address }}
			<p class="address__type">
				({{ t('mail', 'email') }})
			</p>
			<ButtonVue type="tertiary"
				class="button"
				:aria-label="t('mail','Remove')"
				@click="removeInternalAddress(email)">
				{{ t('mail','Remove') }}
			</ButtonVue>
		</div>
		<ButtonVue type="primary"
			@click="openDialog = true">
			<template #icon>
				<IconAdd :size="20" />
			</template>
			{{ t('mail', 'Add internal address') }}
		</ButtonVue>
		<NcDialog :open.sync="openDialog"
			:buttons="buttons"
			:name="t('mail', 'Add internal address')"
			@close="openDialog = false">
			<NcTextField class="input" :label="t('mail', 'Add internal email or domain')" :value.sync="newAddress" />
		</NcDialog>
	</div>
</template>

<script>

import { NcButton as ButtonVue, NcDialog, NcTextField } from '@nextcloud/vue'
import prop from 'lodash/fp/prop.js'
import sortBy from 'lodash/fp/sortBy.js'
import IconAdd from 'vue-material-design-icons/Plus.vue'
import IconCancel from '@mdi/svg/svg/cancel.svg'
import IconCheck from '@mdi/svg/svg/check.svg'
import logger from '../logger.js'
import { showError } from '@nextcloud/dialogs'
import { mapStores } from 'pinia'
import useMainStore from '../store/mainStore.js'

const sortByAddress = sortBy(prop('address'))

export default {
	name: 'InternalAddress',
	components: {
		ButtonVue,
		NcDialog,
		NcTextField,
		IconAdd,
	},

	data() {
		return {
			openDialog: false,
			newAddress: '',
			buttons: [
				{
					label: 'Cancel',
					icon: IconCancel,
					callback: () => { this.openDialog = false },
				},
				{
					label: 'Ok',
					type: 'primary',
					icon: IconCheck,
					callback: () => { this.addInternalAddress() },
				},
			],
		}
	},
	computed: {
		...mapStores(useMainStore),
		list() {
			return this.mainStore.getInternalAddresses
		},
		sortedDomains() {
			return sortByAddress(this.list.filter(a => a.type === 'domain'))
		},
		sortedEmails() {
			return sortByAddress(this.list.filter(a => a.type === 'individual'))
		},
	},
	methods: {
		async removeInternalAddress(sender) {
			// Remove the item immediately
			try {
				await this.mainStore.removeInternalAddress({ id: sender.id, address: sender.address, type: sender.type })
			} catch (error) {
				logger.error(`Could not remove internal address ${sender.email}`, {
					error,
				})
				showError(t('mail', 'Could not remove internal address {sender}', {
					sender: sender.address,
				}))
			}
		},
		async addInternalAddress() {
			const type = this.checkType()
			try {
				await this.mainStore.addInternalAddress({
					address: this.newAddress,
					type,
				}).then(async () => {
					this.newAddress = ''
					this.openDialog = false
				})
			} catch (error) {
				logger.error(`Could not add internal address ${this.newAddress}`, {
					error,
				})
				showError(t('mail', 'Could not add internal address {address}', {
					address: this.newAddress,
				}))
			}
		},
		checkType() {
			const parts = this.newAddress.split('@')
			if (parts.length !== 2) {
				return 'domain'
			}
			// remove '@'' from domain if added by mistake
			if (parts[0].length === 0) {
				this.newAddress = parts[1]
				return 'domain'
			}
			return 'individual'
		},
		senderType(type) {
			switch (type) {
			case 'individual':
				return t('mail', 'individual')
			case 'domain':
				return t('mail', 'domain')
			}
			return type
		},
	},
}
</script>

<style lang="scss" scoped>
.address {
	display: flex;
	align-items: center;
	&__type{
		color: var(--color-text-maxcontrast);
	}
}

</style>
