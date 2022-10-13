<!--
  - @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @license AGPL-3.0-or-later
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
	<div class="section">
		<div>
			<input
				id="signature-above-quote-toggle"
				v-model="signatureAboveQuote"
				type="checkbox"
				class="checkbox">
			<label for="signature-above-quote-toggle">
				{{ t("mail", "Place signature above quoted text") }}
			</label>
		</div>
		<Multiselect
			v-if="identities.length > 1"
			:allow-empty="false"
			:options="identities"
			:searchable="false"
			:value="identity"
			label="label"
			track-by="id"
			@select="changeIdentity" />
		<TextEditor
			v-model="signature"
			:html="true"
			:placeholder="t('mail', 'Signature …')"
			:bus="bus" />
		<p v-if="isLargeSignature" class="warning-large-signature">
			{{ t('mail', 'Your signature is larger than 2 MB. This may affect the performance of your editor.') }}
		</p>
		<ButtonVue
			type="primary"
			:disabled="loading"
			@click="saveSignature">
			<template #icon>
				<IconLoading v-if="loading" :size="20" fill-color="white" />
				<IconCheck v-else :size="20" />
			</template>
			{{ t('mail', 'Save signature') }}
		</ButtonVue>
		<ButtonVue v-if="signature"
			type="tertiary-no-background"
			class="button-text"
			@click="deleteSignature">
			{{ t('mail', 'Delete') }}
		</ButtonVue>
	</div>
</template>

<script>
import logger from '../logger'
import TextEditor from './TextEditor'
import { detect, toHtml } from '../util/text'
import Vue from 'vue'

import { NcMultiselect as Multiselect, NcButton as ButtonVue, NcLoadingIcon as IconLoading } from '@nextcloud/vue'
import IconCheck from 'vue-material-design-icons/Check'

export default {
	name: 'SignatureSettings',
	components: {
		TextEditor,
		Multiselect,
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
			bus: new Vue(),
			identity: null,
			signature: '',
			signatureAboveQuote: this.account.signatureAboveQuote,
		}
	},
	computed: {
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
				await this.$store.dispatch('patchAccount', {
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

			let dispatchType = 'updateAccountSignature'
			const payload = {
				account: this.account,
				signature: this.signature,
			}

			if (this.identity.id > -1) {
				dispatchType = 'updateAliasSignature'
				payload.aliasId = this.identity.id
			}

			return this.$store
				.dispatch(dispatchType, payload)
				.then(() => {
					logger.info('signature updated')
					this.loading = false
				})
				.catch((error) => {
					logger.error('could not update account signature', { error })
					throw error
				})
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
  padding-left: 26px;
  background-position: 6px;
  color: var(--color-main-background);

  &:after {
    left: 14px;
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
.multiselect--single {
  width: 100%;
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
</style>
