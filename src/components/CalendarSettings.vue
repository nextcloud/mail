<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div>
		<NcCheckboxRadioSwitch
			id="imip-create"
			:checked="imipCreate"
			@update:checked="onToggleImipCreate">
			{{ t('mail', 'Automatically create tentative appointments in calendar') }}
		</NcCheckboxRadioSwitch>
	</div>
</template>

<script>
import { NcCheckboxRadioSwitch } from '@nextcloud/vue'
import { mapStores } from 'pinia'
import Logger from '../logger.js'
import useMainStore from '../store/mainStore.js'

export default {
	name: 'CalendarSettings',
	components: {
		NcCheckboxRadioSwitch,
	},
	props: {
		account: {
			type: Object,
			required: true,
		},
	},

	data() {
		return {
			imipCreate: this.account.imipCreate,
		}
	},

	computed: {
		...mapStores(useMainStore),
	},

	methods: {
		async onToggleImipCreate(val) {
			const oldVal = this.imipCreate
			this.imipCreate = val

			try {
				await this.mainStore.patchAccount({
					account: this.account,
					data: {
						imipCreate: val,
					},
				})
				Logger.info(`Automatic calendar appointment creation ${val ? 'enabled' : 'disabled'}`)
			} catch (error) {
				Logger.error(`could not ${val ? 'enable' : 'disable'} automatic calendar appointment creation`, { error })
				this.imipCreate = oldVal
				throw error
			}
		},
	},
}
</script>

<style lang="scss" scoped>
</style>
