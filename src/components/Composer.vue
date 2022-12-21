<template>
	<div v-if="state === STATES.EDITING" class="message-composer">
		<div class="composer-fields composer-fields__from mail-account">
			<label class="from-label" for="from">
				{{ t('mail', 'From') }}
			</label>
			<div class="composer-fields--custom">
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
		</div>
		<div class="composer-fields">
			<label class="to-label" for="to">
				{{ t('mail', 'To') }}
			</label>
			<div class="composer-fields--custom">
				<Multiselect id="to"
					ref="toLabel"
					v-model="selectTo"
					:class="{'opened': !autoLimit}"
					:options="selectableRecipients"
					:taggable="true"
					label="label"
					track-by="email"
					:multiple="true"
					:placeholder="t('mail', 'Contact or email address …')"
					:clear-on-select="true"
					:close-on-select="false"
					:show-no-options="false"
					:preserve-search="true"
					:hide-selected="true"
					:loading="loadingIndicatorTo"
					:auto-limit="autoLimit"
					:options-limit="30"
					@input="callSaveDraft(true, getMessageData)"
					@tag="onNewToAddr"
					@search-change="onAutocomplete($event, 'to')">
					<template #tag="{ option }">
						<RecipientListItem
							:option="option"
							@remove-recipient="onRemoveRecipient(option, 'to')" />
					</template>
					<template #option="{ option }">
						<div class="multiselect__tag multiselect__tag-custom">
							<ListItemIcon
								:no-margin="true"
								:title="option.label"
								:subtitle="option.email"
								:url="option.photo"
								:avatar-size="24" />
						</div>
					</template>
				</Multiselect>
				<button
					:title="t('mail','Toggle recipients list mode')"
					:class="{'active':!autoLimit}"
					@click.prevent="toggleViewMode">
					<UnfoldMoreHorizontal v-if="autoLimit" :size="24" />
					<UnfoldLessHorizontal v-else :size="24" />
				</button>
			</div>
		</div>
		<div v-if="showCC" class="composer-fields">
			<label for="cc" class="cc-label">
				{{ t('mail', 'Cc') }}
			</label>
			<div class="composer-fields--custom">
				<Multiselect id="cc"
					v-model="selectCc"
					:class="{'opened': !autoLimit}"
					:options="selectableRecipients"
					:taggable="true"
					label="label"
					track-by="email"
					:multiple="true"
					:placeholder="t('mail', 'Contact or email address …')"
					:clear-on-select="true"
					:show-no-options="false"
					:preserve-search="true"
					:loading="loadingIndicatorCc"
					:auto-limit="autoLimit"
					:hide-selected="true"
					:options-limit="30"
					@input="callSaveDraft(true, getMessageData)"
					@tag="onNewCcAddr"
					@search-change="onAutocomplete($event, 'cc')">
					<template #tag="{ option }">
						<RecipientListItem
							:option="option"
							@remove-recipient="onRemoveRecipient(option, 'cc')" />
					</template>
					<template #option="{ option }">
						<div class="multiselect__tag multiselect__tag-custom">
							<ListItemIcon
								:no-margin="true"
								:title="option.label"
								:subtitle="option.email"
								:url="option.photo"
								:avatar-size="24" />
						</div>
					</template>
					<span slot="noOptions">{{ t('mail', '') }}</span>
				</Multiselect>
			</div>
		</div>
		<div v-if="showBCC" class="composer-fields">
			<label for="bcc" class="bcc-label">
				{{ t('mail', 'Bcc') }}
			</label>
			<div class="composer-fields--custom">
				<Multiselect id="bcc"
					v-model="selectBcc"
					:class="{'opened': !autoLimit}"
					:options="selectableRecipients"
					:taggable="true"
					label="label"
					track-by="email"
					:multiple="true"
					:placeholder="t('mail', 'Contact or email address …')"
					:show-no-options="false"
					:clear-on-select="true"
					:preserve-search="true"
					:loading="loadingIndicatorBcc"
					:hide-selected="true"
					:options-limit="30"
					@input="callSaveDraft(true, getMessageData)"
					@tag="onNewBccAddr"
					@search-change="onAutocomplete($event, 'bcc')">
					<template #tag="{ option }">
						<RecipientListItem
							:option="option"
							@remove-recipient="onRemoveRecipient(option, 'bcc')" />
					</template>
					<template #option="{ option }">
						<div class="multiselect__tag multiselect__tag-custom">
							<ListItemIcon
								:no-margin="true"
								:title="option.label"
								:subtitle="option.email"
								:url="option.photo"
								:avatar-size="24" />
						</div>
					</template>
					<span slot="noOptions">{{ t('mail', 'No contacts found.') }}</span>
				</Multiselect>
			</div>
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
				@input="callSaveDraft(true, getMessageData)">
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
		<div class="composer-fields message-editor">
			<!--@keypress="onBodyKeyPress"-->
			<TextEditor
				v-if="!encrypt"
				ref="editor"
				:key="editorMode"
				:value="bodyVal"
				:html="!editorPlainText"
				name="body"
				class="message-body"
				:placeholder="t('mail', 'Write message …')"
				:focus="isReply"
				:bus="bus"
				@input="onEditorInput"
				@ready="onEditorReady" />
			<MailvelopeEditor
				v-else
				ref="mailvelopeEditor"
				:value="bodyVal"
				:recipients="allRecipients"
				:quoted-text="body"
				:is-reply-or-forward="isReply || isForward"
				@input="onEditorInput" />
		</div>
		<ComposerAttachments v-model="attachments"
			:bus="bus"
			:upload-size-limit="attachmentSizeLimit"
			@upload="onAttachmentsUploading" />
		<div class="composer-actions-right composer-actions">
			<div class="composer-actions--primary-actions">
				<p class="composer-actions-draft-status">
					<span v-if="savingDraft === true" class="draft-status">{{ t('mail', 'Saving draft …') }}</span>
					<span v-else-if="!canSaveDraft" class="draft-status">{{ t('mail', 'Error saving draft') }}</span>
					<span v-else-if="savingDraft === false" class="draft-status">{{ t('mail', 'Draft saved') }}</span>
				</p>
				<ButtonVue v-if="!savingDraft && !canSaveDraft"
					class="button"
					type="tertiary"
					@click="onSave">
					<template #icon>
						<Download :size="20" :title="t('mail', 'Save draft')" />
					</template>
				</ButtonVue>
				<ButtonVue v-if="savingDraft === false"
					class="button"
					type="tertiary"
					@click="discardDraft">
					<template #icon>
						<Delete :size="20" :title="t('mail', 'Discard & close draft')" />
					</template>
				</ButtonVue>
			</div>
			<div class="composer-actions--secondary-actions">
				<Actions @close="isMoreActionsOpen = false">
					<template v-if="!isMoreActionsOpen">
						<ActionButton @click="onAddLocalAttachment">
							<template #icon>
								<IconUpload :size="20" />
							</template>
							{{
								t('mail', 'Upload attachment')
							}}
						</ActionButton>
						<ActionButton @click="onAddCloudAttachment">
							<template #icon>
								<IconFolder :sizse="20" />
							</template>
							{{
								t('mail', 'Add attachment from Files')
							}}
						</ActionButton>
						<ActionButton :disabled="encrypt" @click="onAddCloudAttachmentLink">
							<template #icon>
								<IconPublic :size="20" />
							</template>
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
						<ActionButton
							v-if="!encrypt && editorPlainText"
							@click="setEditorModeHtml()">
							<template #icon>
								<IconHtml :size="20" />
							</template>
							{{ t('mail', 'Enable formatting') }}
						</ActionButton>
						<ActionButton
							v-if="!encrypt && !editorPlainText"
							@click="setEditorModeText()">
							<template #icon>
								<IconClose :size="20" />
							</template>
							{{ t('mail', 'Disable formatting') }}
						</ActionButton>
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
							@change="onChangeSendLater(undefined)">
							{{ t('mail', 'Send now') }}
						</ActionRadio>
						<ActionRadio :value="dateTomorrowMorning"
							name="sendLater"
							:checked="isSendAtTomorrowMorning"
							class="send-action-radio send-action-radio--multiline"
							@change="onChangeSendLater(dateTomorrowMorning)">
							{{ t('mail', 'Tomorrow morning') }} - {{ convertToLocalDate(dateTomorrowMorning) }}
						</ActionRadio>
						<ActionRadio :value="dateTomorrowAfternoon"
							name="sendLater"
							:checked="isSendAtTomorrowAfternoon"
							class="send-action-radio send-action-radio--multiline"
							@change="onChangeSendLater(dateTomorrowAfternoon)">
							{{ t('mail', 'Tomorrow afternoon') }} - {{ convertToLocalDate(dateTomorrowAfternoon) }}
						</ActionRadio>
						<ActionRadio :value="dateMondayMorning"
							name="sendLater"
							:checked="isSendAtMondayMorning"
							class="send-action-radio send-action-radio--multiline"
							@change="onChangeSendLater(dateMondayMorning)">
							{{ t('mail', 'Monday morning') }} - {{ convertToLocalDate(dateMondayMorning) }}
						</ActionRadio>
						<ActionRadio name="sendLater"
							class="send-action-radio"
							:checked="isSendAtCustom"
							:value="customSendTime"
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

				<ButtonVue :disabled="!canSend"
					native-type="submit"
					type="primary"
					@click="onSend">
					<template #icon>
						<Send
							:title="submitButtonTitle"
							:size="20" />
					</template>
					{{ submitButtonTitle }}
				</ButtonVue>
			</div>
		</div>
	</div>
	<Loading v-else-if="state === STATES.UPLOADING" :hint="t('mail', 'Uploading attachments …')" role="alert" />
	<Loading v-else-if="state === STATES.SENDING"
		:hint="t('mail', 'Sending …')"
		role="alert"
		class="sending-hint" />
	<EmptyContent v-else-if="state === STATES.ERROR"
		:title="t('mail', 'Error sending your message')"
		class="centered-content"
		role="alert">
		<p v-if="errorText">
			{{ errorText }}
		</p>
		<template #action>
			<ButtonVue type="tertiary" @click="state = STATES.EDITING">
				{{ t('mail', 'Go back') }}
			</ButtonVue>
			<ButtonVue type="tertiary" @click="onSend">
				{{ t('mail', 'Retry') }}
			</ButtonVue>
		</template>
	</EmptyContent>
	<EmptyContent v-else-if="state === STATES.WARNING" :title="t('mail', 'Warning sending your message')" role="alert">
		<p v-if="errorText">
			{{ errorText }}
		</p>
		<ButtonVue type="tertiary" @click="state = STATES.EDITING">
			{{ t('mail', 'Go back') }}
		</ButtonVue>
		<ButtonVue type="tertiary" @click="onForceSend">
			{{ t('mail', 'Send anyway') }}
		</ButtonVue>
	</EmptyContent>
	<EmptyContent v-else :title="sendAtVal ? t('mail', 'Message will be sent at') + ' ' + convertToLocalDate(sendAtVal) : t('mail', 'Message sent!')">
		<template #icon>
			<IconCheck :size="20" />
		</template>
	</EmptyContent>
