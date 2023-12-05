<!--
  - @copyright Copyright (c) 2023 Richard Steinmetz <richard@steinmetz.cloud>
  -
  - @author Richard Steinmetz <richard@steinmetz.cloud>
  -
  - @license AGPL-3.0-or-later
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation, either version 3 of the License, or
  - (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<div>
		<input v-model="trashRetentionDays"
			type="number"
			min="0"
			@input="debouncedSave()">
		<p>
			{{ t('mail', 'Disable trash retention by leaving the field empty or setting it to 0. Only mails deleted after enabling trash retention will be processed.') }}
		</p>
	</div>
</template>

<script>
import debounce from 'lodash/fp/debounce.js'

export default {
	name: 'TrashRetentionSettings',
	props: {
		account: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			trashRetentionDays: this.account.trashRetentionDays,
			debouncedSave: debounce(1000, this.save),
		}
	},
	methods: {
		async save() {
			let trashRetentionDays = parseInt(this.trashRetentionDays)
			if (isNaN(trashRetentionDays)) {
				// NaN probably means an empty input field, so we disable retention
				trashRetentionDays = 0
			}

			await this.$store.dispatch('patchAccount', {
				account: this.account,
				data: { trashRetentionDays },
			})
		},
	},
}
</script>
