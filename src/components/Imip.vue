<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="imip">
		<div v-if="isRequest"
			class="imip__type">
			<template v-if="existingEventFetched">
				<span v-if="wasProcessed && existingParticipationStatus === ACCEPTED">
					{{ t('mail', 'You accepted this invitation') }}
				</span>
				<span v-else-if="wasProcessed && existingParticipationStatus === TENTATIVE">
					{{ t('mail', 'You tentatively accepted this invitation') }}
				</span>
				<span v-else-if="wasProcessed && existingParticipationStatus === DECLINED">
					{{ t('mail', 'You declined this invitation') }}
				</span>
				<span v-else-if="wasProcessed && existingParticipationStatus !== NEEDS_ACTION">
					{{ t('mail', 'You already reacted to this invitation') }}
				</span>
				<span v-else-if="userIsAttendee">
					{{ t('mail', 'You have been invited to an event') }}
				</span>
			</template>
		</div>
		<div v-else-if="isReply"
			class="imip__type">
			<CalendarIcon :size="20" />
			<span>{{ replyStatusMessage }}</span>
		</div>
		<div v-else-if="isCancel"
			class="imip__type">
			<CloseIcon :size="20" fill-color="red" />
			<span>{{ t('mail', 'This event was cancelled') }}</span>
		</div>

		<EventData :event="attachedVEvent" />

		<div v-if="showMoreOptions" class="imip__more-options">
			<!-- Hide calendar picker if editing an existing event (e.g. an internal event is
			 shared by default and thus existing even if the attendee didn't react yet). -->
			<div v-if="!isExistingEvent"
				class="imip__more-options__row imip__more-options__row--calendar">
				<label for="targetCalendarPickerId">{{ t('mail', 'Save to') }}</label>
				<div class="imip__more-options__row">
					<NcSelect v-if="calendarsForPicker.length > 1"
						:id="targetCalendarPickerId"
						v-model="targetCalendar"
						:aria-label-combobox="t('mail', 'Select')"
						label="displayname"
						:options="calendarsForPicker">
						<template #option="option">
							<CalendarPickerOption v-bind="option" />
						</template>
						<template #selected-option="option">
							<CalendarPickerOption v-bind="option" />
						</template>
					</NcSelect>
				</div>
			</div>
			<div class="imip__more-options__row imip__more-options__row--comment">
				<label for="commentFieldId">{{ t('mail', 'Comment') }}</label>
				<textarea :id="commentFieldId" v-model="comment" rows="3" />
			</div>
		</div>

		<template v-if="isRequest && userIsAttendee">
			<div v-if="!wasProcessed && eventIsInFuture && existingEventFetched"
				class="imip__actions imip__actions--buttons">
				<NcButton type="secondary"
					:disabled="loading"
					:aria-label="t('mail', 'Accept')"
					@click="accept">
					{{ t('mail', 'Accept') }}
				</NcButton>
				<NcButton type="tertiary"
					:disabled="loading"
					:aria-label="t('mail', 'Decline')"
					@click="decline">
					{{ t('mail', 'Decline') }}
				</NcButton>
				<NcButton type="tertiary"
					:disabled="loading"
					:aria-label="t('mail', 'Tentatively accept')"
					@click="acceptTentatively">
					{{ t('mail', 'Tentatively accept') }}
				</NcButton>
				<NcButton v-if="!showMoreOptions"
					type="tertiary"
					:disabled="loading"
					:aria-label="t('mail', 'More options')"
					@click="showMoreOptions = true">
					{{ t('mail', 'More options') }}
				</NcButton>
				<NcLoadingIcon v-if="loading" />
			</div>
			<p v-else-if="!eventIsInFuture" class="imip__actions imip__actions--hint">
				{{ t('mail', 'This message has an attached invitation but the invitation dates are in the past') }}
			</p>
		</template>
		<div v-if="!userIsAttendee" class="imip__actions imip__actions--hint">
			{{ t('mail', 'This message has an attached invitation but the invitation does not contain a participant that matches any configured mail account address') }}
		</div>
	</div>
</template>

<script>
import EventData from './imip/EventData.vue'
import { NcButton, NcSelect, NcLoadingIcon } from '@nextcloud/vue'
import CloseIcon from 'vue-material-design-icons/Close.vue'
import CalendarIcon from 'vue-material-design-icons/CalendarOutline.vue'
import { getParserManager, Parameter, Property, DateTimeValue, EventComponent, AttendeeProperty, CalendarComponent } from '@nextcloud/calendar-js'
import { removeMailtoPrefix } from '../util/eventAttendee.js'
import logger from '../logger.js'
import { namespaces as NS } from '@nextcloud/cdav-library'
import CalendarPickerOption from './CalendarPickerOption.vue'
import { uidToHexColor } from '../util/calendarColor.js'
import { randomId } from '../util/randomId.js'
import pLimit from 'p-limit'
import { flatten } from 'ramda'
import { showError } from '@nextcloud/dialogs'
import useMainStore from '../store/mainStore.js'
import { mapState } from 'pinia'

// iMIP methods
const REQUEST = 'REQUEST'
const REPLY = 'REPLY'
const CANCEL = 'CANCEL'

// Participation status
const NEEDS_ACTION = 'NEEDS-ACTION'
const ACCEPTED = 'ACCEPTED'
const TENTATIVE = 'TENTATIVE'
const DECLINED = 'DECLINED'

/**
 * Search a vEvent for an attendee by mail.
 *
 * @param {EventComponent|undefined|null} vEvent The event providing the attendee haystack.
 * @param {string} email The email address (with or without a mailto prefix) to use as the needle.
 * @return {AttendeeProperty|undefined} The attendee property or undefined if the given email is not matching an attendee.
 */
function findAttendee(vEvent, email) {
	if (!vEvent) {
		return undefined
	}

	email = removeMailtoPrefix(email)
	for (const attendee of [...vEvent.getPropertyIterator('ORGANIZER'), ...vEvent.getAttendeeIterator()]) {
		if (removeMailtoPrefix(attendee.email) === email) {
			return attendee
		}
	}

	return undefined
}

export default {
	name: 'Imip',
	components: {
		CalendarIcon,
		CalendarPickerOption,
		CloseIcon,
		EventData,
		NcButton,
		NcLoadingIcon,
		NcSelect,
	},
	props: {
		scheduling: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			NEEDS_ACTION,
			ACCEPTED,
			TENTATIVE,
			DECLINED,

			commentFieldId: randomId(),
			targetCalendarPickerId: randomId(),

			showMoreOptions: false,
			loading: false,
			existingEvent: undefined,
			existingEventFetched: false,
			targetCalendar: undefined,
			comment: '',
		}
	},
	computed: {
		...mapState(useMainStore, {
			currentUserPrincipalEmail: 'getCurrentUserPrincipalEmail',
			clonedWriteableCalendars: 'getClonedWriteableCalendars',
			currentUserPrincipal: 'getCurrentUserPrincipal',
		}),

		/**
		 * The method of the iMIP message.
		 *
		 * @return {string}
		 */
		method() {
			return this.scheduling.method
		},

		/**
		 * @return {boolean}
		 */
		isRequest() {
			return this.method === REQUEST
		},

		/**
		 * @return {boolean}
		 */
		isReply() {
			return this.method === REPLY
		},

		/**
		 * @return {boolean}
		 */
		isCancel() {
			return this.method === CANCEL
		},

		/**
		 * @return {boolean}
		 */
		isExistingEvent() {
			return !!this.existingEvent
		},

		/**
		 * Did the attendee already react to the invitation?
		 *
		 * @return {boolean}
		 */
		wasProcessed() {
			return !!this.existingParticipationStatus && this.existingParticipationStatus !== NEEDS_ACTION
		},

		/**
		 * @return {CalendarComponent|undefined}
		 */
		attachedVCalendar() {
			const parserManager = getParserManager()
			const parser = parserManager.getParserForFileType('text/calendar')
			parser.parse(this.scheduling.contents)

			const vCalendar = parser.getItemIterator().next().value
			return vCalendar ?? undefined
		},

		/**
		 * @return {EventComponent|undefined}
		 */
		attachedVEvent() {
			return this.attachedVCalendar?.getFirstComponent('VEVENT') ?? undefined
		},

		/**
		 * @return {CalendarComponent|undefined}
		 */
		existingVCalendar() {
			if (!this.existingEvent) {
				return undefined
			}

			const parserManager = getParserManager()
			const parser = parserManager.getParserForFileType('text/calendar')
			parser.parse(this.existingEvent.data)

			const vCalendar = parser.getItemIterator().next().value
			return vCalendar ?? undefined
		},

		/**
		 * @return {EventComponent|undefined}
		 */
		existingVEvent() {
			return this.existingVCalendar?.getFirstComponent('VEVENT') ?? undefined
		},

		/**
		 * @return {boolean}
		 */
		eventIsInFuture() {
			if (this.attachedVEvent.isRecurring()) {
				const recurrence = this.attachedVEvent.recurrenceManager.getClosestOccurrence(DateTimeValue.fromJSDate(new Date()))
				return recurrence !== undefined && recurrence.startDate.jsDate.getTime() > new Date().getTime()
			} else {
				return this.attachedVEvent.startDate.jsDate.getTime() > new Date().getTime()
			}
		},

		/**
		 * Check if the user is an attendee of the attached event.
		 *
		 * @return {boolean}
		 */
		userIsAttendee() {
			return !!findAttendee(this.attachedVEvent, this.currentUserPrincipalEmail)
		},

		/**
		 * The users participation status taken from the existing event.
		 *
		 * @return {string|undefined}
		 */
		existingParticipationStatus() {
			const attendee = findAttendee(this.existingVEvent, this.currentUserPrincipalEmail)
			return attendee?.participationStatus ?? undefined
		},

		/**
		 * The status message to show in case of REPLY messages.
		 *
		 * @return {string}
		 */
		replyStatusMessage() {
			const attendees = this.attachedVEvent?.getAttendeeList()
			if (!attendees || attendees.length !== 1) {
				// As per the RFCs there should only be one attendee, but you never know.
				return this.t('mail', 'This event was updated')
			}

			const attendee = attendees[0]
			const partStat = attendee.participationStatus
			const name = attendee.commonName ?? attendee.email
			if (partStat === ACCEPTED) {
				return this.t('mail', '{attendeeName} accepted your invitation', {
					attendeeName: name,
				})
			} else if (partStat === TENTATIVE) {
				return this.t('mail', '{attendeeName} tentatively accepted your invitation', {
					attendeeName: name,
				})
			} else if (partStat === DECLINED) {
				return this.t('mail', '{attendeeName} declined your invitation', {
					attendeeName: name,
				})
			}

			return this.t('mail', '{attendeeName} reacted to your invitation', {
				attendeeName: name,
			})
		},

		/**
		 * List of calendar options for the target calendar picker.
		 *
		 * @return {object[]}
		 */
		calendarsForPicker() {
			const getCalendarData = (calendar) => {
				return {
					displayname: calendar.displayname,
					color: calendar.color ?? uidToHexColor(calendar.displayname ?? ''),
					order: calendar.order,
					components: {
						vevent: true, // check if VEVENT exists in props['supported-calendar-component-set'].comps
					},
					writable: calendar.currentUserPrivilegeSet.indexOf('{DAV:}write') !== -1,
					url: calendar.url,
				}
			}

			return this.clonedWriteableCalendars
				.map(getCalendarData)
				.filter(props => props.components.vevent && props.writable === true)
		},

		/**
		 * Get the DAV object of the picked target calendar.
		 * It can't be included in the option as it contains cyclic references.
		 *
		 * @return {object | undefined}
		 */
		targetCalendarDavObject() {
			return this.clonedWriteableCalendars.find((cal) => cal.url === this.targetCalendar.url)
		},
	},
	watch: {
		attachedVEvent: {
			immediate: true,
			async handler() {
				await this.fetchExistingEvent(this.attachedVEvent.uid)
			},
		},
		calendarsForPicker: {
			immediate: true,
			handler(calendarsForPicker) {
				if (this.targetCalendar) {
					return
				}

				const defaultCalendar = calendarsForPicker.find((cal) => cal.url === this.currentUserPrincipal.scheduleDefaultCalendarUrl)

				if (defaultCalendar) {
					this.targetCalendar = defaultCalendar
				} else if (calendarsForPicker.length > 0) {
					this.targetCalendar = calendarsForPicker[0]
				}
			},
		},
	},
	methods: {
		async accept() {
			await this.saveEventWithParticipationStatus(ACCEPTED)
		},
		async acceptTentatively() {
			await this.saveEventWithParticipationStatus(TENTATIVE)
		},
		async decline() {
			await this.saveEventWithParticipationStatus(DECLINED)
		},
		async saveEventWithParticipationStatus(status) {
			let vCalendar
			if (this.isExistingEvent) {
				vCalendar = this.existingVCalendar
			} else {
				vCalendar = this.attachedVCalendar
			}
			const vEvent = vCalendar.getFirstComponent('VEVENT')
			const attendee = findAttendee(vEvent, this.currentUserPrincipalEmail)
			if (!attendee) {
				return
			}

			const calendar = this.targetCalendarDavObject
			if (!calendar) {
				return
			}

			this.loading = true

			if (!this.isExistingEvent) {
				try {
					await calendar.createVObject(vCalendar.toICS())
					await this.fetchExistingEvent(vEvent.uid, true)
				} catch (error) {
					showError(this.t('mail', 'Failed to save your participation status'))
					logger.error('Failed to save event to calendar', {
						error,
						attendee,
						calendar,
						vEvent,
						vCalendar,
						existingEvent: this.existingEvent,
					})
				}
			}

			if (this.isExistingEvent) {
				attendee.participationStatus = status
				if (this.comment) {
					attendee.setParameter(new Parameter('X-RESPONSE-COMMENT', this.comment))
					vEvent.addProperty(new Property('COMMENT', this.comment))
				}

				// TODO: implement an input for guests and save it to the attendee via X-NUM-GUESTS

				try {
					// TODO: don't show buttons if calendar is not writable
					this.existingEvent.data = vCalendar.toICS()
					await this.existingEvent.update()
					this.showMoreOptions = false
				} catch (error) {
					showError(this.t('mail', 'Failed to save your participation status'))
					logger.error('Failed to save event to calendar', {
						error,
						attendee,
						calendar,
						vEvent,
						vCalendar,
						existingEvent: this.existingEvent,
					})
				}
			}

			// Refetch the event to update the shown status message or reset the event in the case
			// of an error.
			await this.fetchExistingEvent(vEvent.uid, true)

			this.loading = false
		},
		async fetchExistingEvent(uid, force = false) {
			if (!force && this.existingEventFetched) {
				return
			}

			// TODO: can this query be reduced to a single request?
			const limit = pLimit(5)
			const promises = this.clonedWriteableCalendars.map(async (calendar) => {
				// Query adapted from https://datatracker.ietf.org/doc/html/rfc4791#section-7.8.6
				return limit(() => calendar.calendarQuery([{
					name: [NS.IETF_CALDAV, 'comp-filter'],
					attributes: [['name', 'VCALENDAR']],
					children: [{
						name: [NS.IETF_CALDAV, 'comp-filter'],
						attributes: [['name', 'VEVENT']],
						children: [{
							name: [NS.IETF_CALDAV, 'prop-filter'],
							attributes: [['name', 'UID']],
							children: [{
								name: [NS.IETF_CALDAV, 'text-match'],
								value: uid,
							}],
						}],
					}],
				}]))
			})
			const results = flatten(await Promise.all(promises))

			if (results.length > 1) {
				logger.warn('Fetched more than one event for iMIP invitation', { results })
			}

			this.existingEvent = results[0]
			this.existingEventFetched = true
		},
	},
}
</script>

<style lang="scss" scoped>
.imip {
	display: flex;
	flex-direction: column;
	border: solid 2px var(--color-border);
	border-radius: var(--border-radius-large);
	padding: 10px;

	&__type {
		display: flex;
		gap: 5px;
		margin-inline-start: 36px;
	}

	&__more-options {
		display: flex;
		flex-direction: column;
		margin-top: 15px;
		gap: 10px;

		&__row {
			display: flex;
			flex-direction: column;

			&--calendar {
				display: flex;
				width: 100%;

					:deep(.calendar-picker-option__label) {
						max-width: unset !important;
					}
			}

			&--comment {
				textarea {
					display: block;
					width: 100%;
				}
			}
		}
	}

	&__actions {
		margin-top: 15px;
		margin-inline-start: 36px;

		&--buttons {
			display: flex;
		}

		&--hint {
			font-style: italic;
		}
	}
}
</style>
