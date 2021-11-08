<template>
	<div class="reservation">
		<div class="departure">
			<div class="station">
				{{ data.reservationFor.departureStation.name }}
			</div>
			<div v-if="departureDate">
				{{ departureDate }}
			</div>
			<div v-if="departureTime">
				{{ departureTime }}
			</div>
		</div>
		<div class="connection">
			<div><TrainIcon :title="t('mail', 'Train')" /></div>
			<div>{{ trainNumber }}</div>
			<div><ArrowIcon decorative /></div>
		</div>
		<div class="arrival">
			<div class="station">
				{{ data.reservationFor.arrivalStation.name }}
			</div>
			<div v-if="arrivalDate">
				{{ arrivalDate }}
			</div>
			<div v-if="arrivalTime">
				{{ arrivalTime }}
			</div>
		</div>
		<CalendarImport v-if="canImport" :calendars="calendars" :handler="handleImport" />
	</div>
</template>

<script>
import ArrowIcon from 'vue-material-design-icons/ArrowRight'
import ical from 'ical.js'
import md5 from 'md5'
import moment from '@nextcloud/moment'
import { showError, showSuccess } from '@nextcloud/dialogs'
import TrainIcon from 'vue-material-design-icons/Train'

import CalendarImport from './CalendarImport'
import { importCalendarEvent } from '../../service/DAVService'
import logger from '../../logger'

export default {
	name: 'TrainReservation',
	components: {
		ArrowIcon,
		CalendarImport,
		TrainIcon,
	},
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
		departureTime() {
			if (!('departureTime' in this.data.reservationFor)) {
				return
			}
			return moment(CalendarImport.itineraryDateTime(this.data.reservationFor.departureTime)).format('LT')
		},
		departureDate() {
			if ('departureTime' in this.data.reservationFor) {
				return moment(CalendarImport.itineraryDateTime(this.data.reservationFor.departureTime)).format('L')
			}
			if ('departureDay' in this.data.reservationFor) {
				return moment(this.data.reservationFor.departureDay).format('L')
			}
			return undefined
		},
		arrivalTime() {
			if (!('arrivalTime' in this.data.reservationFor)) {
				return
			}
			return moment(CalendarImport.itineraryDateTime(this.data.reservationFor.arrivalTime)).format('LT')
		},
		arrivalDate() {
			if (!('arrivalTime' in this.data.reservationFor)) {
				return
			}
			return moment(CalendarImport.itineraryDateTime(this.data.reservationFor.arrivalTime)).format('L')
		},
		trainNumber() {
			return this.data.reservationFor.trainNumber
		},
		canImport() {
			return (
				('departureTime' in this.data.reservationFor && 'arrivalTime' in this.data.reservationFor)
				|| 'departureDay' in this.data.reservationFor
			)
		},
	},
	methods: {
		handleImport(calendar) {
			const event = new ical.Component('VEVENT')
			if ('trainNumber' in this.data.reservationFor) {
				event.updatePropertyWithValue(
					'SUMMARY',
					t('mail', '{trainNr} from {depStation} to {arrStation}', {
						trainNr: this.data.reservationFor.trainNumber,
						depStation: this.data.reservationFor.departureStation.name,
						arrStation: this.data.reservationFor.arrivalStation.name,
					})
				)
			} else {
				event.updatePropertyWithValue(
					'SUMMARY',
					t('mail', 'Train from {depStation} to {arrStation}', {
						depStation: this.data.reservationFor.departureStation.name,
						arrStation: this.data.reservationFor.arrivalStation.name,
					})
				)
			}

			if ('departureTime' in this.data.reservationFor && 'arrivalTime' in this.data.reservationFor) {
				CalendarImport.addIcalTimeProperty(event, this.data.reservationFor.departureTime, 'DTSTART')
				CalendarImport.addIcalTimeProperty(event, this.data.reservationFor.arrivalTime, 'DTEND')
			} else if ('departureDay' in this.data.reservationFor) {
				const date = moment(this.data.reservationFor.departureDay).format()
				event.updatePropertyWithValue('DTSTART', ical.Time.fromDateTimeString(date))
			}

			// TODO: read version from package.json
			event.updatePropertyWithValue('PRODID', 'Nextcloud Mail')

			// TODO: is this free of collisions? the bug reports will tell us!
			event.updatePropertyWithValue('UID', md5(this.messageId + this.departureTime))

			const cal = new ical.Component('VCALENDAR')
			cal.addSubcomponent(event)
			logger.debug('generated calendar event from train reservation data', { ical: cal.toString() })

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

.departure,
.arrival {
	display: flex;
	flex-direction: column;
	flex-grow: 1;
}

.departure,
.arrival,
.connection {
	justify-content: center;
}

.station {
	font-size: larger;
	font-weight: bold;
}

.departure {
	text-align: right;
}

.connection {
	text-align: center;
	padding: 0 40px;
}
</style>
