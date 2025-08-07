<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="section">
		<div>
			<input id="signature-above-quote-toggle"
				v-model="signatureAboveQuote"
				type="checkbox"
				class="checkbox">
			<label for="signature-above-quote-toggle">
				{{ t("mail", "Place signature above quoted text") }}
			</label>
		</div>
		<NcSelect v-if="identities.length > 1"
			:allow-empty="false"
			:options="identities"
			:aria-label-combobox="t('mail','Select an alias')"
			:searchable="false"
			:value="identity"
			label="label"
			track-by="id"
			@option:selected="changeIdentity" />
		<TextEditor v-model="signature"
			:html="true"
			:placeholder="t('mail', 'Signature …')"
			:bus="bus"
			@show-toolbar="handleShowToolbar" />
		<p v-if="isLargeSignature" class="warning-large-signature">
			{{ t('mail', 'Your signature is larger than 2 MB. This may affect the performance of your editor.') }}
		</p>
		<ButtonVue type="primary"
			:disabled="loading"
			:aria-label="t('mail', 'Save signature')"
			@click="saveSignature">
			<template #icon>
				<IconLoading v-if="loading" :size="20" fill-color="white" />
				<IconCheck v-else :size="20" />
			</template>
			{{ t('mail', 'Save signature') }}
		</ButtonVue>
		<ButtonVue v-if="signature"
			:aria-label="t('mail', 'Delete')"
			type="tertiary-no-background"
			class="button-text"
			@click="deleteSignature">
			{{ t('mail', 'Delete') }}
		</ButtonVue>
	</div>
</template>

<script>
import logger from '../logger.js'
import TextEditor from './TextEditor.vue'
import { detect, toHtml } from '../util/text.js'
import mitt from 'mitt'

import { NcSelect, NcButton as ButtonVue, NcLoadingIcon as IconLoading } from '@nextcloud/vue'
import IconCheck from 'vue-material-design-icons/Check.vue'
import useMainStore from '../store/mainStore.js'
import { mapStores } from 'pinia'

export default {
	name: 'SignatureSettings',
	components: {
		TextEditor,
		NcSelect,
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
			loading: false,
			bus: mitt(),
			identity: null,
			signature: '',
			signatureAboveQuote: this.account.signatureAboveQuote,
		}
	},
	computed: {
		...mapStores(useMainStore),
		identities() {
			const identities = this.account.aliases.map((alias) => {
				return {
					id: alias.id,
					label: alias.name + ' (' + alias.alias + ')',
					signature: alias.signature,
				}
			})

			identities.unshift({
				id: -1,
				label: this.account.name + ' (' + this.account.emailAddress + ')',
				signature: this.account.signature,
			})

			return identities
		},
		isLargeSignature() {
			return (new Blob([this.signature])).size > 2 * 1024 * 1024
		},
	},
	watch: {
		async signatureAboveQuote(val, oldVal) {
			try {
				await this.mainStore.patchAccount({
					account: this.account,
					data: {
						signatureAboveQuote: val,
					},
				})
				logger.debug('signature above quoted updated to ' + val)
			} catch (e) {
				logger.error('could not update signature above quote', { e })
				this.signatureAboveQuote = oldVal
			}
		},
	},
	beforeMount() {
		this.changeIdentity(this.identities[0])
	},
	methods: {
		changeIdentity(identity) {
			logger.debug('select identity', { identity })
			this.identity = identity
			this.signature = identity.signature
				? toHtml(detect(identity.signature)).value
				: ''
		},
		async deleteSignature() {
			this.signature = null
			await this.saveSignature()
		},
		async saveSignature() {
			this.loading = true

			const payload = {
				account: this.account,
				signature: this.signature,
			}

			if (this.identity.id > -1) {
				payload.aliasId = this.identity.id
				return this.mainStore.updateAliasSignature(payload)
					.then(() => {
						logger.info('signature updated')
						this.loading = false
					})
					.catch((error) => {
						logger.error('could not update account signature', { error })
						throw error
					})
			}

			return this.mainStore.updateAccountSignature(payload)
				.then(() => {
					logger.info('signature updated')
					this.loading = false
				})
				.catch((error) => {
					logger.error('could not update account signature', { error })
					throw error
				})
		},
		handleShowToolbar(event) {
			this.$emit('show-toolbar', event)
		},
	},
}
</script>

<style lang="scss" scoped>
.ck.ck-editor__editable_inline {
  width: 100%;
  max-width: 78vw;
  height: 100%;
  min-height: 100px;
  border-radius: var(--border-radius) !important;
  border: 1px solid var(--color-border) !important;
  box-shadow: none !important;
}

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

.section {
  display: block;
  padding: 0;
  margin-bottom: 23px;
}

.ck-balloon-panel {
	 z-index: 10000 !important;
 }

.button-vue:deep() {
	display: inline-block !important;
	margin-top: 4px !important;
}

.warning-large-signature {
	color: darkorange;
}

:deep(.ck.ck-toolbar-dropdown>.ck-dropdown__panel) {
	max-width: 34vw;
}

</style>
