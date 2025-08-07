<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<form class="form" @submit.prevent="submit">
		<div class="form__multi-row">
			<fieldset class="form__fieldset">
				<input id="ooo-disabled"
					class="radio"
					type="radio"
					name="enabled"
					:checked="enabled === OOO_DISABLED"
					@change="enabled = OOO_DISABLED">
				<label for="ooo-disabled">{{ t('mail', 'Autoresponder off') }}</label>
			</fieldset>

			<fieldset class="form__fieldset">
				<input id="ooo-enabled"
					class="radio"
					type="radio"
					name="enabled"
					:checked="enabled === OOO_ENABLED"
					@change="enabled = OOO_ENABLED">
				<label for="ooo-enabled">{{ t('mail', 'Autoresponder on') }}</label>
			</fieldset>

			<fieldset v-if="hasPersonalAbsenceSettings" class="form__fieldset">
				<input id="ooo-follow-system"
					class="radio"
					type="radio"
					name="enabled"
					:checked="enabled === OOO_FOLLOW_SYSTEM"
					@change="enabled = OOO_FOLLOW_SYSTEM">
				<label for="ooo-follow-system">{{ t('mail', 'Autoresponder follows system settings') }}</label>
			</fieldset>
		</div>

		<template v-if="followingSystem">
			<p>{{ t('mail', 'The autoresponder follows your personal absence period settings.') }}</p>
			<ButtonVue :href="personalAbsenceSettingsUrl" target="_blank" rel="noopener noreferrer">
				<template #icon>
					<OpenInNewIcon :size="20" />
				</template>
				{{ t('mail', 'Edit absence settings') }}
			</ButtonVue>
		</template>
		<template v-else>
			<div class="form__multi-row">
				<fieldset class="form__fieldset">
					<label for="ooo-first-day">{{ t('mail', 'First day') }}</label>
					<DatetimePicker id="ooo-first-day"
						v-model="firstDay"
						:disabled="!enabled" />
				</fieldset>

				<fieldset class="form__fieldset">
					<div class="form__fieldset__label">
						<input id="ooo-enable-last-day"
							v-model="enableLastDay"
							type="checkbox"
							:disabled="!enabled">
						<label for="ooo-enable-last-day">
							{{ t('mail', 'Last day (optional)') }}
						</label>
					</div>
					<DatetimePicker id="ooo-last-day"
						v-model="lastDay"
						:disabled="!enabled || !enableLastDay" />
				</fieldset>
			</div>

			<fieldset class="form__fieldset">
				<label for="ooo-subject">{{ t('mail', 'Subject') }}</label>
				<input id="ooo-subject"
					v-model="subject"
					type="text"
					:disabled="followingSystem">
				<p class="form__fieldset__description">
					{{ t('mail', '${subject} will be replaced with the subject of the message you are responding to') }}
				</p>
			</fieldset>

			<fieldset class="form__fieldset">
				<label for="ooo-message">{{ t('mail', 'Message') }}</label>
				<TextEditor id="ooo-message"
					v-model="message"
					:html="false"
					:disabled="followingSystem"
					:bus="textEditorDummyBus" />
			</fieldset>
		</template>

		<p v-if="errorMessage">
			{{ t('mail', 'Oh Snap!') }}
			{{ errorMessage }}
		</p>

		<ButtonVue type="primary"
			native-type="submit"
			:aria-label="t('mail', 'Save autoresponder')"
			:disabled="loading || !valid">
			<template #icon>
				<CheckIcon :size="20" />
			</template>
			{{ t('mail', 'Save autoresponder') }}
		</ButtonVue>
	</form>
</template>

<script>
import { NcDateTimePicker as DatetimePicker, NcButton as ButtonVue } from '@nextcloud/vue'
import TextEditor from './TextEditor.vue'
import CheckIcon from 'vue-material-design-icons/Check.vue'
import { html, plain, toHtml, toPlain } from '../util/text.js'
import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import OpenInNewIcon from 'vue-material-design-icons/OpenInNew.vue'
import * as OutOfOfficeService from '../service/OutOfOfficeService.js'
import mitt from 'mitt'
import { mapStores } from 'pinia'
import useMainStore from '../store/mainStore.js'

const OOO_DISABLED = 'disabled'
const OOO_ENABLED = 'enabled'
const OOO_FOLLOW_SYSTEM = 'system'

export default {
	name: 'OutOfOfficeForm',
	components: {
		DatetimePicker,
		TextEditor,
		ButtonVue,
		CheckIcon,
		OpenInNewIcon,
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
			errorMessage: '',
			hasPersonalAbsenceSettings: nextcloudVersion >= 28 && enableSystemOutOfOffice,
			personalAbsenceSettingsUrl: generateUrl('/settings/user/availability'),
			textEditorDummyBus: mitt(),
		}
	},
	computed: {
		...mapStores(useMainStore),
		/**
		 * @return {boolean}
		 */
		valid() {
			if (this.followingSystem) {
				return true
			}

			if (this.enabled === OOO_DISABLED) {
				return true
			}

			return !!(this.firstDay
				&& (!this.enableLastDay || (this.enableLastDay && this.lastDay))
				&& (!this.enableLastDay || (this.lastDay >= this.firstDay))
				&& this.subject
				&& this.message)
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
			this.message = toHtml(plain(state.message)).value
		},
		async submit() {
			this.loading = true
			this.errorMessage = ''

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
						message: toPlain(html(this.message)).value, // CKEditor always returns html data
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
	gap: 15px;

	&__fieldset {
		display: flex;
		flex-direction: column;

		&__label {
			display: flex;
			align-items: center;
			gap: 5px;
		}

		&__input {
			flex: 1 auto;
		}

		&__description {
			color: var(--color-text-maxcontrast);
		}
	}

	&__multi-row {
		display: flex;
		align-items: end;
		gap: 15px;
	}

	#ooo-enable-last-day {
		cursor: pointer;
		min-height: unset;
	}

	#ooo-subject {
		width: 100%;
	}

	#ooo-message {
		width: 100%;
		min-height: 100px;
		border: 1px solid var(--color-border);

		&:active,
		&:focus,
		&:hover {
			border-color: var(--color-primary-element) !important;
		}
	}
}
</style>
