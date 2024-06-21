<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div>
		<template v-for="(entry, idx) in entries">
			<EventReservation v-if="entry['@type'] === 'EventReservation'"
				:key="idx"
				:data="entry"
				:calendars="calendars"
				:message-id="messageId" />
			<FlightReservation v-else-if="entry['@type'] === 'FlightReservation'"
				:key="idx"
				:data="entry"
				:calendars="calendars"
				:message-id="messageId" />
			<TrainReservation v-else-if="entry['@type'] === 'TrainReservation'"
				:key="idx"
				:data="entry"
				:calendars="calendars"
				:message-id="messageId" />
			<span v-else :key="idx">{{
				t('mail', 'Itinerary for {type} is not supported yet', {type: entry['@type']})
			}}</span>
		</template>
	</div>
</template>

<script>
import once from 'lodash/fp/once.js'

import { getUserCalendars } from '../service/DAVService.js'
import logger from '../logger.js'
import EventReservation from './itinerary/EventReservation.vue'
import FlightReservation from './itinerary/FlightReservation.vue'
import TrainReservation from './itinerary/TrainReservation.vue'

const getUserCalendarsOnce = once(getUserCalendars)

export default {
	name: 'Itinerary',
	components: {
		EventReservation,
		FlightReservation,
		TrainReservation,
	},
	props: {
		entries: {
			type: Array,
			required: true,
		},
		messageId: {
			type: String,
			required: true,
		},
	},
	data() {
		return {
			calendars: [],
		}
	},
	mounted() {
		getUserCalendarsOnce()
			.then((calendars) => (this.calendars = calendars))
			.catch((error) => logger.error('Could not load calendars', { error }))
	},
}
</script>
