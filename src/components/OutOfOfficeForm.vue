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
	<!-- Wait until sieve script has been fetched before showing form. -->
	<form v-if="sieveScriptData" class="form" @submit.prevent="submit">
		<div class="form__multi-row">
			<fieldset class="form__fieldset">
				<input
					id="ooo-disabled"
					class="radio"
					type="radio"
					name="enabled"
					:checked="!enabled"
					@change="enabled = false">
				<label for="ooo-disabled">{{ t('mail', 'Autoresponder off') }}</label>
			</fieldset>

			<fieldset class="form__fieldset">
				<input
					id="ooo-enabled"
					class="radio"
					type="radio"
					name="enabled"
					:checked="enabled"
					@change="enabled = true">
				<label for="ooo-enabled">{{ t('mail', 'Autoresponder on') }}</label>
			</fieldset>
		</div>

		<div class="form__multi-row">
			<fieldset class="form__fieldset">
				<label for="ooo-first-day">{{ t('mail', 'First day') }}</label>
				<DatetimePicker
					id="ooo-first-day"
					v-model="firstDay"
					:disabled="!enabled" />
			</fieldset>

			<fieldset class="form__fieldset">
				<div class="form__fieldset__label">
					<input
						id="ooo-enable-last-day"
						v-model="enableLastDay"
						type="checkbox"
						:disabled="!enabled">
					<label for="ooo-enable-last-day">
						{{ t('mail', 'Last day (optional)') }}
					</label>
				</div>
				<DatetimePicker
					id="ooo-last-day"
					v-model="lastDay"
					:disabled="!enabled || !enableLastDay" />
			</fieldset>
		</div>

		<fieldset class="form__fieldset">
			<label for="ooo-subject">{{ t('mail', 'Subject') }}</label>
			<input
				id="ooo-subject"
				v-model="subject"
				type="text"
				:disabled="!enabled">
			<p class="form__fieldset__description">
				{{ t('mail', '${subject} will be replaced with the subject of the message you are responding to') }}
			</p>
		</fieldset>

		<fieldset class="form__fieldset">
			<label for="ooo-message">{{ t('mail', 'Message') }}</label>
			<TextEditor
				id="ooo-message"
				v-model="message"
				:html="false"
				:disabled="!enabled"
				:bus="{ '$on': () => { /* noop */ } }" />
		</fieldset>

		<p v-if="errorMessage">
			{{ t('mail', 'Oh Snap!') }}
			{{ errorMessage }}
		</p>

		<Button type="primary" native-type="submit" :disabled="loading || !valid">
			<template #icon>
				<CheckIcon :size="20" />
			</template>
			{{ t('mail', 'Save autoresponder') }}
		</Button>
	</form>
</template>

<script>
import { NcDatetimePicker as DatetimePicker, NcButton as Button } from '@nextcloud/vue'
import TextEditor from './TextEditor'
import CheckIcon from 'vue-material-design-icons/Check'
import { buildOutOfOfficeSieveScript, parseOutOfOfficeState } from '../util/outOfOffice'
import logger from '../logger'
import { html, plain, toHtml, toPlain } from '../util/text'

export default {
	name: 'OutOfOfficeForm',
	components: {
		DatetimePicker,
		TextEditor,
		Button,
		CheckIcon,
	},
	props: {
		account: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			enabled: false,
			enableLastDay: false,
			firstDay: new Date(),
			lastDay: null,
			subject: '',
			message: '',
			loading: false,
			errorMessage: '',
		}
	},
	computed: {
		/**
		 * @return {boolean}
		 */
		valid() {
			return !!(this.firstDay
				&& (!this.enableLastDay || (this.enableLastDay && this.lastDay))
				&& (!this.enableLastDay || (this.lastDay >= this.firstDay))
				&& this.subject
				&& this.message)
		},

		/**
		 * @return {{script: string, scriptName: string}|undefined}
		 */
		sieveScriptData() {
			return this.$store.getters.getActiveSieveScript(this.account.id)
		},

		/**
		 * @return {object|undefined}
		 */
		parsedState() {
			if (!this.sieveScriptData?.script) {
				return undefined
			}

			try {
				return parseOutOfOfficeState(this.sieveScriptData.script).data
			} catch (error) {
				logger.warn('Failed to parse OOO state', { error })
			}

			return undefined
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
	},
	watch: {
		parsedState: {
			immediate: true,
			handler(state) {
				state ??= {}
				this.enabled = !!state.enabled ?? false
				this.firstDay = state.start ?? new Date()
				this.lastDay = state.end ?? null
				this.enableLastDay = !!this.lastDay
				this.subject = state.subject ?? ''
				this.message = toHtml(plain(state.message ?? '')).value
			},
		},
		enableLastDay(enableLastDay) {
			if (enableLastDay) {
				this.lastDay = new Date(this.firstDay)
				this.lastDay.setDate(this.lastDay.getDate() + 6)
			} else {
				this.lastDay = null
			}
		},
		firstDay(firstDay, previousFirstDay) {
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
	methods: {
		async submit() {
			this.loading = true
			this.errorMessage = ''

			const state = parseOutOfOfficeState(this.sieveScriptData.script)
			const originalScript = state.sieveScript

			const enrichedScript = buildOutOfOfficeSieveScript(originalScript, {
				enabled: this.enabled,
				start: this.firstDay,
				end: this.lastDay,
				subject: this.subject,
				message: toPlain(html(this.message)).value, // CKEditor always returns html data
				allowedRecipients: this.aliases,
			})

			try {
				await this.$store.dispatch('updateActiveSieveScript', {
					accountId: this.account.id,
					scriptData: {
						...this.sieveScriptData,
						script: enrichedScript,
					},
				})
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
