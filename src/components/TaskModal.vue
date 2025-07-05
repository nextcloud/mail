<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<Modal @close="onClose">
		<div class="modal-content">
			<h2>{{ t('mail', 'Create task') }}</h2>
			<div class="taskTitle">
				<input v-model="taskTitle" type="text">
			</div>
			<div class="all-day">
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
			<label for="note">{{ t('mail', 'Description') }}</label>
			<textarea id="note" v-model="note" rows="7" />
			<div class="all-day">
				<input id="allDay"
					v-model="isAllDay"
					type="checkbox"
					class="checkbox">
				<label for="allDay">
					{{ t('mail', 'All day') }}
				</label>
			</div>
			<!-- FIXME: is broken due to upstream select component serializing options to JSON -->
			<NcSelect v-model="selectedCalendarChoice"
				label="displayname"
				input-id="url"
				:placeholder="t('mail', 'Select calendar')"
				:aria-label-combobox="t('mail', 'Select calendar')"
				:allow-empty="false"
				:options="calendarChoices">
				<template #option="{ id }">
					<CalendarPickerOption :color="getCalendarById(id).color"
						:displayname="getCalendarById(id).displayname" />
				</template>
				<template #selected-option="{ id }">
					<CalendarPickerOption :color="getCalendarById(id).color"
						:displayname="getCalendarById(id).displayname"
						:display-icon="getCalendarById(id).displayIcon" />
				</template>
				<template #no-options>
					<span>{{ t('mail', 'No calendars with task list support') }}</span>
				</template>
			</NcSelect>
			<br>
			<button class="primary" :disabled="disabled" @click="onSave">
				{{ t('mail', 'Create') }}
			</button>
		</div>
	</Modal>
</template>

<script>
import { NcDateTimePicker as DatetimePicker, NcModal as Modal, NcSelect } from '@nextcloud/vue'
import jstz from 'jstz'

import logger from '../logger.js'
import ICAL from 'ical.js'
import Task from '../task.js'
import CalendarPickerOption from './CalendarPickerOption.vue'
import { showError, showSuccess } from '@nextcloud/dialogs'
import moment from '@nextcloud/moment'
import { mapStores } from 'pinia'
import useMainStore from '../store/mainStore.js'

export default {
	name: 'TaskModal',
	components: {
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

			taskTitle: this.envelope.subject,
			startDate: null,
			endDate: null,
			isAllDay: true,
			startTimezoneId: defaultTimezoneId,
			endTimezoneId: defaultTimezoneId,
			saving: false,
			selectedCalendarChoice: undefined,
			note: this.envelope.previewText,
		}
	},
	computed: {
		...mapStores(useMainStore),
		disabled() {
			return this.saving || this.calendars.length === 0
		},
		dateFormat() {
			return this.isAllDay ? 'YYYY-MM-DD' : 'YYYY-MM-DD HH:mm'
		},
		datePickerType() {
			return this.isAllDay ? 'date' : 'datetime'
		},
		tags() {
			return this.mainStore.getAllTags
		},
		calendars() {
			return this.mainStore.getTaskCalendarsForCurrentUser
		},
		calendarChoices() {
			return this.calendars.map(calendar => ({
				id: calendar.id,
				color: calendar.color,
				displayname: calendar.displayname,
			}))
		},
		selectedCalendar() {
			if (!this.selectedCalendarChoice) {
				return undefined
			}

			return this.calendars.find((cal) => cal.id === this.selectedCalendarChoice.id)
		},
	},
	created() {
		logger.debug('creating task from envelope', {
			envelope: this.envelope,
		})
	},
	async mounted() {
		if (this.calendars.length) {
			this.selectedCalendarChoice = this.calendarChoices[0]
		}
	},
	methods: {
		/**
		 * @param {string} id The calendar id
		 * @return {object|undefined} The calendar object (if it exists)
		 */
		getCalendarById(id) {
			return this.calendars.find((cal) => cal.id === id)
		},

		onClose() {
			this.$emit('close')
		},
		async createTask(taskData) {
			const task = new Task('BEGIN:VCALENDAR\nVERSION:2.0\nPRODID:-//Nextcloud Mail v' + this.mainStore.getAppVersion + '\nEND:VCALENDAR', taskData.calendar)
			task.created = ICAL.Time.now()
			task.summary = taskData.summary
			task.hidesubtasks = 0
			if (taskData.priority) {
				task.priority = taskData.priority
			}
			if (taskData.complete) {
				task.complete = taskData.complete
			}
			if (taskData.note) {
				task.note = taskData.note
			}
			if (taskData.due) {
				task.due = taskData.due
			}
			if (taskData.start) {
				task.start = taskData.start
			}
			if (taskData.allDay) {
				task.allDay = taskData.allDay
			}
			const vData = ICAL.stringify(task.jCal)

			await task.calendar.dav.createVObject(vData)

			return task

		},
		async onSave() {
			this.saving = true

			const taskData = {
				summary: this.taskTitle,
				calendar: this.selectedCalendar,
				start: this.startDate ? moment(this.startDate).format().toString() : null,
				due: this.endDate ? moment(this.endDate).set().format().toString() : null,
				allDay: this.isAllDay,
				note: this.note,
			}
			try {
				logger.debug('create task', taskData)

				await this.createTask(taskData)

				showSuccess(t('mail', 'Task created'))

				this.onClose()
			} catch (error) {
				showError(t('mail', 'Could not create task'))

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

:deep(.calendar-picker-option__color-indicator){
    margin-inline-start: 10px !important;
}

.modal-content {
	padding: 30px 30px 20px !important;
}

input , textarea {
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

.taskTitle {
	margin-bottom: 5px;
}

.primary {
	height: 44px !important;
	float: inline-end;
}

:deep(.mx-datepicker) {
	width: 213px;
}
</style>
