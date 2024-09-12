<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div>
		<input v-model="trashRetentionDays"
			type="number"
			min="0"
			@input="debouncedSave()">
		<p>
			{{ t('mail', 'Disable trash retention by leaving the field empty or setting it to 0. Only mails deleted after enabling trash retention will be processed.') }}
		</p>
	</div>
</template>

<script>
import debounce from 'lodash/fp/debounce.js'
import useMainStore from '../store/mainStore.js'
import { mapStores } from 'pinia'

export default {
	name: 'TrashRetentionSettings',
	props: {
		account: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			trashRetentionDays: this.account.trashRetentionDays,
			debouncedSave: debounce(1000, this.save),
		}
	},
	computed: {
		...mapStores(useMainStore),
	},
	methods: {
		async save() {
			let trashRetentionDays = parseInt(this.trashRetentionDays)
			if (isNaN(trashRetentionDays)) {
				// NaN probably means an empty input field, so we disable retention
				trashRetentionDays = 0
			}

			await this.mainStore.patchAccount({
				account: this.account,
				data: { trashRetentionDays },
			})
		},
	},
}
</script>
