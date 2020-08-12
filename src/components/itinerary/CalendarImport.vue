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
	<Actions v-if="calendars.length" default-icon="icon-add">
		<ActionButton
			v-for="(calendar, idx) in cals"
			:key="idx"
			:icon="calendar.loading ? 'icon-loading-small' : 'icon-add'"
			@click="onImport(calendar)">
			{{ t('mail', 'Import into {calendar}', {calendar: calendar.displayname}) }}
		</ActionButton>
	</Actions>
</template>

<script>
import Actions from '@nextcloud/vue/dist/Components/Actions'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'

export default {
	name: 'CalendarImport',
	components: {
		Actions,
		ActionButton,
	},
	props: {
		calendars: {
			type: Array,
			required: true,
		},
		handler: {
			type: Function,
			required: true,
		},
	},
	computed: {
		cals() {
			return this.calendars.map((original) => {
				this.$set(original, 'loading', false)
				return original
			})
		},
	},
	methods: {
		onImport(calendar) {
			calendar.loading = true

			this.handler(calendar)
				.catch(console.error.bind(this))
				.then(() => {
					calendar.loading = false
				})
		},
	},
}
</script>

<style scoped></style>
