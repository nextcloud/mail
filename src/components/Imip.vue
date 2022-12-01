<!--
  - @copyright Copyright (c) 2022 Richard Steinmetz <richard@steinmetz.cloud>
  -
  - @author Richard Steinmetz <richard@steinmetz.cloud>
  -
  - @license AGPL-3.0-or-later
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<div class="imip">
		<div
			v-if="isRequest"
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
		<div
			v-else-if="isReply"
			class="imip__type">
			<CalendarIcon :size="20" />
			<span>{{ replyStatusMessage }}</span>
		</div>
		<div
			v-else-if="isCancel"
			class="imip__type">
			<CloseIcon :size="20" fill-color="red" />
			<span>{{ t('mail', 'This event was cancelled') }}</span>
		</div>

		<EventData :event="attachedVEvent" />

		<div v-if="showMoreOptions" class="imip__more-options">
			<!-- Hide calendar picker if editing an existing event (e.g. an internal event is
			 shared by default and thus existing even if the attendee didn't react yet). -->
			<div
				v-if="!isExistingEvent"
				class="imip__more-options__row imip__more-options__row--calendar">
				<label for="targetCalendarPickerId">{{ t('mail', 'Save to') }}</label>
				<div class="imip__more-options__row--calendar__multiselect">
					<Multiselect
						v-if="calendarsForPicker.length > 1"
						:id="targetCalendarPickerId"
						v-model="targetCalendar"
						label="displayname"
						track-by="url"
						:allow-empty="false"
						:options="calendarsForPicker">
						<template #option="{option}">
							<CalendarPickerOption v-bind="option" />
						</template>
						<template #singleLabel="{option}">
							<CalendarPickerOption :display-icon="true" v-bind="option" />
						</template>
					</Multiselect>
				</div>
			</div>
			<div class="imip__more-options__row imip__more-options__row--comment">
				<label for="commentFieldId">{{ t('mail', 'Comment') }}</label>
				<textarea :id="commentFieldId" v-model="comment" rows="3" />
			</div>
		</div>

		<template v-if="isRequest && userIsAttendee">
			<div
				v-if="!wasProcessed && eventIsInFuture && existingEventFetched"
				class="imip__actions imip__actions--buttons">
				<Button
					type="secondary"
					:loading="loading"
					@click="accept">
					{{ t('mail', 'Accept') }}
				</Button>
				<Button
					type="tertiary"
					:loading="loading"
					@click="decline">
					{{ t('mail', 'Decline') }}
				</Button>
				<Button
					type="tertiary"
					:loading="loading"
					@click="acceptTentatively">
					{{ t('mail', 'Tentatively accept') }}
				</Button>
				<Button
					v-if="!showMoreOptions"
					type="tertiary"
					@click="showMoreOptions = true">
					{{ t('mail', 'More options') }}
				</Button>
			</div>
			<p v-else-if="!eventIsInFuture" class="imip__actions imip__actions--hint">
				{{ t('mail', 'This event is in the past.') }}
			</p>
		</template>
	</div>
</template>

<script>
import EventData from './imip/EventData'
import { NcButton as Button, NcMultiselect as Multiselect } from '@nextcloud/vue'
import CloseIcon from 'vue-material-design-icons/Close'
import CalendarIcon from 'vue-material-design-icons/Calendar'
import { getParserManager, Parameter, Property } from '@nextcloud/calendar-js'
import { mapGetters } from 'vuex'
import { removeMailtoPrefix } from '../util/eventAttendee'
import logger from '../logger'
import { namespaces as NS } from '@nextcloud/cdav-library'
import CalendarPickerOption from './CalendarPickerOption'
import { uidToHexColor } from '../util/calendarColor'
import { randomId } from '../util/randomId'
import pLimit from 'p-limit'
import { flatten } from 'ramda'
import { showError } from '@nextcloud/dialogs'

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
	for (const attendee of vEvent.getAttendeeIterator()) {
		if (removeMailtoPrefix(attendee.email) === email) {
			return attendee
		}
	}

	return undefined
}

export default {
	name: 'Imip',
	components: {
		EventData,
		Button,
		CloseIcon,
		CalendarIcon,
		CalendarPickerOption,
		Multiselect,
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
		...mapGetters({
			currentUserPrincipalEmail: 'getCurrentUserPrincipalEmail',
			clonedCalendars: 'getClonedCalendars',
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
			return this.attachedVEvent.startDate.jsDate.getTime() > new Date().getTime()
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
					dav: calendar,
				}
			}

			return this.clonedCalendars
				.map(getCalendarData)
				.filter(props => props.components.vevent && props.writable === true)
		},
	},
	watch: {
		attachedVEvent: {
			immediate: true,
			async handler() {
				await this.fetchExistingEvent(this.attachedVEvent.uid)
			},
		},
		clonedCalendars: {
			immediate: true,
			async handler() {
				await this.fetchExistingEvent(this.attachedVEvent.uid)
			},
		},
		calendarsForPicker: {
			immediate: true,
			handler(calendarsForPicker) {
				if (calendarsForPicker.length > 0 && !this.targetCalendar) {
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

			const calendar = this.targetCalendar?.dav
			if (!calendar) {
				return
			}

			attendee.participationStatus = status
			if (this.comment) {
				attendee.setParameter(new Parameter('X-RESPONSE-COMMENT', this.comment))
				vEvent.addProperty(new Property('COMMENT', this.comment))
			}
			// TODO: implement an input for guests and save it to the attendee via X-NUM-GUESTS

			this.loading = true
			try {
				if (this.isExistingEvent) {
					// TODO: don't show buttons if calendar is not writable
					this.existingEvent.data = vCalendar.toICS()
					await this.existingEvent.update()
				} else {
					await calendar.createVObject(vCalendar.toICS())
				}
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
			} finally {
				this.loading = false
			}

			// Refetch the event to update the shown status message or reset the event in the case
			// of an error.
			this.existingEventFetched = false
			await this.fetchExistingEvent(vEvent.uid)
		},
		async fetchExistingEvent(uid) {
			if (this.existingEventFetched) {
				return
			}

			// TODO: can this query be reduced to a single request?
			const limit = pLimit(5)
			const promises = this.clonedCalendars.map(async (calendar) => {
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
		margin-left: 36px;
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

				&__multiselect {
					width: 100%;

					:deep(.calendar-picker-option__label) {
						max-width: unset !important;
					}
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
		margin-left: 36px;

		&--buttons {
			display: flex;
		}

		&--hint {
			font-style: italic;
		}
	}
}
</style>
