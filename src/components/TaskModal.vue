<template>
	<Modal @close="onClose">
		<div class="modal-content">
			<h2>{{ t('mail', 'Create event') }}</h2>
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
			<label for="note">Tell us your story:</label>
			<textarea id="note" v-model="note" />
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
						:display-icon="option.displayIcon"
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
import { NcDatetimePicker as DatetimePicker, NcModal as Modal, NcMultiselect as Multiselect } from '@nextcloud/vue'
import jstz from 'jstz'

import logger from '../logger'
import CalendarPickerOption from './CalendarPickerOption'
import { showError, showSuccess } from '@nextcloud/dialogs'
import moment from 'moment'

export default {
	name: 'TaskModal',
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

			taskTitle: this.envelope.subject,
			startDate: new Date(),
			endDate: new Date(Date.now() + 60 * 60 * 1000),
			isAllDay: true,
			startTimezoneId: defaultTimezoneId,
			endTimezoneId: defaultTimezoneId,
			saving: false,
			selectedCalendar: undefined,
			note: this.envelope.previewText,
		}
	},
	computed: {
		dateFormat() {
			return this.isAllDay ? moment.localeData().longDateFormat('L') : moment.localeData().longDateFormat('LT')
		},
		datePickerType() {
			return this.isAllDay ? 'date' : 'datetime'
		},
		tags() {
			return this.$store.getters.getAllTags
		},
		calendars() {
			return this.$store.getters.getTaskCalendarsForCurrentUser
		},
	},
	created() {
		logger.debug('creating task from envelope', {
			envelope: this.envelope,
		})
	},
	async mounted() {

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

			const taskData = {
				summary: this.taskTitle,
				calendar: this.selectedCalendar,
				start: moment(this.startDate).format().toString(),
				due: moment(this.endDate).set().format().toString(),
				allDay: this.isAllDay,
				note: this.note,
			}
			try {
				logger.debug('create task', taskData)

				this.$store.commit('createTask', taskData)

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
    margin-left: 10px !important;
}
.modal-content {
	padding: 30px 30px 20px !important;
}
input , textarea {
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
.taskTitle {
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
