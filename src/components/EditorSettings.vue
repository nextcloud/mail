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
		<h3>{{ t('mail', 'Editor') }}</h3>
		<p>
			{{ t('mail', 'Configure your preferred editing mode for new messages and replies.') }}
			<br />
			<Multiselect v-model="mode" :options="options" track-by="name" label="label" />
		</p>
	</div>
</template>

<script>
import Multiselect from '@nextcloud/vue/dist/Components/Multiselect'

import Logger from '../logger'

export default {
	name: 'EditorSettings',
	components: {
		Multiselect,
	},
	props: {
		account: {
			type: Object,
			required: true,
		},
	},
	data() {
		const options = [
			{
				name: 'richtext',
				label: t('mail', 'Rich text'),
			},
			{
				name: 'plaintext',
				label: t('mail', 'Plain text'),
			},
		]

		return {
			mode: options.find(o => o.name === this.account.editorMode),
			options,
		}
	},
	watch: {
		mode(val, oldVal) {
			this.$store
				.dispatch('patchAccount', {
					account: this.account,
					data: {
						editorMode: val.name,
					},
				})
				.then(() => {
					Logger.info('editor mode updated')
				})
				.catch(error => {
					Logger.error('could not upate editor mode', {error})
					this.editorMode = oldVal
					throw error
				})
		},
	},
}
</script>
