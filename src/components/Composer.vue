<template>
	<div v-if="state === STATES.EDITING" class="message-composer">
		<div class="composer-fields mail-account">
			<label class="from-label" for="from">
				{{ t('mail', 'From') }}
			</label>
			<Multiselect
				id="from"
				:value="selectedAlias"
				:options="aliases"
				label="name"
				track-by="selectId"
				:searchable="false"
				:custom-label="formatAliases"
				:placeholder="t('mail', 'Select account')"
				:clear-on-select="false"
				@select="onAliasChange" />
		</div>
		<div class="composer-fields">
			<label class="to-label" for="to">
				{{ t('mail', 'To') }}
			</label>
			<Multiselect
				id="to"
				ref="toLabel"
				v-model="selectTo"
				:options="selectableRecipients"
				:taggable="true"
				label="label"
				track-by="email"
				:limit="4"
				:multiple="true"
				:placeholder="t('mail', 'Contact or email address …')"
				:clear-on-select="true"
				:close-on-select="false"
				:show-no-options="false"
				:preserve-search="true"
				:hide-selected="true"
				:loading="loadingIndicatorTo"
				@keyup="onInputChanged"
				@tag="onNewToAddr"
				@search-change="onAutocomplete($event, 'to')" />
			<a v-if="!showCC"
				class="copy-toggle"
				href="#"
				@click.prevent="showCC = true">
				{{ t('mail', '+ Cc/Bcc') }}
			</a>
		</div>
		<div v-if="showCC" class="composer-fields">
			<label for="cc" class="cc-label">
				{{ t('mail', 'Cc') }}
			</label>
			<Multiselect
				id="cc"
				v-model="selectCc"
				:options="selectableRecipients"
				:taggable="true"
				label="label"
				track-by="email"
				:multiple="true"
				:placeholder="t('mail', '')"
				:clear-on-select="true"
				:show-no-options="false"
				:preserve-search="true"
				:loading="loadingIndicatorCc"
				@keyup="onInputChanged"
				@tag="onNewCcAddr"
				@search-change="onAutocomplete($event, 'cc')">
				<span slot="noOptions">{{ t('mail', 'No contacts found.') }}</span>
			</Multiselect>
		</div>
		<div v-if="showCC" class="composer-fields">
			<label for="bcc" class="bcc-label">
				{{ t('mail', 'Bcc') }}
			</label>
			<Multiselect
				id="bcc"
				v-model="selectBcc"
				:options="selectableRecipients"
				:taggable="true"
				label="label"
				track-by="email"
				:multiple="true"
				:placeholder="t('mail', '')"
				:show-no-options="false"
				:preserve-search="true"
				:loading="loadingIndicatorBcc"
				@keyup="onInputChanged"
				@tag="onNewBccAddr"
				@search-change="onAutocomplete($event, 'bcc')">
				<span slot="noOptions">{{ t('mail', 'No contacts found.') }}</span>
			</Multiselect>
		</div>
		<div class="composer-fields">
			<label for="subject" class="subject-label hidden-visually">
				{{ t('mail', 'Subject') }}
			</label>
			<input
				id="subject"
				v-model="subjectVal"
				type="text"
				name="subject"
				class="subject"
				autocomplete="off"
				:placeholder="t('mail', 'Subject …')"
				@keyup="onInputChanged">
		</div>
		<div v-if="noReply" class="warning noreply-warning">
			{{ t('mail', 'This message came from a noreply address so your reply will probably not be read.') }}
		</div>
		<div v-if="mailvelope.keysMissing.length" class="warning noreply-warning">
			{{
				t('mail', 'The following recipients do not have a PGP key: {recipients}.', {
					recipients: mailvelope.keysMissing.join(', '),
				})
			}}
		</div>
		<div class="composer-fields">
			<!--@keypress="onBodyKeyPress"-->
			<TextEditor
				v-if="!encrypt && editorPlainText"
				key="editor-plain"
				v-model="bodyVal"
				name="body"
				class="message-body"
				:placeholder="t('mail', 'Write message …')"
				:focus="isReply"
				:bus="bus"
				@input="onInputChanged" />
			<TextEditor
				v-else-if="!encrypt && !editorPlainText"
				key="editor-rich"
				v-model="bodyVal"
				:html="true"
				name="body"
				class="message-body"
				:placeholder="t('mail', 'Write message …')"
				:focus="isReply"
				:bus="bus"
				@input="onInputChanged" />
			<MailvelopeEditor
				v-else
				ref="mailvelopeEditor"
				v-model="bodyVal"
				:recipients="allRecipients"
				:quoted-text="body"
				:is-reply-or-forward="isReply || isForward" />
		</div>
		<div class="composer-actions">
			<ComposerAttachments v-model="attachments"
				:bus="bus"
				:upload-size-limit="attachmentSizeLimit"
				@upload="onAttachmentsUploading" />
			<div class="composer-actions-right">
				<button v-if="savingDraft === false"
					class="button"
					:title="t('mail', 'Discard & close draft')"
					@click="discardDraft">
					{{ t('mail', 'Discard draft') }}
				</button>
				<p class="composer-actions-draft">
					<span v-if="!canSaveDraft" id="draft-status">{{ t('mail', 'Cannot save draft because this account does not have a drafts mailbox configured.') }}</span>
					<span v-else-if="savingDraft === true" id="draft-status">{{ t('mail', 'Saving draft …') }}</span>
					<span v-else-if="savingDraft === false" id="draft-status">{{ t('mail', 'Draft saved') }}</span>
				</p>
				<Actions>
					<template v-if="!isMoreActionsOpen">
						<ActionButton icon="icon-upload" @click="onAddLocalAttachment">
							{{
								t('mail', 'Upload attachment')
							}}
						</ActionButton>
						<ActionButton icon="icon-folder" @click="onAddCloudAttachment">
							{{
								t('mail', 'Add attachment from Files')
							}}
						</ActionButton>
						<ActionButton :disabled="encrypt" icon="icon-public" @click="onAddCloudAttachmentLink">
							{{
								addShareLink
							}}
						</ActionButton>
						<ActionButton
							v-if="!isScheduledSendingDisabled"
							:close-after-click="false"
							@click="isMoreActionsOpen=true">
							<template #icon>
								<SendClock :size="20" :title="t('mail', 'Send later')" />
							</template>
							{{
								t('mail', 'Send later')
							}}
						</ActionButton>
						<ActionCheckbox
							:checked="!encrypt && !editorPlainText"
							:disabled="encrypt"
							@check="editorMode = 'html'"
							@uncheck="editorMode = 'plaintext'">
							{{ t('mail', 'Enable formatting') }}
						</ActionCheckbox>
						<ActionCheckbox
							:checked="requestMdn"
							@check="requestMdn = true"
							@uncheck="requestMdn = false">
							{{ t('mail', 'Request a read receipt') }}
						</ActionCheckbox>
						<ActionCheckbox
							v-if="mailvelope.available"
							:checked="encrypt"
							@check="encrypt = true"
							@uncheck="encrypt = false">
							{{ t('mail', 'Encrypt message with Mailvelope') }}
						</ActionCheckbox>
						<ActionLink v-else
							href="https://www.mailvelope.com/"
							target="_blank"
							icon="icon-password">
							{{
								t('mail', 'Looking for a way to encrypt your emails? Install the Mailvelope browser extension!')
							}}
						</ActionLink>
					</template>
					<template v-if="isMoreActionsOpen">
						<ActionButton :close-after-click="false"
							@click="isMoreActionsOpen=false">
							<template #icon>
								<ChevronLeft
									:title="t('mail', 'Send later')"
									:size="20" />
								{{ t('mail', 'Send later') }}
							</template>
						</ActionButton>
						<ActionRadio :value="undefined"
							name="sendLater"
							:checked="!sendAtVal"
							class="send-action-radio"
							@update:checked="sendAtVal = undefined"
							@change="onChangeSendLater(undefined)">
							{{ t('mail', 'Send now') }}
						</ActionRadio>
						<ActionRadio :value="dateTomorrowMorning"
							name="sendLater"
							:checked="isSendAtTomorrowMorning"
							class="send-action-radio send-action-radio--multiline"
							@update:checked="sendAtVal = dateTomorrowMorning"
							@change="onChangeSendLater(dateTomorrowMorning)">
							{{ t('mail', 'Tomorrow morning') }} - {{ convertToLocalDate(dateTomorrowMorning) }}
						</ActionRadio>
						<ActionRadio :value="dateTomorrowAfternoon"
							name="sendLater"
							:checked="isSendAtTomorrowAfternoon"
							class="send-action-radio send-action-radio--multiline"
							@update:checked="sendAtVal = dateTomorrowAfternoon"
							@change="onChangeSendLater(dateTomorrowAfternoon)">
							{{ t('mail', 'Tomorrow afternoon') }} - {{ convertToLocalDate(dateTomorrowAfternoon) }}
						</ActionRadio>
						<ActionRadio :value="dateMondayMorning"
							name="sendLater"
							:checked="isSendAtMondayMorning"
							class="send-action-radio send-action-radio--multiline"
							@update:checked="sendAtVal = dateMondayMorning"
							@change="onChangeSendLater(dateMondayMorning)">
							{{ t('mail', 'Monday morning') }} - {{ convertToLocalDate(dateMondayMorning) }}
						</ActionRadio>
						<ActionRadio name="sendLater"
							class="send-action-radio"
							:checked="isSendAtCustom"
							:value="customSendTime"
							@update:checked="sendAtVal = customSendTime"
							@change="onChangeSendLater(customSendTime)">
							{{ t('mail', 'Custom date and time') }}
						</ActionRadio>
						<ActionInput v-model="selectedDate"
							type="datetime-local"
							:first-day-of-week="firstDayDatetimePicker"
							:use12h="showAmPm"
							:formatter="formatter"
							:format="'YYYY-MM-DD HH:mm'"
							icon=""
							:minute-step="5"
							:show-second="false"
							:disabled-date="disabledDatetimepickerDate"
							:disabled-time="disabledDatetimepickerTime"
							@change="onChangeSendLater(customSendTime)">
							{{ t('mail', 'Enter a date') }}
						</ActionInput>
					</template>
				</Actions>

				<button :disabled="!canSend"
					class="button primary send-button"
					type="submit"
					@click="onSend">
					<Send
						:title="submitButtonTitle"
						:size="20" />
					{{ submitButtonTitle }}
				</button>
			</div>
		</div>
	</div>
	<Loading v-else-if="state === STATES.UPLOADING" :hint="t('mail', 'Uploading attachments …')" role="alert" />
	<Loading v-else-if="state === STATES.SENDING"
		:hint="t('mail', 'Sending …')"
		role="alert"
		class="sending-hint" />
	<Loading v-else-if="state === STATES.DISCARDING" :hint="t('mail', 'Discarding …')" class="emptycontent" />
	<EmptyContent v-else-if="state === STATES.DISCARDED" icon="icon-mail">
		<h2>{{ t('mail', 'Draft was discarded!') }}</h2>
	</EmptyContent>
	<div v-else-if="state === STATES.ERROR" class="emptycontent" role="alert">
		<h2>{{ t('mail', 'Error sending your message') }}</h2>
		<p v-if="errorText">
			{{ errorText }}
		</p>
		<button class="button" @click="state = STATES.EDITING">
			{{ t('mail', 'Go back') }}
		</button>
		<button class="button primary" @click="onSend">
			{{ t('mail', 'Retry') }}
		</button>
	</div>
	<div v-else-if="state === STATES.WARNING" class="emptycontent" role="alert">
		<h2>{{ t('mail', 'Warning sending your message') }}</h2>
		<p v-if="errorText">
			{{ errorText }}
		</p>
		<button class="button primary" @click="state = STATES.EDITING">
			{{ t('mail', 'Go back') }}
		</button>
		<button class="button" @click="onForceSend">
			{{ t('mail', 'Send anyway') }}
		</button>
	</div>
	<EmptyContent v-else icon="icon-checkmark">
		<h2>{{ sendAtVal ? t('mail', 'Message will be sent at') + ' ' + convertToLocalDate(sendAtVal) : t('mail', 'Message sent!') }}</h2>
	</EmptyContent>
