<!--
  - @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @license GNU AGPL version 3 or any later version
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
		<h2>{{ t('mail', 'Signature') }}</h2>
		<p class="settings-hint">
			{{ t('mail', 'A signature is added to the text of new messages and replies.') }}
		</p>
		<textarea v-model="signature" v-autosize="signature" />
		<p>
			<button
				class="primary"
				:class="loading ? 'icon-loading-small-dark' : 'icon-checkmark-white'"
				:disabled="loading"
				@click="saveSignature"
			>
				{{ t('mail', 'Save signature') }}
			</button>
			<button v-if="signature" class="button-text" @click="deleteSignature">
				{{ t('mail', 'Delete') }}
			</button>
		</p>
	</div>
</template>

<script>
import Vue from 'vue'
import Autosize from 'vue-autosize'
import Logger from '../logger'

Vue.use(Autosize)

export default {
	name: 'SignatureSettings',
	props: {
		account: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			loading: false,
			signature: this.account.signature,
		}
	},
	methods: {
		deleteSignature() {
			this.loading = true

			this.$store
				.dispatch('updateAccountSignature', {account: this.account, signature: null})
				.then(() => {
					Logger.info('signature deleted')
					this.signature = ''
					this.loading = false
				})
				.catch(error => {
					Logger.error('could not delete account signature', {error})
					throw error
				})
		},
		saveSignature() {
			this.loading = true

			this.$store
				.dispatch('updateAccountSignature', {account: this.account, signature: this.signature})
				.then(() => {
					Logger.info('signature updated')
					this.loading = false
				})
				.catch(error => {
					Logger.error('could not update account signature', {error})
					throw error
				})
		},
	},
}
</script>

<style lang="scss" scoped>
.settings-hint {
	margin-top: -12px;
	margin-bottom: 6px;
	color: var(--color-text-maxcontrast);
}

textarea {
	display: block;
	width: 400px;
	max-width: 85vw;
	height: 100px;
	resize: none;
}

.primary {
	padding-left: 26px;
	background-position: 6px;

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
</style>
