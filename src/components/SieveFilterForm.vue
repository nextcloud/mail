<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="section">
		<textarea id="sieve-text-area"
			v-model="script"
			v-shortkey.avoid
			rows="20"
			:disabled="loading" />
		<p v-if="errorMessage">
			{{ t('mail', 'Oh Snap!') }}
			{{ errorMessage }}
		</p>
		<ButtonVue type="primary"
			:disabled="loading"
			:aria-label="t('mail', 'Save sieve script')"
			@click="saveActiveScript">
			<template #icon>
				<IconLoading v-if="loading" :size="20" />
				<IconCheck v-else :size="20" />
			</template>
			{{ t('mail', 'Save sieve script') }}
		</ButtonVue>
	</div>
</template>

<script>
import { NcButton as ButtonVue, NcLoadingIcon as IconLoading } from '@nextcloud/vue'
import IconCheck from 'vue-material-design-icons/Check.vue'
import { mapStores } from 'pinia'
import useMainStore from '../store/mainStore.js'

export default {
	name: 'SieveFilterForm',
	components: {
		ButtonVue,
		IconLoading,
		IconCheck,
	},
	props: {
		account: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			script: '',
			loading: true,
			errorMessage: '',
		}
	},
	computed: {
		...mapStores(useMainStore),
		scriptData() {
			return this.mainStore.getActiveSieveScript(this.account.id)
		},
	},
	watch: {
		scriptData: {
			immediate: true,
			handler(scriptData) {
				if (!scriptData) {
					return
				}

				this.script = scriptData.script
				this.loading = false
			},
		},
	},
	methods: {
		async saveActiveScript() {
			this.loading = true
			this.errorMessage = ''

			try {
				await this.mainStore.updateActiveSieveScript({
					accountId: this.account.id,
					scriptData: {
						...this.scriptData,
						script: this.script,
					},
				})
			} catch (error) {
				if (error.response.status === 422) {
					this.errorMessage = t('mail', 'The syntax seems to be incorrect:') + ' ' + error.response.data.message
				} else {
					this.errorMessage = error.message
				}
			}

			this.loading = false
		},
	},
}
</script>

<style lang="scss" scoped>
.section {
	display: block;
	padding: 0;
	margin-bottom: 23px;
}

textarea {
	width: 100%;
	resize: vertical;
}

.primary {
	padding-inline-start: 26px;
	background-position: 6px;
	color: var(--color-main-background);

	&:after {
		 inset-inline-start: 14px;
	 }
}
</style>
