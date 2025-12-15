<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div>
		<NcListItem
			v-for="domain in sortedDomains"
			:key="domain.address">
			<template #name>
				{{ domain.address }}
			</template>
			<template #icon>
				<IconDomain v-if="domain.type === 'domain'" :size="20" :title="senderType(domain.type)" />
				<IconEmail v-if="domain.type === 'individual'" :size="20" :title="senderType(domain.type)" />
			</template>
			<template #extra-actions>
				<NcActionButton
					:title="t('mail', 'Remove')"
					:aria-label="t('mail', 'Remove')"
					@click="removeInternalAddress(domain)">
					<template #icon>
						<IconDelete :size="20" />
					</template>
				</NcActionButton>
			</template>
		</NcListItem>

		<NcListItem
			v-for="email in sortedEmails"
			:key="email.address">
			<template #name>
				{{ email.address }}
			</template>
			<template #icon>
				<IconDomain v-if="email.type === 'domain'" :size="20" :title="senderType(email.type)" />
				<IconEmail v-if="email.type === 'individual'" :size="20" :title="senderType(email.type)" />
			</template>
			<template #extra-actions>
				<NcActionButton
					:title="t('mail', 'Remove')"
					:aria-label="t('mail', 'Remove')"
					@click="removeInternalAddress(email)">
					<template #icon>
						<IconDelete :size="20" />
					</template>
				</NcActionButton>
			</template>
		</NcListItem>

		<ButtonVue
			type="secondary"
			wide
			@click="openDialog = true">
			<template #icon>
				<IconAdd :size="20" />
			</template>
			{{ t('mail', 'Add internal address') }}
		</ButtonVue>
		<NcDialog
			:open.sync="openDialog"
			:buttons="buttons"
			:name="t('mail', 'Add internal address')"
			@close="openDialog = false">
			<NcTextField class="input" :label="t('mail', 'Add internal email or domain')" :value.sync="newAddress" />
		</NcDialog>
	</div>
</template>

<script>

import IconCancel from '@mdi/svg/svg/cancel.svg'
import IconCheck from '@mdi/svg/svg/check.svg'
import { showError } from '@nextcloud/dialogs'
import { NcButton as ButtonVue, NcActionButton, NcDialog, NcListItem, NcTextField } from '@nextcloud/vue'
import prop from 'lodash/fp/prop.js'
import sortBy from 'lodash/fp/sortBy.js'
import { mapStores } from 'pinia'
import IconDomain from 'vue-material-design-icons/Domain.vue'
import IconEmail from 'vue-material-design-icons/EmailOutline.vue'
import IconAdd from 'vue-material-design-icons/Plus.vue'
import IconDelete from 'vue-material-design-icons/TrashCanOutline.vue'
import logger from '../logger.js'
import useMainStore from '../store/mainStore.js'

const sortByAddress = sortBy(prop('address'))

export default {
	name: 'InternalAddress',
	components: {
		ButtonVue,
		NcDialog,
		NcTextField,
		NcActionButton,
		NcListItem,
		IconDomain,
		IconEmail,
		IconDelete,
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
			return sortByAddress(this.list.filter((a) => a.type === 'domain'))
		},

		sortedEmails() {
			return sortByAddress(this.list.filter((a) => a.type === 'individual'))
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
