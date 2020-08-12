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
		<h2>{{ t('mail', 'Writing mode') }}</h2>
		<p class="settings-hint">
			{{ t('mail', 'Preferred writing mode for new messages and replies.') }}
		</p>
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
import Logger from '../logger'

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
	watch: {
		mode(val, oldVal) {
			this.$store
				.dispatch('patchAccount', {
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
.settings-hint {
	margin-top: -12px;
	margin-bottom: 6px;
	color: var(--color-text-maxcontrast);
}

label {
	padding-right: 12px;
}
</style>