</template>

<script>
import debounce from 'lodash/fp/debounce'
import uniqBy from 'lodash/fp/uniqBy'
import isArray from 'lodash/fp/isArray'
import trimStart from 'lodash/fp/trimCharsStart'
import Autosize from 'vue-autosize'
import debouncePromise from 'debounce-promise'
import Actions from '@nextcloud/vue/dist/Components/Actions'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import ActionCheckbox from '@nextcloud/vue/dist/Components/ActionCheckbox'
import ActionInput from '@nextcloud/vue/dist/Components/ActionInput'
import ActionLink from '@nextcloud/vue/dist/Components/ActionLink'
import ActionRadio from '@nextcloud/vue/dist/Components/ActionRadio'
import EmptyContent from '@nextcloud/vue/dist/Components/EmptyContent'
import Multiselect from '@nextcloud/vue/dist/Components/Multiselect'
import { showError } from '@nextcloud/dialogs'
import { translate as t, getCanonicalLocale, getFirstDay, getLocale } from '@nextcloud/l10n'
import Vue from 'vue'

import ComposerAttachments from './ComposerAttachments'
import ChevronLeft from 'vue-material-design-icons/ChevronLeft'
import { findRecipient } from '../service/AutocompleteService'
import { detect, html, plain, toHtml, toPlain } from '../util/text'
import Loading from './Loading'
import logger from '../logger'
import TextEditor from './TextEditor'
import { buildReplyBody } from '../ReplyBuilder'
import MailvelopeEditor from './MailvelopeEditor'
import { getMailvelope } from '../crypto/mailvelope'
import { isPgpgMessage } from '../crypto/pgp'
import { matchError } from '../errors/match'
import NoSentMailboxConfiguredError
	from '../errors/NoSentMailboxConfiguredError'
