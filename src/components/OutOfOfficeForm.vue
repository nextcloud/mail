<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<form class="form" @submit.prevent="submit">
		<NcFormGroup hide-label hide-description>
			<NcRadioGroup v-model="enabled" label="" hide-label>
				<NcRadioGroupButton :label="t('mail', 'On')" :value="OOO_ENABLED" />
				<NcRadioGroupButton :label="t('mail', 'Off')" :value="OOO_DISABLED" />
				<NcRadioGroupButton v-if="hasPersonalAbsenceSettings" :label="t('mail', 'Follows system settings')" :value="OOO_FOLLOW_SYSTEM" />
			</NcRadioGroup>

			<template v-if="followingSystem">
				<NcNoteCard
					type="info"
					:text="t('mail', 'The autoresponder follows your personal absence period settings.')" />

				<NcButton :href="personalAbsenceSettingsUrl" target="_blank" rel="noopener noreferrer">
					<template #icon>
						<OpenInNewIcon :size="20" />
					</template>
					{{ t('mail', 'Edit absence settings') }}
				</NcButton>
			</template>
			<template v-else-if="enabled === OOO_ENABLED">
				<div class="split-row">
					<NcDateTimePickerNative
						v-model="firstDay"
						:label="t('mail', 'First day')" />

					<div>
						<NcCheckboxRadioSwitch v-model="enableLastDay">
							{{ t('mail', 'Last day (optional)') }}
						</NcCheckboxRadioSwitch>

						<NcDateTimePickerNative
							v-model="lastDay"
							:disabled="!enableLastDay"
							:label="t('mail', 'Last day')"
							hide-label />
					</div>
				</div>

				<NcTextField
					v-model="subject"
					type="text"
					:label="t('mail', 'Subject')"
					:helper-text="t('mail', '${subject} will be replaced with the subject of the message you are responding to')" />

				<NcTextArea
					v-model="message"
					:label="t('mail', 'Message')" />
			</template>

			<NcNoteCard
				v-if="errorMessage"
				type="error"
				:text="errorMessage" />

			<NcButton
				variant="primary"
				type="submit"
				:aria-label="t('mail', 'Save autoresponder')"
				:disabled="loading || !valid">
				<template #icon>
					<CheckIcon :size="20" />
				</template>
				{{ t('mail', 'Save autoresponder') }}
			</NcButton>
		</NcFormGroup>
	</form>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import {
	NcButton,
	NcCheckboxRadioSwitch,
	NcDateTimePickerNative,
	NcFormGroup,
	NcNoteCard,
	NcRadioGroup,
	NcRadioGroupButton,
	NcTextArea,
	NcTextField,
} from '@nextcloud/vue'
import { mapStores } from 'pinia'
import CheckIcon from 'vue-material-design-icons/Check.vue'
import OpenInNewIcon from 'vue-material-design-icons/OpenInNew.vue'
import * as OutOfOfficeService from '../service/OutOfOfficeService.js'
import useMainStore from '../store/mainStore.js'

const OOO_DISABLED = 'disabled'
const OOO_ENABLED = 'enabled'
const OOO_FOLLOW_SYSTEM = 'system'

