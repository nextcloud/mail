<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="reservation">
		<div class="event">
			<div class="event-name">
				{{ eventName }}
			</div>
			<div v-if="location" class="venue">
				{{ location }}
			</div>
			<div v-if="date">
				{{ date }}
			</div>
			<div v-if="time">
				{{ time }}
			</div>
		</div>
		<CalendarImport v-if="canImport" :calendars="calendars" :handler="handleImport" />
	</div>
</template>

<script>
import ical from 'ical.js'
import md5 from 'md5'
import moment from '@nextcloud/moment'
import { showError, showSuccess } from '@nextcloud/dialogs'

import CalendarImport from './CalendarImport.vue'
import { importCalendarEvent } from '../../service/DAVService.js'
import logger from '../../logger.js'

export default {
	name: 'EventReservation',
	components: { CalendarImport },
	props: {
		data: {
			type: Object,
			required: true,
		},
		calendars: {
			type: Array,
			required: true,
		},
		messageId: {
			type: String,
			required: true,
		},
	},
	computed: {
		eventName() {
			return this.data.reservationFor.name
		},
		time() {
			if (!('startDate' in this.data.reservationFor)) {
				return
			}
			return moment(CalendarImport.itineraryDateTime(this.data.reservationFor.startDate)).format('LT')
		},
		date() {
			if (!('startDate' in this.data.reservationFor)) {
				return
			}
			return moment(CalendarImport.itineraryDateTime(this.data.reservationFor.startDate)).format('L')
		},
		location() {
			if (!('location' in this.data.reservationFor) || !('name' in this.data.reservationFor.location)) {
				return
			}
			return this.data.reservationFor.location.name
		},
		canImport() {
			return 'startDate' in this.data.reservationFor
		},
	},
	methods: {
		getEndDateTime(event) {
			if ('endDate' in this.data.reservationFor) {
				return moment(CalendarImport.itineraryDateTime(this.data.reservationFor.endDate)).format()
			} else if ('startDate' in this.data.reservationFor) {
				// Assume it's 2h and user will adjust if necessary
				// TODO: handle 'duration' https://schema.org/Event
				return moment(CalendarImport.itineraryDateTime(this.data.reservationFor.startDate)).add(2, 'hours').format()
			}
		},
		handleImport(calendar) {
			const event = new ical.Component('VEVENT')
			event.updatePropertyWithValue('SUMMARY', this.eventName)

			const start = moment(CalendarImport.itineraryDateTime(this.data.reservationFor.startDate)).format()
			event.updatePropertyWithValue('DTSTART', ical.Time.fromDateTimeString(start))
			const end = this.getEndDateTime(this.data.reservationFor)
			event.updatePropertyWithValue('DTEND', ical.Time.fromDateTimeString(end))

			if ('location' in this.data.reservationFor) {
				event.updatePropertyWithValue('LOCATION', this.data.reservationFor.location.name)
				if ('geo' in this.data.reservationFor.location) {
					// https://www.kanzaki.com/docs/ical/geo.html
					event.updatePropertyWithValue(
						'GEO',
						`${this.data.reservationFor.location.geo.latitude};${this.data.reservationFor.location.geo.longitude}`,
					)
				}
			}

			// TODO: read version from package.json
			event.updatePropertyWithValue('PRODID', 'Nextcloud Mail')

			// TODO: is this free of collisions? the bug reports will tell us!
			event.updatePropertyWithValue('UID', md5(this.messageId + this.eventName))

			const cal = new ical.Component('VCALENDAR')
			cal.addSubcomponent(event)
			logger.debug('generated calendar event from event reservation data', { ical: cal.toString() })
			return importCalendarEvent(calendar.url)(cal.toString())
				.then(() => {
					logger.debug('event successfully imported')
					showSuccess(t('mail', 'Event imported into {calendar}', { calendar: calendar.displayname }))
				})
				.catch((error) => {
					logger.error('Could not import event', { error })
					showError(t('mail', 'Could not create event'))
				})
		},
	},
}
</script>

<style scoped>
.reservation {
	display: flex;
	flex-direction: row;
	margin: 30px 38px;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	padding: 20px;
	align-items: center;
}

.event {
	flex-grow: 1;
}

.event-name {
	font-size: larger;
	font-weight: bold;
}
</style>
