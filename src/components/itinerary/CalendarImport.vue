<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<Actions v-if="calendars.length">
		<template #icon>
			<IconAdd :size="20" />
		</template>
		<ActionButton v-for="(calendar, idx) in cals"
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

import IconAdd from 'vue-material-design-icons/Plus.vue'
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
