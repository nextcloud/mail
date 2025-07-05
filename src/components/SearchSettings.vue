<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div>
		<input id="searchBody"
			v-model="searchBody"
			type="checkbox">
		<label for="searchBody">
			{{ t('mail', 'Enable mail body search') }}
		</label>
	</div>
</template>

<script>
import Logger from '../logger.js'
import useMainStore from '../store/mainStore.js'
import { mapStores } from 'pinia'

export default {
	name: 'SearchSettings',
	props: {
		account: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			searchBody: this.account.searchBody,
		}
	},
	computed: {
		...mapStores(useMainStore),
	},
	watch: {
		searchBody(val, oldVal) {
			this.mainStore.patchAccount({
				account: this.account,
				data: {
					searchBody: val,
				},
			})
				.then(() => {
					Logger.info(`Body search ${val ? 'enabled' : 'disabled'}`)
				})
				.catch((error) => {
					Logger.error(`could not ${val ? 'enable' : 'disable'} body search`, { error })
					this.searchBody = oldVal
					throw error
				})
		},
	},
}
</script>

<style lang="scss" scoped>

label {
	padding-inline-end: 12px;
}

div{
	display: flex;
	align-items: center;
}
</style>