import NoDraftsMailboxConfiguredError
	from '../errors/NoDraftsMailboxConfiguredError'
import ManyRecipientsError
	from '../errors/ManyRecipientsError'
import Send from 'vue-material-design-icons/Send'
import SendClock from 'vue-material-design-icons/SendClock'
import moment from '@nextcloud/moment'
import { mapGetters } from 'vuex'

const debouncedSearch = debouncePromise(findRecipient, 500)

const NO_ALIAS_SET = -1

Vue.use(Autosize)

const STATES = Object.seal({
	EDITING: 0,
	UPLOADING: 1,
	SENDING: 2,
	ERROR: 3,
	WARNING: 4,
	FINISHED: 5,
	DISCARDING: 6,
	DISCARDED: 7,
})

export default {
	name: 'Composer',
	components: {
		MailvelopeEditor,
		Actions,
		ActionButton,
		ActionCheckbox,
		ActionInput,
		ActionLink,
		ActionRadio,
		ComposerAttachments,
		ChevronLeft,
		Loading,
		Multiselect,
		TextEditor,
		EmptyContent,
		Send,
		SendClock,
	},
	props: {
		fromAccount: {
			type: Number,
			default: () => undefined,
		},
		to: {
			type: Array,
			default: () => [],
		},
		cc: {
			type: Array,
			default: () => [],
		},
		bcc: {
			type: Array,
			default: () => [],
		},
		subject: {
			type: String,
			default: '',
		},
		body: {
			type: Object,
			default: () => html(''),
		},
		draftId: {
			type: Number,
			default: undefined,
		},
		draft: {
			type: Function,
			required: true,
		},
		send: {
			type: Function,
			required: true,
		},
		replyTo: {
			type: Object,
			required: false,
			default: () => undefined,
		},
		forwardFrom: {
			type: Object,
			required: false,
			default: () => undefined,
		},
		forwardedMessages: {
			type: Array,
			required: false,
			default: () => [],
		},
		sendAt: {
			type: Number,
			default: undefined,
		},
		attachmentsData: {
			type: Array,
			default: () => [],
		},
	},
	data() {
		let bodyVal = toHtml(this.body).value
		if (bodyVal.length === 0) {
			// an empty body (e.g "") does not trigger an onInput event.
			// but to append the signature a onInput event is required.
			bodyVal = '<p></p><p></p>'
		}

		// Set default custom date time picker value to now + 1 hour
		const selectedDate = new Date()
		selectedDate.setHours(selectedDate.getHours() + 1)

		return {
			showCC: this.cc.length > 0,
			selectedAlias: NO_ALIAS_SET, // Fixed in `beforeMount`
			autocompleteRecipients: this.to.concat(this.cc).concat(this.bcc),
			newRecipients: [],
			subjectVal: this.subject,
			bodyVal,
			attachments: this.attachmentsData,
			noReply: this.to.some((to) => to.email.startsWith('noreply@') || to.email.startsWith('no-reply@')),
			draftsPromise: Promise.resolve(this.draftId),
			attachmentsPromise: Promise.resolve(),
			canSaveDraft: true,
			savingDraft: undefined,
			saveDraftDebounced: debounce(10 * 1000, this.saveDraft),
			state: STATES.EDITING,
			errorText: undefined,
			STATES,
			selectTo: this.to,
			selectCc: this.cc,
			selectBcc: this.bcc,
			bus: new Vue(),
			encrypt: false,
			mailvelope: {
				available: false,
				keyRing: undefined,
				keysMissing: [],
			},
			editorMode: (this.body?.format !== 'html') ? 'plaintext' : 'html',
			addShareLink: t('mail', 'Add share link from {productName} Files', { productName: OC?.theme?.name ?? 'Nextcloud' }),
			requestMdn: false,
			appendSignature: true,
			loadingIndicatorTo: false,
			loadingIndicatorCc: false,
			loadingIndicatorBcc: false,
			isMoreActionsOpen: false,
			selectedDate,
			sendAtVal: this.sendAt,
			firstDayDatetimePicker: getFirstDay() === 0 ? 7 : getFirstDay(),
			formatter: {
				stringify: (date) => {
					return date ? moment(date).format('LLL') : ''
				},
				parse: (value) => {
					return value ? moment(value, 'LLL').toDate() : null
				},
			},
		}
	},
	computed: {
		...mapGetters([
			'isScheduledSendingDisabled',
		]),
		aliases() {
			let cnt = 0
			const accounts = this.$store.getters.accounts.filter((a) => !a.isUnified)
			const aliases = accounts.flatMap((account) => [
				{
					id: account.id,
					aliasId: null,
					selectId: cnt++,
					editorMode: account.editorMode,
					signature: account.signature,
					name: account.name,
					emailAddress: account.emailAddress,
					signatureAboveQuote: account.signatureAboveQuote,
				},
				account.aliases.map((alias) => {
					return {
						id: account.id,
						aliasId: alias.id,
						selectId: cnt++,
						editorMode: account.editorMode,
						signature: alias.signature,
						name: alias.name,
						emailAddress: alias.alias,
						signatureAboveQuote: account.signatureAboveQuote,
					}
				}),
			])
			return aliases.flat()
		},
		allRecipients() {
			return this.selectTo.concat(this.selectCc).concat(this.selectBcc)
		},
		attachmentSizeLimit() {
			return this.$store.getters.getPreference('attachment-size-limit')
		},
		selectableRecipients() {
			return this.newRecipients
				.concat(this.autocompleteRecipients)
				.map((recipient) => ({ ...recipient, label: recipient.label || recipient.email }))
		},
		isForward() {
			return this.forwardFrom !== undefined
		},
		isReply() {
			return this.replyTo !== undefined
		},
		canSend() {
			if (this.encrypt && this.mailvelope.keysMissing.length) {
				return false
			}

			return this.selectTo.length > 0 || this.selectCc.length > 0 || this.selectBcc.length > 0
		},
		editorPlainText() {
			return this.editorMode === 'plaintext'
		},
		submitButtonTitle() {
			if (this.sendAtVal) {
				return t('mail', 'Send later') + ` ${this.convertToLocalDate(this.sendAtVal)}`
			}
			if (!this.mailvelope.available) {
				return t('mail', 'Send')
			}

			return this.encrypt ? t('mail', 'Encrypt and send') : t('mail', 'Send unencrypted')
		},
		dateTomorrowMorning() {
			const today = new Date()
			today.setTime(today.getTime() + 24 * 60 * 60 * 1000)
			return today.setHours(9, 0, 0, 0)

		},
		dateTomorrowAfternoon() {
			const today = new Date()
			today.setTime(today.getTime() + 24 * 60 * 60 * 1000)
			return today.setHours(14, 0, 0, 0)
		},
		dateMondayMorning() {
			const today = new Date()
			today.setHours(9, 0, 0, 0)
			return today.setDate(today.getDate() + (7 - today.getDay()) % 7 + 1)
		},
		customSendTime() {
			return new Date(this.selectedDate).getTime()
		},
		showAmPm() {
			const localeData = moment().locale(getLocale()).localeData()
			const timeFormat = localeData.longDateFormat('LT').toLowerCase()

			return timeFormat.indexOf('a') !== -1
		},
		isSendAtTomorrowMorning() {
			return this.sendAtVal
				&& Math.floor(this.dateTomorrowMorning / 1000) === Math.floor(this.sendAtVal / 1000)
		},
		isSendAtTomorrowAfternoon() {
			return this.sendAtVal
				&& Math.floor(this.dateTomorrowAfternoon / 1000) === Math.floor(this.sendAtVal / 1000)
		},
		isSendAtMondayMorning() {
			return this.sendAtVal
				&& Math.floor(this.dateMondayMorning / 1000) === Math.floor(this.sendAtVal / 1000)
		},
		isSendAtCustom() {
			return this.sendAtVal
				&& !this.isSendAtTomorrowMorning
				&& !this.isSendAtTomorrowAfternoon
				&& !this.isSendAtMondayMorning
		},
	},
	watch: {
		'$route.params.threadId'() {
			this.reset()
		},
		allRecipients() {
			this.checkRecipientsKeys()
		},
		aliases(newAliases) {
			console.debug('aliases changed')
			if (this.selectedAlias === NO_ALIAS_SET) {
				return
			}

			const newAlias = newAliases.find(alias => alias.id === this.selectedAlias.id && alias.aliasId === this.selectedAlias.aliasId)
			if (newAlias === undefined) {
				// selected alias does not exist anymore.
				this.onAliasChange(newAliases[0])
			} else {
				// update the selected alias
				this.onAliasChange(newAlias)
			}
		},
	},
	async beforeMount() {
		this.setAlias()
		this.initBody()

		await this.onMailvelopeLoaded(await getMailvelope())
	},
	mounted() {
		if (!this.isReply) {
			this.$refs.toLabel.$el.focus()
		}

		// Add attachments in case of forward
		if (this.forwardFrom?.attachments !== undefined) {
			this.forwardFrom.attachments.forEach(att => {
				this.attachments.push({
					fileName: att.fileName,
					displayName: trimStart('/', att.fileName),
					id: att.id,
					messageId: this.forwardFrom.databaseId,
					type: 'message-attachment',
				})
			})
		}
		// Add messages forwarded as attachments
		let forwards = []
		if (this.forwardedMessages && !isArray(this.forwardedMessages)) {
			forwards = [this.forwardedMessages]
		} else if (this.forwardedMessages && isArray(this.forwardedMessages)) {
			forwards = this.forwardedMessages
		}
		forwards.forEach(id => {
			const env = this.$store.getters.getEnvelope(id)
			if (!env) {
				// TODO: also happens when the composer page is reloaded
				showError(t('mail', 'Message {id} could not be found', {
					id,
				}))
				return
			}

			this.attachments.push({
				displayName: env.subject + '.eml',
				id,
				type: 'message',
			})
		})

		// Set custom date and time picker value if initialized with custom send at value
		if (this.sendAt && this.isSendAtCustom) {
			this.selectedDate = new Date(this.sendAt)
		}
	},
	beforeDestroy() {
		window.removeEventListener('mailvelope', this.onMailvelopeLoaded)
	},
	methods: {
		setAlias() {
			const previous = this.selectedAlias
			if (this.fromAccount) {
				this.selectedAlias = this.aliases.find((alias) => alias.id === this.fromAccount)
			} else {
				this.selectedAlias = this.aliases[0]
			}
			// only overwrite editormode if no body provided
			if (previous === NO_ALIAS_SET && !this.body) {
				this.editorMode = this.selectedAlias.editorMode
			}
		},
		async checkRecipientsKeys() {
			if (!this.encrypt || !this.mailvelope.available) {
				return
			}

			const recipients = this.allRecipients.map((r) => r.email)
			const keysValid = await this.mailvelope.keyRing.validKeyForAddress(recipients)
			logger.debug('recipients keys validated', { recipients, keysValid })
			this.mailvelope.keysMissing = recipients.filter((r) => keysValid[r] === false)
		},
		initBody() {
			/** @var {Text} body **/
			let body
			if (this.replyTo) {
				body = buildReplyBody(
					this.editorPlainText ? toPlain(this.body) : toHtml(this.body),
					this.replyTo.from[0],
					this.replyTo.dateInt,
					this.$store.getters.getPreference('reply-mode', 'top') === 'top'
				).value
			} else if (this.forwardFrom) {
				body = buildReplyBody(
					this.editorPlainText ? toPlain(this.body) : toHtml(this.body),
					this.forwardFrom.from[0],
					this.forwardFrom.dateInt,
					this.$store.getters.getPreference('reply-mode', 'top') === 'top'
				).value
			} else {
				body = this.bodyVal
			}
			this.bodyVal = html(body).value
		},
		getMessageData(id) {
			return {
				// TODO: Rename account to accountId
				account: this.selectedAlias.id,
				accountId: this.selectedAlias.id,
				aliasId: this.selectedAlias.aliasId,
				to: this.selectTo,
				cc: this.selectCc,
				bcc: this.selectBcc,
				draftId: id,
				subject: this.subjectVal,
				body: this.encrypt ? plain(this.bodyVal) : html(this.bodyVal),
				attachments: this.attachments,
				messageId: this.replyTo ? this.replyTo.databaseId : undefined,
				isHtml: !this.editorPlainText,
				requestMdn: this.requestMdn,
				sendAt: this.sendAtVal ? Math.floor(this.sendAtVal / 1000) : undefined,
			}
		},
		saveDraft(data) {
			this.savingDraft = true
			this.draftsPromise = this.draftsPromise
				.then((id) => {
					const draftData = data(id)
					if (
						!id
						&& !draftData.subject
						&& !draftData.body
						&& !draftData.cc
						&& !draftData.bcc
						&& !draftData.to
						&& !draftData.sendAt
					) {
						// this might happen after a call to reset()
						// where the text input gets reset as well
						// and fires an input event
						logger.debug('Nothing substantial to save, ignoring draft save')
						this.savingDraft = false
						return id
					}
					return this.draft(draftData)
				})
				.then((uid) => {
					// It works (again)
					this.canSaveDraft = true

					return uid
				})
				.catch(async(error) => {
					console.error('could not save draft', error)
					const canSave = await matchError(error, {
						[NoDraftsMailboxConfiguredError.getName()]() {
							return false
						},
						default() {
							return true
						},
					})
					if (!canSave) {
						this.canSaveDraft = false
					}
				})
				.then((uid) => {
					this.savingDraft = false
					return uid
				})
			return this.draftsPromise
		},
		onInputChanged() {
			this.saveDraftDebounced(this.getMessageData)
			if (this.appendSignature) {
				const signatureValue = toHtml(detect(this.selectedAlias.signature)).value
				this.bus.$emit('insertSignature', signatureValue, this.selectedAlias.signatureAboveQuote)
				this.appendSignature = false
			}
		},
		onChangeSendLater(value) {
			this.sendAtVal = value ? Number.parseInt(value, 10) : undefined
		},
		convertToLocalDate(timestamp) {
			const options = {
				month: 'short',
				day: 'numeric',
				hour: '2-digit',
				minute: '2-digit',
			}
			return new Date(timestamp).toLocaleString(getCanonicalLocale(), options)
		},
		onAliasChange(alias) {
			logger.debug('changed alias', { alias })
			this.selectedAlias = alias
			this.appendSignature = true
			this.onInputChanged()
		},
		onAddLocalAttachment() {
			this.bus.$emit('onAddLocalAttachment')
		},
		onAddCloudAttachment() {
			this.bus.$emit('onAddCloudAttachment')
		},
		onAddCloudAttachmentLink() {
			this.bus.$emit('onAddCloudAttachmentLink')
		},
		onAutocomplete(term, loadingIndicator) {
			if (term === undefined || term === '') {
				return
			}
			this.loadingIndicatorTo = loadingIndicator === 'to'
			this.loadingIndicatorCc = loadingIndicator === 'cc'
			this.loadingIndicatorBcc = loadingIndicator === 'bcc'
			debouncedSearch(term).then((results) => {
				if (loadingIndicator === 'to') {
					this.loadingIndicatorTo = false
				} else if (loadingIndicator === 'cc') {
					this.loadingIndicatorCc = false
				} else if (loadingIndicator === 'bcc') {
					this.loadingIndicatorBcc = false
				}
				this.autocompleteRecipients = uniqBy('email')(this.autocompleteRecipients.concat(results))
			})
		},
		onAttachmentsUploading(uploaded) {
			this.attachmentsPromise = this.attachmentsPromise
				.then(() => uploaded)
				.catch((error) => logger.error('could not upload attachments', { error }))
				.then(() => logger.debug('attachments uploaded'))
		},
		async onMailvelopeLoaded(mailvelope) {
			this.encrypt = isPgpgMessage(this.body)
			this.mailvelope.available = true
			logger.info('Mailvelope loaded', {
				encrypt: this.encrypt,
				isPgpgMessage: isPgpgMessage(this.body),
				keyRing: this.mailvelope.keyRing,
			})
			this.mailvelope.keyRing = await mailvelope.getKeyring()
			await this.checkRecipientsKeys()
		},
		onNewToAddr(addr) {
			this.onNewAddr(addr, this.selectTo)
		},
		onNewCcAddr(addr) {
			this.onNewAddr(addr, this.selectCc)
		},
		onNewBccAddr(addr) {
			this.onNewAddr(addr, this.selectBcc)
		},
		onNewAddr(addr, list) {
			const res = {
				label: addr, // TODO: parse if possible
				email: addr, // TODO: parse if possible
			}
			this.newRecipients.push(res)
			list.push(res)
		},
		async onSend(_, force = false) {
			if (this.encrypt) {
				logger.debug('get encrypted message from mailvelope')
				await this.$refs.mailvelopeEditor.pull()
			}

			this.state = STATES.UPLOADING

			await this.attachmentsPromise
				.then(() => (this.state = STATES.SENDING))
				.then(() => this.draftsPromise)
				.then(this.getMessageData)
				.then((data) => this.send({ ...data, force }))
				.then(() => logger.info('message sent'))
				.then(() => (this.state = STATES.FINISHED))
				.catch(async(error) => {
					logger.error('could not send message', { error });
					[this.errorText, this.state] = await matchError(error, {
						[NoSentMailboxConfiguredError.getName()]() {
							return [t('mail', 'No sent mailbox configured. Please pick one in the account settings.'), STATES.ERROR]
						},
						[ManyRecipientsError.getName()]() {
							return [t('mail', 'You are trying to send to many recipients in To and/or Cc. Consider using Bcc to hide recipient addresses.'), STATES.WARNING]
						},
						default(error) {
							if (error && error.toString) {
								return [error.toString(), STATES.ERROR]
							}
						},
					})
				})

			// Sync sent mailbox when it's currently open
			const account = this.$store.getters.getAccount(this.selectedAlias.id)
			if (parseInt(this.$route.params.mailboxId, 10) === account.sentMailboxId) {
				setTimeout(() => {
					this.$store.dispatch('syncEnvelopes', {
						mailboxId: account.sentMailboxId,
						query: '',
						init: false,
					})
				}, 500)
			}
		},
		async onForceSend() {
			await this.onSend(null, true)
		},
		reset() {
			this.draftsPromise = Promise.resolve() // "resets" draft uid as well
			this.selectTo = []
			this.selectCc = []
			this.selectBcc = []
			this.subjectVal = ''
			this.bodyVal = '<p></p><p></p>'
			this.attachments = []
			this.errorText = undefined
			this.state = STATES.EDITING
			this.autocompleteRecipients = []
			this.newRecipients = []
			this.requestMdn = false
			this.appendSignature = true
			this.savingDraft = undefined
			this.sendAtVal = undefined

			this.setAlias()
			this.initBody()
			Vue.nextTick(() => {
				// toLabel may not be on the DOM yet
				// (because "Message sent" is shown)
				// so we defer the focus call
				this.$refs.toLabel.$el.focus()
			})
		},
		/**
		 * Format aliases for the Multiselect
		 * @param {Object} alias the alias to format
		 * @returns {string}
		 */
		formatAliases(alias) {
			if (!alias.name) {
				return alias.emailAddress
			}

			return `${alias.name} <${alias.emailAddress}>`
		},
		async discardDraft() {
			this.state = STATES.DISCARDING
			const id = await this.draftsPromise
			await this.$store.dispatch('deleteMessage', { id })
			this.state = STATES.DISCARDED
			this.$emit('close')
		},
		/**
		 * Whether the date is acceptable
		 *
		 * @param {Date} date The date to compare to
		 * @returns {boolean}
		 */
		disabledDatetimepickerDate(date) {
			const minimumDate = new Date()
			// Make it one sec before midnight so it shows the next full day as available
			minimumDate.setHours(0, 0, 0)
			minimumDate.setSeconds(minimumDate.getSeconds() - 1)

			return date.getTime() <= minimumDate
		},

		/**
		 * Whether the time for date is acceptable
		 *
		 * @param {Date} date The date to compare to
		 * @returns {boolean}
		 */
		disabledDatetimepickerTime(date) {
			const now = new Date()
			const minimumDate = new Date(now.getTime())
			return date.getTime() <= minimumDate
		},
	},
}
</script>

