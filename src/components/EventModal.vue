<template>
	<Modal @close="onClose">
		<div class="modal-content">
			<h2>{{ t('mail', 'Create event') }}</h2>
			<div class="eventTitle">
				<input v-model="eventTitle" type="text">
			</div>
			<div class="dateTimePicker">
				<DatetimePicker v-model="startDate"
					:format="dateFormat"
					:clearable="false"
					:minute-step="5"
					:show-second="false"
					:type="datePickerType"
					:show-timezone-select="true"
					:timezone-id="startTimezoneId" />
				<DatetimePicker v-model="endDate"
					:format="dateFormat"
					:clearable="false"
					:minute-step="5"
					:show-second="false"
					:type="datePickerType"
					:show-timezone-select="true"
					:timezone-id="endTimezoneId" />
			</div>
			<div class="all-day">
				<input
					id="allDay"
					v-model="isAllDay"
					type="checkbox"
					class="checkbox">
				<label
					for="allDay">
					{{ t('mail', 'All day') }}
				</label>
			</div>
			<Multiselect
				v-model="selectedCalendar"
				label="displayname"
				track-by="url"
				:allow-empty="false"
				:options="calendars">
				<template #option="{option}">
					<CalendarPickerOption
						v-bind="option" />
				</template>
				<template #singleLabel="{option}">
					<CalendarPickerOption
						:display-icon="true"
						v-bind="option" />
				</template>
			</Multiselect>
			<br>
			<button class="primary" @click="onSave">
				{{ t('mail', 'Create') }}
			</button>
		</div>
	</Modal>
</template>

<script>
import { createEvent, getTimezoneManager, DateTimeValue, TextProperty } from '@nextcloud/calendar-js'
import { NcDatetimePicker as DatetimePicker, NcModal as Modal, NcMultiselect as Multiselect } from '@nextcloud/vue'
import jstz from 'jstz'

import { getUserCalendars, importCalendarEvent } from '../service/DAVService'
import logger from '../logger'
import CalendarPickerOption from './CalendarPickerOption'
import { showError, showSuccess } from '@nextcloud/dialogs'

export default {
	name: 'EventModal',
	components: {
		CalendarPickerOption,
		DatetimePicker,
		Modal,
		Multiselect,
	},
	props: {
		envelope: {
			type: Object,
			required: true,
		},
	},
	data() {
		// Try to determine the current timezone, and fall back to UTC otherwise
		const defaultTimezone = jstz.determine()
		const defaultTimezoneId = defaultTimezone ? defaultTimezone.name() : 'UTC'

		return {
			calendars: [],
			eventTitle: this.envelope.subject,
			startDate: new Date(),
			endDate: new Date(),
			isAllDay: false,
			startTimezoneId: defaultTimezoneId,
			endTimezoneId: defaultTimezoneId,
			saving: false,
			selectedCalendar: undefined,
		}
	},
	computed: {
		dateFormat() {
			return this.isAllDay ? 'YYYY-MM-DD' : 'YYYY-MM-DD HH:mm'
		},
		datePickerType() {
			return this.isAllDay ? 'date' : 'datetime'
		},
	},
	created() {
		logger.debug('creating event from envelope', {
			envelope: this.envelope,
		})
	},
	async mounted() {
		this.calendars = (await getUserCalendars()).filter(c => c.writable)

		if (this.calendars.length) {
			this.selectedCalendar = this.calendars[0]
		}
	},
	methods: {
		onClose() {
			this.$emit('close')
		},
		async onSave() {
			this.saving = true

			try {
				logger.debug('create event', {
					calendar: this.selectedCalendar,
					eventTitle: this.eventTitle,
					startDate: this.startDate,
					startTimezone: this.startTimezoneId,
					endTimezone: this.endTimezoneId,
				})

				const timezoneManager = getTimezoneManager()
				// TODO: only do this once
				timezoneManager.registerDefaultTimezones()
				const startTimezone = timezoneManager.getTimezoneForId(this.startTimezoneId)
				const startDateTime = DateTimeValue
					.fromJSDate(this.startDate, true)
					.getInTimezone(startTimezone)
				const endTimezone = timezoneManager.getTimezoneForId(this.endTimezoneId)
				const endDateTime = DateTimeValue
					.fromJSDate(this.endDate, true)
					.getInTimezone(endTimezone)
				if (this.isAllDay) {
					startDateTime.isDate = true
					endDateTime.isDate = true
				}

				const calendar = createEvent(startDateTime, endDateTime)
				const event = calendar.getFirstComponent('VEVENT')
				event.addProperty(new TextProperty('SUMMARY', this.eventTitle))
				for (const vObject of calendar.getVObjectIterator()) {
					vObject.undirtify()
				}
				logger.debug('calendar object created', { calendar, event })

				await importCalendarEvent(
					this.selectedCalendar.url,
					calendar.toICS()
				)

				showSuccess(t('mail', 'Event created'))

				this.onClose()
			} catch (error) {
				showError(t('mail', 'Could not create event'))

				logger.error('Creating event from message failed', { error })
			} finally {
				this.saving = false
			}
		},
	},
}
</script>

<style lang="scss" scoped>
:deep(.modal-wrapper .modal-container) {
	width: calc(100vw - 120px) !important;
	height: calc(100vh - 120px) !important;
	max-width: 490px !important;
	max-height: 500px !important;
}
.modal-content {
	padding: 30px 30px 20px !important;
}
input {
	width: 100%;
}
:deep(input[type='text'].multiselect__input) {
	padding: 0 !important;
}
:deep(.multiselect__single) {
	margin-left: -18px;
	width: 100px;
}
:deep(.multiselect__tags) {
	border: none !important;
}
.all-day {
	margin-left: -1px;
	margin-top: 5px;
	margin-bottom: 5px;
}
.eventTitle {
	margin-bottom: 5px;
}
.primary {
	height: 44px !important;
	float: right;
}
:deep(.mx-datepicker) {
	width: 213px;
}
</style>
