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
	<Actions v-if="calendars.length">
		<template #icon>
			<IconAdd :size="20" />
		</template>
		<ActionButton
			v-for="(calendar, idx) in cals"
			:key="idx"
			@click="onImport(calendar)">
			<template #icon>
				<IconLoading v-if="calendar.loading" :size="20" />
				<IconAdd v-else :size="20" />
			</template>
			{{ t('mail', 'Import into {calendar}', {calendar: calendar.displayname}) }}
		</ActionButton>
	</Actions>
</template>

<script>

import { NcActions as Actions, NcActionButton as ActionButton, NcLoadingIcon as IconLoading } from '@nextcloud/vue'

import IconAdd from 'vue-material-design-icons/Plus'
import ical from 'ical.js'
import moment from '@nextcloud/moment'

export default {
	name: 'CalendarImport',
	components: {
		Actions,
		ActionButton,
		IconAdd,
		IconLoading,
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

	itineraryDateTime(dt) {
		if (typeof dt === 'string') {
			return dt
		}
		return dt['@value']
	},

	addIcalTimeProperty(icalEvent, itineraryDt, icalPropertyName) {
		const t = moment(this.itineraryDateTime(itineraryDt)).format()
		const prop = icalEvent.updatePropertyWithValue(icalPropertyName, ical.Time.fromDateTimeString(t))
		if (typeof itineraryDt !== 'string') {
			prop.setParameter('TZID', itineraryDt.timezone)
		}
	},
}
</script>

<style scoped></style>
