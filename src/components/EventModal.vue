<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<Modal size="large"
		:name="t('mail', 'Create event')"
		@close="onClose">
		<div class="modal-content">
			<h2>{{ t('mail', 'Create event') }}</h2>
			<div class="eventTitle">
				<input v-model="eventTitle" :disabled="generatingData" type="text">
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
				<input id="allDay"
					v-model="isAllDay"
					type="checkbox"
					class="checkbox">
				<label for="allDay">
					{{ t('mail', 'All day') }}
				</label>
			</div>
			<div class="attendees-field">
				<label for="attendees">{{ t('mail', 'Attendees') }}</label>
				<span v-if="!organizerEmail" class="attendees-disabled-msg">
					{{ t('mail', 'You can only invite attendees if your account has an email address set') }}
				</span>
				<NcSelect v-else
					id="attendee"
					:value="attendeesList"
					class="select-users"
					:multiple="true"
					label="displayName"
					track-by="email"
					:clearable="true"
					:searchable="true"
					:label-outside="true"
					input-id="uid"
					:disabled="!organizerEmail"
					:options="attendeesOptions"
					:taggable="true"
					:create-option="createRecipientOption"
					@option:selecting="addAttendee">
					<template #search="{ events, attributes }">
						<input :placeholder="t('mail', 'Contact or email address …')"
							type="search"
							class="vs__search"
							v-bind="attributes"
							v-on="events">
					</template>
					<template #selected-option-container="{option}">
						<RecipientListItem :option="option"
							class="vs__selected selected"
							@remove-recipient="removeAttendee(option)" />
					</template>
				</NcSelect>
			</div>
			<NcSelect v-model="selectedCalendar"
				class="modal-content__calendar-picker"
				label="displayname"
				:aria-label-combobox="t('mail', 'Select calendar')"
				:options="calendars">
				<template #option="option">
					<CalendarPickerOption v-bind="option" />
				</template>
				<template #singleLabel="option">
					<CalendarPickerOption :display-icon="true"
						v-bind="option" />
				</template>
			</NcSelect>
			<label for="description">{{ t('mail', 'Description') }}</label>
			<textarea id="description"
				v-model="description"
				:disabled="generatingData"
				class="modal-content__description-input"
				rows="7" />
			<br>
			<button class="primary" @click="onSave">
				{{ t('mail', 'Create') }}
			</button>
		</div>
	</Modal>
</template>

<script>
import { createEvent, DateTimeValue, TextProperty, AttendeeProperty } from '@nextcloud/calendar-js'
import { getTimezoneManager } from '@nextcloud/timezones'
import { NcDateTimePicker as DatetimePicker, NcModal as Modal, NcSelect } from '@nextcloud/vue'
import RecipientListItem from './RecipientListItem.vue'
import jstz from 'jstz'

import { getUserCalendars, importCalendarEvent } from '../service/DAVService.js'
import logger from '../logger.js'
import CalendarPickerOption from './CalendarPickerOption.vue'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { mapState, mapStores } from 'pinia'
import useMainStore from '../store/mainStore.js'
import { generateEventData } from '../service/AiIntergrationsService.js'

export default {
	name: 'EventModal',
	components: {
		RecipientListItem,
		CalendarPickerOption,
		DatetimePicker,
		Modal,
		NcSelect,
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
			description: this.envelope.previewText,
			generatingData: false,
			llmProcessingEnabled: loadState('mail', 'llm_summaries_available', false),
			attendeesList: [],
			attendeesOptions: [],
		}
	},
	computed: {
		...mapStores(useMainStore),
		...mapState(useMainStore, {
			organizerEmail: 'getCurrentUserPrincipalEmail',
		}),
		dateFormat() {
			return this.isAllDay ? 'YYYY-MM-DD' : 'YYYY-MM-DD HH:mm'
		},
		datePickerType() {
			return this.isAllDay ? 'date' : 'datetime'
		},
		prefilledAttendees() {
			const attendees = []
			if (this.envelope.from) {
				attendees.push({
					displayName: this.envelope.from[0].name || this.envelope.from[0].email,
					email: this.envelope.from[0].email || this.envelope.from[0].name,
				})
			}
			if (Array.isArray(this.envelope.to)) {
				this.envelope.to.forEach(person => {
					if (person.email) {
						attendees.push({
							displayName: person.name || person.email,
							email: person.email,
						})
					}
				})
			}
			return attendees
		},
	},
	async created() {
		logger.debug('creating event from envelope', {
			envelope: this.envelope,
		})
		this.attendeesOptions = this.prefilledAttendees
		this.attendeesList = [...this.attendeesOptions]

		await this.generateEventData()
	},
	async mounted() {
		this.calendars = (await getUserCalendars()).filter(c => c.writable)

		if (this.calendars.length) {
			this.selectedCalendar = this.calendars[0]
		}
	},
	methods: {
		addAttendee(option) {
			if (option === undefined || option === null || option === '') {
				return
			}
			const attendee = { ...option }
			this.attendeesOptions.push(attendee)
			this.attendeesList.push(attendee)
		},
		removeAttendee(option) {
			this.attendeesList = this.attendeesList.filter(
				attendee => attendee.email !== option.email,
			)
		},
		async generateEventData() {
			if (!this.llmProcessingEnabled) {
				return
			}
			try {
				this.generatingData = true

				const { summary, description } = await generateEventData(this.envelope.databaseId)
				this.eventTitle = summary
				this.description = description
			} finally {
				this.generatingData = false
			}
		},
		createRecipientOption(value) {
			return { email: value, displayName: value }
		},
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
					description: this.description,
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
				if (this.description) {
					event.addProperty(new TextProperty('DESCRIPTION', this.description))
				}

				const organizerEmail = this.organizerEmail?.toLowerCase() || ''

				if (organizerEmail && this.attendeesList.length > 0) {
					const filteredAttendees = this.attendeesList
						.map(a => a.email || a.displayName)
						.filter(email => email && email.toLowerCase() !== organizerEmail)

					filteredAttendees.forEach(att => {
						event.addProperty(new AttendeeProperty('ATTENDEE', `mailto:${att}`))
					})

					event.addProperty(new AttendeeProperty('ORGANIZER', organizerEmail))
				}

				for (const vObject of calendar.getVObjectIterator()) {
					vObject.undirtify()
				}
				logger.debug('calendar object created', { calendar, event })

				await importCalendarEvent(
					this.selectedCalendar.url,
					calendar.toICS(),
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
	max-width: 590px !important;
	max-height: 600px !important;
}

.modal-content {
	padding: 10px 30px 20px !important;

	&__calendar-picker {
		display: block;
	}
	&__description-input {
		width: 100%;
		resize: vertical;
	}
}

input {
	width: 100%;
}

:deep(input[type='text']) {
	padding: 0 !important;
}

.all-day {
	margin-inline-start: -1px;
	margin-top: 5px;
	margin-bottom: 5px;
}

.eventTitle {
	margin-bottom: 5px;
}

.primary {
	float: inline-end;
}

:deep(.mx-datepicker) {
	width: 213px;
}

.vs__search {
	width: 100%;
}

.attendees-disabled-msg {
	display: inline-block;
}

.attendees-field {
	padding-bottom: 10px;
}
</style>