export default {
	name: 'OutOfOfficeForm',
	components: {
		NcDateTimePickerNative,
		NcCheckboxRadioSwitch,
		NcButton,
		CheckIcon,
		OpenInNewIcon,
		NcFormGroup,
		NcNoteCard,
		NcRadioGroup,
		NcRadioGroupButton,
		NcTextField,
		NcTextArea,
	},

	props: {
		account: {
			type: Object,
			required: true,
		},
	},

	data() {
		const nextcloudVersion = parseInt(OC.config.version.split('.')[0])
		const enableSystemOutOfOffice = loadState('mail', 'enable-system-out-of-office', false)

		return {
			OOO_DISABLED,
			OOO_ENABLED,
			OOO_FOLLOW_SYSTEM,
			initialized: false,
			enabled: this.account.outOfOfficeFollowsSystem ? OOO_FOLLOW_SYSTEM : OOO_DISABLED,
			enableLastDay: false,
			firstDay: new Date(),
			lastDay: null,
			subject: '',
			message: '',
			loading: false,
			errorMessage: null,
			hasPersonalAbsenceSettings: nextcloudVersion >= 28 && enableSystemOutOfOffice,
			personalAbsenceSettingsUrl: generateUrl('/settings/user/availability'),
		}
	},

	computed: {
		...mapStores(useMainStore),
		/**
		 * @return {boolean}
		 */
		valid() {
			if (this.enabled === OOO_ENABLED) {
				return !!(this.firstDay
					&& (!this.enableLastDay || (this.enableLastDay && this.lastDay))
					&& (!this.enableLastDay || (this.lastDay >= this.firstDay))
					&& this.subject
					&& this.message)
			} else {
				return true
			}
		},

		/**
		 * Main address and all aliases formatted for use with sieve.
		 *
		 * @return {string[]}
		 */
		aliases() {
			return [
				{
					name: this.account.name,
					alias: this.account.emailAddress,
				},
				...this.account.aliases,
			].map(({ name, alias }) => `${name} <${alias}>`)
		},

		/**
		 * @return {boolean}
		 */
		followingSystem() {
			return this.hasPersonalAbsenceSettings && this.enabled === OOO_FOLLOW_SYSTEM
		},
	},

	watch: {
		enableLastDay(enableLastDay) {
			if (!this.initialized) {
				return
			}

			if (enableLastDay) {
				this.lastDay = new Date(this.firstDay)
				this.lastDay.setDate(this.lastDay.getDate() + 6)
			} else {
				this.lastDay = null
			}
		},

		firstDay(firstDay, previousFirstDay) {
			if (!this.initialized) {
				return
			}

			if (!this.enableLastDay) {
				return
			}

			const dayInMillis = 24 * 60 * 60 * 1000
			const diffDays = Math.floor((this.lastDay - previousFirstDay) / dayInMillis)
			if (diffDays < 0) {
				return
			}

			this.lastDay = new Date(firstDay)
			this.lastDay.setDate(firstDay.getDate() + diffDays)
		},
	},

	async mounted() {
		await this.fetchState()
		this.initialized = true
	},

	methods: {
		async fetchState() {
			const { state } = await OutOfOfficeService.fetch(this.account.id)

			if (this.account.outOfOfficeFollowsSystem) {
				this.enabled = OOO_FOLLOW_SYSTEM
			} else {
				this.enabled = state.enabled ? OOO_ENABLED : OOO_DISABLED
			}

			if (state.enabled && state.start) {
				this.firstDay = new Date(state.start)
			}
			if (state.enabled && state.end) {
				this.lastDay = new Date(state.end)
				// FIXME: The dav automation adds 23:59 and mail adds 24:00 hours to the last day.
				//        Subtract 23 hours to get the actual date.
				this.lastDay.setHours(this.lastDay.getHours() - 23, 0, 0, 0)
				this.enableLastDay = true
			}

			this.subject = state.subject
			this.message = state.message
		},

		async submit() {
			this.loading = true
			this.errorMessage = null

			try {
				if (this.followingSystem) {
					await OutOfOfficeService.followSystem(this.account.id)
					this.mainStore.patchAccountMutation({
						account: this.account,
						data: {
							outOfOfficeFollowsSystem: true,
						},
					})
				} else {
					const firstDay = new Date(this.firstDay)
					firstDay.setHours(0, 0, 0, 0)

					let lastDay = null
					if (this.lastDay) {
						// Add 24 hours to the last day to include the whole day
						lastDay = new Date(this.lastDay)
						lastDay.setHours(24, 0, 0, 0)
					}

					// Date.toISOString() always returns the date in UTC
					await OutOfOfficeService.update(this.account.id, {
						enabled: this.enabled === OOO_ENABLED,
						start: firstDay.toISOString(),
						end: lastDay?.toISOString() ?? null,
						subject: this.subject,
						message: this.message,
						allowedRecipients: this.aliases,
					})

					this.mainStore.patchAccountMutation({
						account: this.account,
						data: {
							outOfOfficeFollowsSystem: false,
						},
					})
				}
				await this.mainStore.fetchActiveSieveScript({ accountId: this.account.id })
			} catch (error) {
				this.errorMessage = error.message
			} finally {
				this.loading = false
			}
		},
	},
}
</script>

<style lang="scss" scoped>

.form {
	display: flex;
	flex-direction: column;

	.split-row {
		display: flex;
		align-items: end;
		gap: var(--form-group-content-gap);

		> * {
			flex-grow: 1;
		}
	}
}

</style>
