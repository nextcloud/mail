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
			<span v-if="wasUpdated">{{ t('mail', 'An event you have been invited to was updated') }}</span>
			<span v-else>{{ t('mail', 'You have been invited to an event') }}</span>
		</div>
		<div
			v-else-if="isReply"
			class="imip__type">
			<CalendarIcon :size="20" />
			<span>{{ t('mail', 'This event was updated') }}</span>
		</div>
		<div
			v-else-if="isCancel"
			class="imip__type">
			<CloseIcon :size="20" fill-color="red" />
			<span>{{ t('mail', 'This event was cancelled') }}</span>
		</div>

		<EventData :event="parsedEvent" />

		<!-- TODO: actually implement buttons (https://github.com/nextcloud/mail/issues/6803) -->
		<!-- TODO: "More options" needs more specification -->
		<!--
		<div class="imip__actions">
			<template v-if="isRequest && eventIsInFuture">
				<Button
					type="tertiary">
					{{ t('mail', 'Accept') }}
				</Button>
				<Button type="tertiary">
					{{ t('mail', 'Decline') }}
				</Button>
			</template>
			<Actions :menu-title="t('mail', 'More options')" />
		</div>
		-->
	</div>
</template>

<script>
import EventData from './imip/EventData'
// import Button from '@nextcloud/vue/dist/Components/Button'
// import Actions from '@nextcloud/vue/dist/Components/Actions'
import CloseIcon from 'vue-material-design-icons/Close'
import CalendarIcon from 'vue-material-design-icons/Calendar'
import { getParserManager } from '@nextcloud/calendar-js'

const REQUEST = 'REQUEST'
const REPLY = 'REPLY'
const CANCEL = 'CANCEL'

export default {
	name: 'Imip',
	components: {
		EventData,
		// Button,
		// Actions,
		CloseIcon,
		CalendarIcon,
	},
	props: {
		scheduling: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			REQUEST,
			CANCEL,
			REPLY,
		}
	},
	computed: {
		/**
		 * @returns {string}
		 */
		method() {
			return this.scheduling.method
		},

		/**
		 * @returns {boolean}
		 */
		isRequest() {
			return this.method === REQUEST
		},

		/**
		 * @returns {boolean}
		 */
		isReply() {
			return this.method === REPLY
		},

		/**
		 * @returns {boolean}
		 */
		isCancel() {
			return this.method === CANCEL
		},

		/**
		 * @returns {boolean}
		 */
		wasUpdated() {
			// TODO: ask backend whether invitation is new or was updated
			return false
		},

		/**
		 * @returns {EventComponent|undefined}
		 */
		parsedEvent() {
			const parserManager = getParserManager()
			const parser = parserManager.getParserForFileType('text/calendar')
			parser.parse(this.scheduling.contents)

			const vCalendar = parser.getItemIterator().next().value
			if (!vCalendar) {
				return undefined
			}

			const vEvent = vCalendar.getEventIterator().next().value
			return vEvent ?? undefined
		},

		/**
		 * @returns {boolean}
		 */
		eventIsInFuture() {
			return this.parsedEvent.startDate.jsDate.getTime() > new Date().getTime()
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
	}

	&__actions {
		display: flex;
		margin-top: 15px;
	}
}
</style>