</template>

<script>
import debounce from 'lodash/fp/debounce'
import uniqBy from 'lodash/fp/uniqBy'
import isArray from 'lodash/fp/isArray'
import trimStart from 'lodash/fp/trimCharsStart'
import Autosize from 'vue-autosize'
import debouncePromise from 'debounce-promise'

import { NcActions as Actions, NcActionButton as ActionButton, NcActionCheckbox as ActionCheckbox, NcActionInput as ActionInput, NcActionRadio as ActionRadio, NcButton as ButtonVue, NcEmptyContent as EmptyContent, NcMultiselect as Multiselect, NcListItemIcon as ListItemIcon } from '@nextcloud/vue'
import ChevronLeft from 'vue-material-design-icons/ChevronLeft'
import Delete from 'vue-material-design-icons/Delete'
import ComposerAttachments from './ComposerAttachments'
import Download from 'vue-material-design-icons/Download'
import IconUpload from 'vue-material-design-icons/Upload'
import IconFolder from 'vue-material-design-icons/Folder'
import IconPublic from 'vue-material-design-icons/Link'
import IconCheck from 'vue-material-design-icons/Check'
import RecipientListItem from './RecipientListItem'
import UnfoldMoreHorizontal from 'vue-material-design-icons/UnfoldMoreHorizontal'
import UnfoldLessHorizontal from 'vue-material-design-icons/UnfoldLessHorizontal'
import IconHtml from 'vue-material-design-icons/ImageSizeSelectActual'
import IconClose from 'vue-material-design-icons/Close'
import { showError } from '@nextcloud/dialogs'
import { getCanonicalLocale, getFirstDay, getLocale, translate as t } from '@nextcloud/l10n'
import Vue from 'vue'

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
import NoSentMailboxConfiguredError from '../errors/NoSentMailboxConfiguredError'
import NoDraftsMailboxConfiguredError from '../errors/NoDraftsMailboxConfiguredError'
import ManyRecipientsError from '../errors/ManyRecipientsError'

