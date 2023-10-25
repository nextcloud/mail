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
import Logger from '../logger'

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
	watch: {
		searchBody(val, oldVal) {
			this.$store
				.dispatch('patchAccount', {
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
	padding-right: 12px;
}

div{
	display: flex;
	align-items: center;
}
</style>
