<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div>
		<p>
			<input id="plaintext"
				v-model="mode"
				type="radio"
				class="radio"
				value="plaintext">
			<label :class="{primary: mode === 'plaintext'}" for="plaintext">
				{{ t('mail', 'Plain text') }}
			</label>
			<input id="richtext"
				v-model="mode"
				type="radio"
				class="radio"
				value="richtext">
			<label :class="{primary: mode === 'richtext'}" for="richtext">
				{{ t('mail', 'Rich text') }}
			</label>
		</p>
	</div>
</template>

<script>
import Logger from '../logger.js'
import useMainStore from '../store/mainStore.js'
import { mapStores } from 'pinia'

export default {
	name: 'EditorSettings',
	props: {
		account: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			mode: this.account.editorMode,
		}
	},
	computed: {
		...mapStores(useMainStore),
	},
	watch: {
		mode(val, oldVal) {
			this.mainStore.patchAccount({
				account: this.account,
				data: {
					editorMode: val,
				},
			})
				.then(() => {
					Logger.info('editor mode updated')
				})
				.catch((error) => {
					Logger.error('could not update editor mode', { error })
					this.editorMode = oldVal
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
</style>