import Send from 'vue-material-design-icons/Send'
import SendClock from 'vue-material-design-icons/SendClock'
import moment from '@nextcloud/moment'
import { mapGetters } from 'vuex'
import { TRIGGER_CHANGE_ALIAS, TRIGGER_EDITOR_READY } from '../ckeditor/signature/InsertSignatureCommand'
import { EDITOR_MODE_HTML, EDITOR_MODE_TEXT } from '../store/constants'

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
		ActionRadio,
		ButtonVue,
		ComposerAttachments,
		ChevronLeft,
		Delete,
		Download,
		IconUpload,
		IconFolder,
		IconPublic,
		IconCheck,
		Loading,
		Multiselect,
		TextEditor,
		EmptyContent,
		ListItemIcon,
		RecipientListItem,
		Send,
		SendClock,
		UnfoldMoreHorizontal,
		UnfoldLessHorizontal,
		IconHtml,
		IconClose,
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
		editorBody: {
			type: String,
			default: '',
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
		inReplyToMessageId: {
			type: String,
			default: undefined,
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
		// Set default custom date time picker value to now + 1 hour
		const selectedDate = new Date()
		selectedDate.setHours(selectedDate.getHours() + 1)

		return {
			showCC: this.cc.length > 0,
			showBCC: this.bcc.length > 0,
			selectedAlias: NO_ALIAS_SET, // Fixed in `beforeMount`
			autocompleteRecipients: this.to.concat(this.cc).concat(this.bcc),
			newRecipients: [],
			subjectVal: this.subject,
			bodyVal: this.editorBody,
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
			editorMode: (this.body?.format !== 'html') ? EDITOR_MODE_TEXT : EDITOR_MODE_HTML,
			addShareLink: t('mail', 'Add share link from {productName} Files', { productName: OC?.theme?.name ?? 'Nextcloud' }),
			requestMdn: false,
			changeSignature: false,
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
			autoLimit: true,
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
			return this.editorMode === EDITOR_MODE_TEXT
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
			if (this.sendAtVal && Math.floor(this.dateTomorrowMorning / 1000) === Math.floor(this.sendAtVal / 1000)) {
				return true
			} else {
				return false
			}
		},
		isSendAtTomorrowAfternoon() {
			if (this.sendAtVal && Math.floor(this.dateTomorrowAfternoon / 1000) === Math.floor(this.sendAtVal / 1000)) {
				return true
			} else {
				return false
			}
		},
		isSendAtMondayMorning() {
			if (this.sendAtVal && Math.floor(this.dateMondayMorning / 1000) === Math.floor(this.sendAtVal / 1000)) {
				return true
			} else {
				return false
			}
		},
		isSendAtCustom() {
			if (this.sendAtVal && !this.isSendAtTomorrowMorning && !this.isSendAtTomorrowAfternoon && !this.isSendAtMondayMorning) {
				return true
			} else {
				return false
			}
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
			this.$nextTick(() => this.$refs.toLabel.$el.focus())
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
			// Only overwrite editormode if body is empty
			if (previous === NO_ALIAS_SET && (!this.body || this.body.value === '')) {
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
			/** @member {Text} body */
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
				inReplyToMessageId: this.inReplyToMessageId ?? (this.replyTo ? this.replyTo.messageId : undefined),
				isHtml: !this.encrypt && !this.editorPlainText,
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
				.catch(async (error) => {
					await matchError(error, {
						[NoDraftsMailboxConfiguredError.getName()]() {
							return false
						},
						default() {
							return true
						},
					})
					this.canSaveDraft = false
				})
				.then((uid) => {
					this.savingDraft = false
					return uid
				})
			return this.draftsPromise
		},
		callSaveDraft(withDebounce, ...args) {
			if (withDebounce) {
				return this.saveDraftDebounced(...args)
			} else {
				return this.saveDraft(...args)
			}
		},
		onSave() {
			this.callSaveDraft(false, this.getMessageData)
		},
		insertSignature() {
			let trigger

			if (this.changeSignature) {
				trigger = TRIGGER_CHANGE_ALIAS
			} else {
				trigger = TRIGGER_EDITOR_READY
			}

			this.$refs.editor.editorExecute('insertSignature',
				trigger,
				toHtml(detect(this.selectedAlias.signature)).value,
				this.selectedAlias.signatureAboveQuote
			)

			this.changeSignature = false
		},
		onEditorInput(text) {
			this.bodyVal = text
			this.callSaveDraft(true, this.getMessageData)
		},
		onEditorReady(editor) {
			this.bodyVal = editor.getData()
			this.insertSignature()
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
			this.changeSignature = true

			/**
			 * Alias change may change the editor mode as well.
			 *
			 * As editorMode is the key for the TextEditor component a change will destroy the current instance
			 * and the signature for the alias is inserted via onEditorReady event.
			 *
			 * Otherwise (when editorMode is the same) call insertSignature directly.
			 */
			if (this.editorMode === EDITOR_MODE_TEXT && alias.editorMode === EDITOR_MODE_HTML) {
				this.editorMode = EDITOR_MODE_HTML
			} else {
				this.insertSignature()
			}
		},
		onAddLocalAttachment() {
			this.bus.$emit('on-add-local-attachment')
			this.callSaveDraft(true, this.getMessageData)
		},
		onAddCloudAttachment() {
			this.bus.$emit('on-add-cloud-attachment')
			this.callSaveDraft(true, this.getMessageData)
		},
		onAddCloudAttachmentLink() {
			this.bus.$emit('on-add-cloud-attachment-link')
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
				.then(() => this.callSaveDraft(true, this.getMessageData))
				.then(() => logger.debug('attachments uploaded'))
				.catch((error) => logger.error('could not upload attachments', { error }))
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
			this.callSaveDraft(true, this.getMessageData)
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
				.catch(async (error) => {
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
			this.changeSignature = false
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
		 *
		 * @param {object} alias the alias to format
		 * @return {string}
		 */
		formatAliases(alias) {
			if (!alias.name) {
				return alias.emailAddress
			}

			return `${alias.name} <${alias.emailAddress}>`
		},
		async discardDraft() {
			const id = await this.draftsPromise
			this.$emit('discard-draft', id)
		},
		/**
		 * Whether the date is acceptable
		 *
		 * @param {Date} date The date to compare to
		 * @return {boolean}
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
		 * @return {boolean}
		 */
		disabledDatetimepickerTime(date) {
			const now = new Date()
			const minimumDate = new Date(now.getTime())
			return date.getTime() <= minimumDate
		},
		/**
		 * Remove recipient from recipients array (To,Cc,Bcc)
		 *
		 * @param {Array} option Current option from Multiselect
		 * @param {Array} field List of recipients (ex. this.selectTo)
		 */
		onRemoveRecipient(option, field) {
			switch (field) {
			case 'to':
				this.removeRecipientTo(option)
				break
			case 'cc':
				this.removeRecipientCc(option)
				break
			case 'bcc':
				this.removeRecipientBcc(option)
				break
			}
		},
		removeRecipient(option, list) {
			return list.filter((recipient) => recipient.email !== option.email)
		},
		removeRecipientTo(option) {
			this.selectTo = this.removeRecipient(option, this.selectTo)
		},
		removeRecipientCc(option) {
			this.selectCc = this.removeRecipient(option, this.selectCc)
		},
		removeRecipientBcc(option) {
			this.selectBcc = this.removeRecipient(option, this.selectBcc)
		},
		toggleViewMode() {
			this.autoLimit = !this.autoLimit
			this.showCC = !(this.showCC && this.selectCc.length === 0 && this.autoLimit)
			this.showBCC = !(this.showBCC && this.selectBcc.length === 0 && this.autoLimit)
		},
		setEditorModeHtml() {
			this.editorMode = EDITOR_MODE_HTML
		},
		setEditorModeText() {
			OC.dialogs.confirmDestructive(
				t('mail', 'Any existing formatting (for example bold, italic, underline or inline images) will be removed.'),
				t('mail', 'Turn off formatting'),
				{
					type: OC.dialogs.YES_NO_BUTTONS,
					confirm: t('mail', 'Turn off and remove formatting'),
					confirmClasses: 'error',
					cancel: t('mail', 'Keep formatting'),
				},
				(decision) => {
					if (decision) {
						this.editorMode = EDITOR_MODE_TEXT
					}
				},
			)
		},
	},
}
</script>

<style lang="scss" scoped>
.message-composer {
	margin: 0;
	z-index: 100;
	display: flex;
	flex-direction: column;
	height: 100%;
	max-height: 100%;
}

.composer-actions {
	position: sticky;
	background: linear-gradient(rgba(255, 255, 255, 0), var(--color-main-background-translucent) 50%);
}

.composer-fields {
	display: flex;
	border-top: 1px solid var(--color-border);
	align-items: flex-start;

	label {
		padding: 11px 20px 11px 0;
	}

	:deep(.multiselect--multiple .multiselect__tags) {
		display: grid;
		grid-template-columns: calc(100% - 18px) 18px 100%;

		.multiselect__limit {
			margin-right: 0;
			margin-left: 8px
		}
	}

	:deep(.multiselect__content-wrapper) {
		border-bottom: 1px solid var(--color-border);
		margin-top: 0;

		& li > span::before {
			display: none
		}
	}

	:deep(.multiselect__input) {
		position: relative !important;
		top: 0;
		grid-column-start: 1;
		grid-column-end: 3;
	}

	:deep(.multiselect--active input:focus-visible) {
		box-shadow: none;
	}

	:deep(.multiselect__tags) {
		box-sizing: border-box;
		height: auto;
	}

	&__from {
		margin-right: 50px; /* for the modal close button */
	}

	.multiselect.multiselect--multiple::after {
		position: absolute;
		right: 0;
		top: auto;
		bottom: 8px
	}

	.multiselect__tag {
		position: relative;
	}

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

	.composer-fields--custom {
		display: flex;
		align-items: flex-start;
		flex-wrap: wrap;
		padding-top: 2px;
		width: calc(100% - 120px);

		button {
			margin-top: 0;
			margin-bottom: 0;
			background-color: transparent;
			border: none;
			opacity: 0.5;
			padding: 10px 16px;
		}

		button.active, button:active {
			opacity: 1;
		}

		.multiselect {
			width: calc(100% - 150px);
		}
	}

	.multiselect {
		margin-right: 12px;
	}

	.subject {
		font-size: 15px;
		font-weight: bold;
		margin: 3px 0 !important;
		padding: 0 12px !important;

		&:focus-visible {
			box-shadow: none !important;
		}
	}

	.message-body {
		height: 100%;
		width: 100%;
		margin: 0;
		border: none !important;
		outline: none !important;
		box-shadow: none !important;
		padding: 12px;

		// Fix contenteditable not becoming focused upon clichint within it's
		// boundaries in safari
		-webkit-user-select: text;
		user-select: text;
	}
}

.warning-box {
	padding: 5px 12px;
	border-radius: 0;
}

// Make composer editor expand
.message-editor {
	flex: 1 1 100%;
	min-height: 0;
}

.draft-status {
	padding: 2px;
	opacity: 0.5;
	font-size: small;
	display: block;

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
	font-weight: bold;
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

:deep(.multiselect .multiselect__tags), .subject {
	border: none !important;
}
:deep([data-select="create"] .avatardiv--unknown) {
	background: var(--color-text-maxcontrast) !important;
}
:deep(.multiselect.opened .multiselect__tags .multiselect__tags-wrap) {
	flex-wrap: wrap;
}

.submit-message.send.primary.icon-confirm-white {
	color: var(--color-main-background);
}
.sending-hint {
	height: 50px;
	margin-top: 50px;
}
.button {
	background-color: transparent;
	border: none;
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
.centered-content {
	margin-top: 0 !important;
}
.composer-actions-right {
	display: flex;
	align-items: center;
	flex-direction: row;
	justify-content: space-between;
	bottom: 5px;
}
.composer-actions--primary-actions {
	display: flex;
	flex-direction: row;
	padding-left: 10px;
	align-items: center;
}
.composer-actions--secondary-actions {
	display: flex;
	flex-direction: row;
	padding: 12px;
}
.composer-actions--primary-actions .button {
	padding: 2px;
}
.composer-actions--secondary-actions .button{
	flex-shrink: 0;
}

.composer-actions-draft-status {
	padding-left: 10px;
}

@media only screen and (max-width: 580px) {
	.composer-actions-right {
		align-items: end;
		flex-direction: column-reverse;
	}
	.composer-actions-draft-status {
		text-align: end;
		padding-right: 15px;
	}
	.composer-actions--primary-actions {
		padding-right: 5px;
	}
}

</style>