<style lang="scss" scoped>
.message-composer {
	margin: 0;
	z-index: 100;
}

.composer-actions {
	display: flex;
	flex-direction: row;
	align-items: flex-end;
	justify-content: space-between;
	position: sticky;
	bottom: 0;
	padding: 12px;
	background: linear-gradient(rgba(255, 255, 255, 0), var(--color-main-background-translucent) 50%);
}

.composer-actions-right {
	display: flex;
	align-items: center;
}

.composer-fields {
	display: flex;
	align-items: center;
	border-top: 1px solid var(--color-border);

	&.mail-account {
		border-top: none;
		padding-top: 10px;

		& > .multiselect {
			max-width: none;
			min-height: auto;
		}
	}

	.multiselect,
	input,
	TextEditor {
		flex-grow: 1;
		max-width: none;
		border: none;
		border-radius: 0;
	}

	.multiselect {
		margin-right: 12px;
	}
}

.subject {
	font-size: 20px;
	font-weight: bold;
	margin: 0;
	padding: 24px 20px;
}

.warning-box {
	padding: 5px 12px;
	border-radius: 0;
}

.message-body {
	min-height: 570px;
	width: 100%;
	margin: 0;
	border: none !important;
	outline: none !important;
	box-shadow: none !important;
	padding-top: 12px;
	padding-bottom: 12px;
	padding-left: 20px;
}

#draft-status {
	padding: 5px;
	opacity: 0.5;
	font-size: small;
}

.from-label,
.to-label,
.copy-toggle,
.cc-label,
.bcc-label {
	padding-top: 12px;
	padding-bottom: 12px;
	padding-right: 20px;
	cursor: text;
	color: var(--color-text-maxcontrast);
	width: 100px;
	text-align: right;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
}

.bcc-label {
	top: initial;
	bottom: 0;
}

.copy-toggle {
	cursor: pointer;
	width: initial;

	&:hover,
	&:focus {
		color: var(--color-main-text);
	}
}

.reply {
	min-height: 100px;
}

::v-deep .multiselect .multiselect__tags {
	border: none !important;
}

.sending-hint {
	height: 50px;
	margin-top: 50px;
}
.button {
	background-color: transparent;
	border: none;
}
.emptycontent {
	margin-top: 250px;
	height: 120px;
}
.send-action-radio {
	padding: 5px 0 5px 0;
}
.send-button {
	display: flex;
	align-items: center;
	padding: 10px 15px;
	margin-left: 5px;
}
.send-button .send-icon {
	padding-right: 5px;
}
</style>
