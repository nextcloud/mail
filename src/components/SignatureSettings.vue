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
	<div>
		<h3>{{ t('mail', 'Signature') }}</h3>
		<p>
			{{ t('mail', 'A signature will be added to the text of new and response messages.') }}
		</p>
		<p>
			<textarea v-model="signature" :disabled="loading"></textarea>
		</p>
		<p>
			<input type="submit" :value="t('mail', 'Delete')" :disabled="loading" @click="deleteSignature" />
			<input
				type="submit"
				class="primary"
				:value="t('mail', 'Save')"
				:disabled="loading"
				@click="saveSignature"
			/>
		</p>
	</div>
</template>

<script>
import Logger from '../logger'

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
