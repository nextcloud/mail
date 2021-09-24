<template>
	<div class="reservation">
		<div class="departure">
			<div class="iata">
				{{ data.reservationFor.departureAirport.iataCode }}
			</div>
			<div class="airport">
				{{ data.reservationFor.departureAirport.name }}
			</div>
			<div v-if="departureDate">
				{{ departureDate }}
			</div>
			<div v-if="departureTime">
				{{ departureTime }}
			</div>
		</div>
		<div class="connection">
			<div><AirplaneIcon :title="t('mail', 'Airplane')" /></div>
			<div>{{ flightNumber }}</div>
			<div v-if="reservation">
				{{ t('mail', 'Reservation {id}', {id: reservation}) }}
			</div>
			<div v-else>
				<ArrowIcon decorative />
			</div>
		</div>
		<div class="arrival">
			<div class="iata">
				{{ data.reservationFor.arrivalAirport.iataCode }}
			</div>
			<div class="airport">
				{{ data.reservationFor.arrivalAirport.name }}
			</div>
			<div v-if="arrivalDate">
				{{ arrivalDate }}
			</div>
			<div v-if="arrivalTime">
				{{ arrivalTime }}
			</div>
		</div>
		<CalendarImport :calendars="calendars" :handler="handleImport" />
	</div>
</template>

<script>
import AirplaneIcon from 'vue-material-design-icons/Airplane'
import ArrowIcon from 'vue-material-design-icons/ArrowRight'
import ical from 'ical.js'
import md5 from 'md5'
import moment from '@nextcloud/moment'
import { showError, showSuccess } from '@nextcloud/dialogs'

import CalendarImport from './CalendarImport'
import { importCalendarEvent } from '../../service/DAVService'
import logger from '../../logger'

export default {
	name: 'FlightReservation',
	components: {
		AirplaneIcon,
		ArrowIcon,
		CalendarImport,
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
			if (!('departureTime' in this.data.reservationFor)) {
				return
			}
			return moment(CalendarImport.itineraryDateTime(this.data.reservationFor.departureTime)).format('L')
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
		flightNumber() {
			return this.data.reservationFor.airline.iataCode + this.data.reservationFor.flightNumber
		},
		reservation() {
			if (!('reservationNumber' in this.data)) {
				return
			}
			return this.data.reservationNumber
		},
		canImport() {
			return 'departureTime' in this.data.reservationFor && 'arrivalTime' in this.data.reservationFor
		},
	},
	methods: {
		handleImport(calendar) {
			const event = new ical.Component('VEVENT')
			event.updatePropertyWithValue(
				'SUMMARY',
				t('mail', 'Flight {flightNr} from {depAirport} to {arrAirport}', {
					flightNr: this.flightNumber,
					depAirport: this.data.reservationFor.departureAirport.iataCode,
					arrAirport: this.data.reservationFor.arrivalAirport.iataCode,
				})
			)

			CalendarImport.addIcalTimeProperty(event, this.data.reservationFor.departureTime, 'DTSTART')
			CalendarImport.addIcalTimeProperty(event, this.data.reservationFor.arrivalTime, 'DTEND')

			// TODO: read version from package.json
			event.updatePropertyWithValue('PRODID', 'Nextcloud Mail')

			// TODO: is this free of collisions? the bug reports will tell us!
			event.updatePropertyWithValue('UID', md5(this.messageId + this.flightNumber))

			const cal = new ical.Component('VCALENDAR')
			cal.addSubcomponent(event)
			logger.debug('generated calendar event from flight reservation data', { ical: cal.toString() })

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

.iata {
	font-size: larger;
	font-weight: bold;
}

.airport {
	font-size: large;
}

.departure {
	text-align: right;
}

.connection {
	text-align: center;
	padding: 0 40px;
}
</style>
