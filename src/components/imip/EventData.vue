<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="event-data">
		<h2 class="event-data__heading">
			{{ title }}
		</h2>

		<div class="event-data__row event-data__row--date">
			<CalendarIcon class="event-data__row__icon" :size="20" />
			<div>
				{{ startDate }}
				<span v-if="startTimezone && startTimezone !== endTimezone" class="muted">
					{{ startTimezone }}
				</span>
				<template v-if="endDate">
					<span> - </span>
					{{ endDate }}
					<span v-if="endTimezone" class="muted">{{ endTimezone }}</span>
				</template>
			</div>
		</div>

		<div v-if="location"
			class="event-data__row event-data__row--location">
			<MapMarkerIcon class="event-data__row__icon" :size="20" />
			<span>{{ location }}</span>
		</div>

		<div class="event-data__row event-data__row--participants">
			<AccountMultipleIcon class="event-data__row__icon" :size="20" />
			<ul>
				<li v-for="{ name, isOrganizer, key } in attendees" :key="key">
					{{ name }}
					<span v-if="isOrganizer" class="muted">{{ t('mail', '(organizer)') }}</span>
				</li>
			</ul>
		</div>
	</div>
</template>

<script>
import AccountMultipleIcon from 'vue-material-design-icons/AccountMultipleOutline.vue'
import CalendarIcon from 'vue-material-design-icons/CalendarOutline.vue'
import MapMarkerIcon from 'vue-material-design-icons/MapMarkerOutline.vue'
import { getReadableTimezoneName } from '@nextcloud/timezones'
import moment from '@nextcloud/moment'
import { removeMailtoPrefix } from '../../util/eventAttendee.js'
import { DateTimeValue } from '@nextcloud/calendar-js'

/**
 * Check whether two dates are on the exact same day, month and year.
 *
 * @param {Date} a Date a
 * @param {Date} b Date b
 * @return {boolean} True if both dates a and b are on the same day, month and year.
 */
function isSameDay(a, b) {
	return a.getFullYear() === b.getFullYear()
		&& a.getMonth() === b.getMonth()
		&& a.getDate() === b.getDate()
}

/**
 * Get a human readable timezone name from a DateTimeValue.
 * If timezone is floating, undefined will be returned.
 *
 * @param {DateTimeValue} date Date
 * @return {string|undefined} Human readable timezone name or undefined
 */
function getTimezoneFromDate(date) {
	const timezoneId = date.timezoneId
	if (!timezoneId || timezoneId === 'floating') {
		return undefined
	}

	return getReadableTimezoneName(timezoneId)
}

export default {
	name: 'EventData',
	components: {
		AccountMultipleIcon,
		CalendarIcon,
		MapMarkerIcon,
	},
	props: {
		event: {
			type: Object,
			required: true,
		},
	},
	computed: {
		/**
		 * @return {string}
		 */
		title() {
			// Use || here to handle empty strings as well
			return this.event.title || this.t('mail', 'Untitled event')
		},

		/**
		 * @return {string}
		 */
		startDate() {
			if (this.event.isAllDay()) {
				return moment(this.event.startDate.jsDate).format('ll')
			}

			return moment(this.event.startDate.jsDate).format('ll LT')
		},

		/**
		 * @return {string|undefined}
		 */
		endDate() {
			const start = this.event.startDate.jsDate
			const end = this.event.endDate.jsDate

			let date
			if (this.event.isAllDay()) {
				// All day events end a day later, so we need to subtract a day
				end.setDate(end.getDate() - 1)
				if (isSameDay(start, end)) {
					return undefined
				}
				date = moment(end).format('ll')
			} else {
				if (isSameDay(start, end)) {
					date = moment(end).format('LT')
				} else {
					date = moment(end).format('ll LT')
				}
			}

			return date
		},

		/**
		 * @return {string|undefined}
		 */
		startTimezone() {
			return getTimezoneFromDate(this.event.startDate)
		},

		/**
		 * @return {string|undefined}
		 */
		endTimezone() {
			return getTimezoneFromDate(this.event.endDate)
		},

		/**
		 * @return {string|undefined|null}
		 */
		location() {
			return this.event.location
		},

		/**
		 * @return {{name: string, isOrganizer: boolean, key: string}[]}
		 */
		attendees() {
			const attendees = []
			for (const attendee of [
				...this.event.getPropertyIterator('ORGANIZER'),
				...this.event.getAttendeeIterator(),
			]) {
				const name = attendee.commonName ?? removeMailtoPrefix(attendee.email)
				const isOrganizer = attendee.isOrganizer()
				const key = (isOrganizer ? 'organizer_' : 'attendee_') + name

				attendees.push({ name, isOrganizer, key })
			}
			return attendees
		},
	},
}
</script>

<style lang="scss" scoped>
.event-data {
	display: flex;
	flex-direction: column;
	gap: 5px;

	&__heading {
		margin-inline-start: 36px;
	}

	&__row {
		display: flex;

		&__icon {
			align-self: start;
			margin: 0 8px;

			// Fix slight misalignment caused by align-self: start
			padding-top: 2px;
		}
	}
}

.muted {
	color: var(--color-text-lighter);
}
</style>
