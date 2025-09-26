<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="message-composer">
		<NcReferencePickerModal v-if="isPickerAvailable && isPickerOpen"
			id="reference-picker"
			@submit="onPicked"
			@cancel="closePicker" />
		<TextBlockModal v-if="isTextBlockPickerOpen" @close="isTextBlockPickerOpen = false" @insert="onTextBlockInsert" />
		<div class="composer-fields composer-fields__from mail-account">
			<label class="from-label" for="from">
				{{ t('mail', 'From') }}
			</label>
			<div class="composer-fields--custom">
				<NcSelect id="from"
					:value="selectedAlias"
					:options="aliases"
					label="name"
					:get-option-key="(option)=>option.selectId"
					:searchable="false"
					:placeholder="t('mail', 'Select account')"
					:aria-label-combobox="t('mail', 'Select account')"
					:clear-on-select="false"
					:append-to-body="false"
					:selectable="(option)=>option.selectable"
					@option:selected="onAliasChange">
					<template #option="option">
						{{ formatAliases(option) }}
					</template>

					<template #selected-option="option">
						{{ formatAliases(option) }}
					</template>
				</NcSelect>
			</div>
		</div>
		<div class="composer-fields">
			<div class="composer-fields__label">
				<label class="to-label" for="to">
					{{ t('mail', 'To') }}
				</label>
				<ButtonVue size="small" type="tertiary-no-background" @click.prevent="toggleViewMode">
					{{ t('mail','Cc/Bcc') }}
				</ButtonVue>
			</div>
			<div class="composer-fields--custom">
				<NcSelect id="to"
					ref="toLabel"
					:value="selectTo"
					:options="selectableRecipients.filter(recipient=>!selectTo.some(to=>to.email===recipient.email))"
					:get-option-key="(option) => option.email"
					:taggable="true"
					:aria-label-combobox="t('mail', 'Select recipient')"
					:filter-by="(option, label, search)=>filterOption(option, label, search,'to')"
					:multiple="true"
					:close-on-select="true"
					:clear-search-on-select="true"
					:loading="loadingIndicatorTo"
					:reducible="true"
					:clearable="true"
					:no-wrap="false"
					:append-to-body="false"
					:create-option="createRecipientOption"
					:clear-search-on-blur="() => clearOnBlur('to')"
					@input="saveDraftDebounced"
					@option:selecting="onNewToAddr"
					@search:blur="onNewToAddr"
					@search="onAutocomplete($event, 'to')">
					<template #search="{ events, attributes }">
						<input :placeholder="t('mail', 'Contact or email address …')"
							type="search"
							class="vs__search"
							v-bind="attributes"
							v-on="events">
					</template>
					<template #selected-option-container="{option}">
						<RecipientListItem :option="option"
							class="vs__selected selected"
							@remove-recipient="onRemoveRecipient(option, 'to')" />
					</template>
					<template #option="option">
						<div>
							<ListItemIcon :no-margin="true"
								:name="option.label"
								:subname="getSubnameForRecipient(option)"
								:icon-class="!option.id ? 'icon-user' : null"
								:url="option.photo" />
						</div>
					</template>
				</NcSelect>
			</div>
		</div>
		<div v-if="showCC" class="composer-fields">
			<label for="cc" class="cc-label">
				{{ t('mail', 'Cc') }}
			</label>
			<div class="composer-fields--custom">
				<NcSelect id="cc"
					ref="toLabel"
					:value="selectCc"
					:class="{'opened': !autoLimit,'select':true}"
					:options="selectableRecipients.filter(recipient=>!selectCc.some(cc=>cc.email===recipient.email))"
					:get-option-key="(option) => option.email"
					:no-wrap="false"
					:filter-by="(option, label, search)=>filterOption(option, label, search,'cc')"
					:taggable="true"
					:close-on-select="true"
					:clear-search-on-blur="() => clearOnBlur('cc')"
					:append-to-body="false"
					:multiple="true"
					:placeholder="t('mail', 'Contact or email address …')"
					:aria-label-combobox="t('mail', 'Contact or email address …')"
					:clear-search-on-select="true"
					:loading="loadingIndicatorCc"
					:reducible="true"
					:clearable="true"
					:create-option="createRecipientOption"
					@input="saveDraftDebounced"
					@option:selecting="onNewCcAddr"
					@search:blur="onNewCcAddr"
					@search="onAutocomplete($event, 'cc')">
					<template #search="{ events, attributes }">
						<input :placeholder="t('mail', 'Contact or email address …')"
							type="search"
							class="vs__search"
							v-bind="attributes"
							v-on="events">
					</template>
					<template #selected-option-container="{option}">
						<RecipientListItem :option="option"
							class="vs__selected"
							@remove-recipient="onRemoveRecipient(option, 'cc')" />
					</template>
					<template #option="option">
						<div>
							<ListItemIcon :no-margin="true"
								:name="option.label"
								:subname="getSubnameForRecipient(option)"
								:url="option.photo"
								:icon-class="!option.id ? 'icon-user' : null" />
						</div>
					</template>
				</NcSelect>
			</div>
		</div>
		<div v-if="showBCC" class="composer-fields">
			<label for="bcc" class="bcc-label">
				{{ t('mail', 'Bcc') }}
			</label>
			<div class="composer-fields--custom">
				<NcSelect id="bcc"
					ref="toLabel"
					:value="selectBcc"
					:class="{'opened': !autoLimit,'select':true}"
					:no-wrap="false"
					:filter-by="(option, label, search)=>filterOption(option, label, search,'bcc')"
					:options="selectableRecipients.filter(recipient=>!selectBcc.some(bcc=>bcc.email===recipient.email))"
					:get-option-key="(option) => option.email"
					:taggable="true"
					:close-on-select="true"
					:clear-search-on-blur="() => clearOnBlur('bcc')"
					:append-to-body="false"
					:multiple="true"
					:placeholder="t('mail', 'Contact or email address …')"
					:aria-label-combobox="t('mail', 'Contact or email address …')"
					:clear-search-on-select="true"
					:reset-on-options-change="true"
					:loading="loadingIndicatorBcc"
					:clearable="true"
					:create-option="createRecipientOption"
					@input="saveDraftDebounced"
					@option:selecting="onNewBccAddr"
					@search:blur="onNewBccAddr"
					@search="onAutocomplete($event, 'bcc')">
					<template #search="{ events, attributes }">
						<input :placeholder="t('mail', 'Contact or email address …')"
							type="search"
							class="vs__search"
							v-bind="attributes"
							dir="auto"
							v-on="events">
					</template>
					<template #selected-option-container="{option}">
						<RecipientListItem :option="option"
							class="vs__selected"
							@remove-recipient="onRemoveRecipient(option, 'bcc')" />
					</template>
					<template #option="option">
						<div>
							<ListItemIcon :no-margin="true"
								:name="option.label"
								:subname="getSubnameForRecipient(option)"
								:url="option.photo"
								:icon-class="!option.id ? 'icon-user' : null" />
						</div>
					</template>
				</NcSelect>
			</div>
		</div>
		<div class="composer-fields">
			<label for="subject" class="subject-label hidden-visually">
				{{ t('mail', 'Subject') }}
			</label>
			<input id="subject"
				v-model="subjectVal"
				type="text"
				name="subject"
				class="subject"
				autocomplete="off"
				:placeholder="t('mail', 'Subject …')"
				@input="saveDraftDebounced">
		</div>
		<div v-if="noReply" class="warning noreply-warning">
			{{ t('mail', 'This message came from a noreply address so your reply will probably not be read.') }}
		</div>
		<div v-if="wantsSmimeEncrypt && missingSmimeCertificatesForRecipients.length" class="warning noreply-warning">
			{{
				t('mail', 'The following recipients do not have a S/MIME certificate: {recipients}.', {
					recipients: missingSmimeCertificatesForRecipients.join(', '),
				})
			}}
		</div>
		<div v-if="encrypt && mailvelope.keysMissing.length" class="warning noreply-warning">
			{{
				t('mail', 'The following recipients do not have a PGP key: {recipients}.', {
					recipients: mailvelope.keysMissing.join(', '),
				})
			}}
		</div>
		<div class="composer-fields message-editor">
			<!--@keypress="onBodyKeyPress"-->
			<TextEditor v-if="!encrypt"
				ref="editor"
				:key="editorMode"
				:value="bodyVal"
				:html="!editorPlainText"
				name="body"
				class="message-body"
				:placeholder="t('mail', 'Write message …')"
				:focus="isReply || !isFirstOpen"
				:bus="bus"
				:text-blocks="textBlocks"
				@input="onEditorInput"
				@ready="onEditorReady"
				@mention="handleMention"
				@show-toolbar="handleShow" />
			<MailvelopeEditor v-else
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
			@upload="$emit('upload-attachment', $event, getMessageData())" />
		<div class="composer-actions-right composer-actions">
			<div class="composer-actions--primary-actions">
				<p class="composer-actions-draft-status">
					<span v-if="savingDraft" class="draft-status">{{ t('mail', 'Saving draft …') }}</span>
					<span v-else-if="!canSaveDraft" class="draft-status">{{ t('mail', 'Error saving draft') }}</span>
					<span v-else-if="draftSaved" class="draft-status">{{ t('mail', 'Draft saved') }}</span>
				</p>
				<ButtonVue v-if="!savingDraft && !canSaveDraft"
					class="button"
					type="tertiary"
					:aria-label="t('mail', 'Save draft')"
					@click="saveDraft">
					<template #icon>
						<Download :size="20" :title="t('mail', 'Save draft')" />
					</template>
				</ButtonVue>
				<ButtonVue v-if="!savingDraft && draftSaved"
					class="button"
					type="tertiary"
					:aria-label="t('mail', 'Discard & close draft')"
					@click="$emit('discard-draft')">
					<template #icon>
						<Delete :size="20" :title="t('mail', 'Discard & close draft')" />
					</template>
				</ButtonVue>
			</div>
			<div class="composer-actions--secondary-actions">
				<ButtonVue v-if="!encrypt && editorPlainText"
					type="tertiary"
					:aria-label="t('mail', 'Enable formatting')"
					@click="setEditorModeHtml()">
					<template #icon>
						<IconFormat :size="20" :title="t('mail', 'Enable formatting')" />
					</template>
				</ButtonVue>
				<ButtonVue v-if="!encrypt && !editorPlainText"
					type="tertiary"
					:pressed="true"
					:aria-label="t('mail', 'Disable formatting')"
					@click="setEditorModeText()">
					<template #icon>
						<IconFormat :size="20" :title="t('mail', 'Disable formatting')" />
					</template>
				</ButtonVue>

				<Actions :open.sync="isAddAttachmentsOpen">
					<template #icon>
						<Paperclip :size="20" />
					</template>
					<ActionButton :close-after-click="true" @click="onAddLocalAttachment">
						<template #icon>
							<IconUpload :size="20" />
						</template>
						{{
							t('mail', 'Upload attachment')
						}}
					</ActionButton>
					<ActionButton :close-after-click="true" @click="onAddCloudAttachment">
						<template #icon>
							<IconFolder :size="20" />
						</template>
						{{
							t('mail', 'Add attachment from Files')
						}}
					</ActionButton>
					<ActionButton :close-after-click="true" :disabled="encrypt" @click="onAddCloudAttachmentLink">
						<template #icon>
							<IconPublic :size="20" />
						</template>
						{{
							t('mail', 'Add share link from Files')
						}}
					</ActionButton>
				</Actions>

				<Actions :open.sync="isActionsOpen"
					@close="isMoreActionsOpen = false">
					<template v-if="!isMoreActionsOpen">
						<ActionButton v-if="isPickerAvailable" :close-after-click="true" @click="openPicker">
							<template #icon>
								<IconLinkPicker :size="20" />
							</template>
							{{
								t('mail', 'Smart picker')
							}}
						</ActionButton>
						<ActionButton :close-after-click="true" @click="openTextBlockPicker">
							<template #icon>
								<NcIconSvgWrapper :size="20"
									:title="t('mail', 'Text blocks')"
									:svg="textBlockSvg" />
							</template>
							{{
								t('mail', 'Text blocks')
							}}
						</ActionButton>
						<ActionButton v-if="!isScheduledSendingDisabled"
							:close-after-click="false"
							@click="isMoreActionsOpen=true">
							<template #icon>
								<SendClock :size="20" :title="t('mail', 'Send later')" />
							</template>
							{{
								t('mail', 'Send later')
							}}
						</ActionButton>
						<ActionCheckbox :checked="requestMdnVal"
							@check="requestMdnVal = true"
							@uncheck="requestMdnVal = false">
							{{ t('mail', 'Request a read receipt') }}
						</ActionCheckbox>
						<ActionCheckbox v-if="smimeCertificateForCurrentAlias"
							:checked="wantsSmimeSign"
							@check="smimeSignCheck(true)"
							@uncheck="smimeSignCheck(false)">
							{{ t('mail', 'Sign message with S/MIME') }}
						</ActionCheckbox>
						<ActionCheckbox v-if="smimeCertificateForCurrentAlias"
							:checked="wantsSmimeEncrypt"
							:disabled="encrypt"
							@check="wantsSmimeEncrypt = true"
							@uncheck="wantsSmimeEncrypt = false">
							{{ t('mail', 'Encrypt message with S/MIME') }}
						</ActionCheckbox>
						<ActionCheckbox v-if="mailvelope.available"
							:checked="encrypt"
							:disabled="wantsSmimeEncrypt"
							@change="isActionsOpen = false"
							@check="encrypt = true"
							@uncheck="encrypt = false">
							{{ t('mail', 'Encrypt message with Mailvelope') }}
						</ActionCheckbox>
					</template>
					<template v-if="isMoreActionsOpen">
						<ActionButton :close-after-click="false"
							@click="isMoreActionsOpen=false">
							<template #icon>
								<ChevronLeft :title="t('mail', 'Send later')"
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
							:is-native-picker="true"
							:min="dateToday"
							type="datetime-local"
							:first-day-of-week="firstDayDatetimePicker"
							:use12h="showAmPm"
							:formatter="formatter"
							:format="'YYYY-MM-DD HH:mm'"
							icon=""
							:minute-step="5"
							@change="onChangeSendLater(customSendTime)">
							{{ t('mail', 'Enter a date') }}
						</ActionInput>
					</template>
				</Actions>

				<ButtonVue :disabled="!canSend || sending"
					native-type="submit"
					type="primary"
					:aria-label="submitButtonTitle"
					@click="onSend">
					<template #icon>
						<Send :title="submitButtonTitle"
							:size="20" />
					</template>
					{{ submitButtonTitle }}
				</ButtonVue>
			</div>
		</div>
	</div>
</template>

<script>
import debounce from 'lodash/fp/debounce.js'
import uniqBy from 'lodash/fp/uniqBy.js'
import trimStart from 'lodash/fp/trimCharsStart.js'
import Autosize from 'vue-autosize'
import debouncePromise from 'debounce-promise'

import { NcActions as Actions, NcActionButton as ActionButton, NcActionCheckbox as ActionCheckbox, NcActionInput as ActionInput, NcActionRadio as ActionRadio, NcButton as ButtonVue, NcSelect, NcListItemIcon as ListItemIcon, NcIconSvgWrapper } from '@nextcloud/vue'
import ChevronLeft from 'vue-material-design-icons/ChevronLeft.vue'
import Delete from 'vue-material-design-icons/TrashCanOutline.vue'
import ComposerAttachments from './ComposerAttachments.vue'
import TextBlockModal from './textBlocks/TextBlockModal.vue'
import Download from 'vue-material-design-icons/TrayArrowDown.vue'
import IconUpload from 'vue-material-design-icons/TrayArrowUp.vue'
import IconFolder from 'vue-material-design-icons/FolderOutline.vue'
import IconPublic from 'vue-material-design-icons/Link.vue'
import IconLinkPicker from 'vue-material-design-icons/ShapeOutline.vue'
import RecipientListItem from './RecipientListItem.vue'
import Paperclip from 'vue-material-design-icons/Paperclip.vue'
import IconFormat from 'vue-material-design-icons/FormatSize.vue'
import { showError, showWarning } from '@nextcloud/dialogs'
import { getCanonicalLocale, getFirstDay, getLocale, translate as t } from '@nextcloud/l10n'
import Vue from 'vue'
import mitt from 'mitt'
import textBlockSvg from './../../img/text_snippet.svg'

import { findRecipient } from '../service/AutocompleteService.js'
import { detect, html, toHtml, toPlain } from '../util/text.js'
import logger from '../logger.js'
import TextEditor from './TextEditor.vue'
import { buildReplyBody } from '../ReplyBuilder.js'
import MailvelopeEditor from './MailvelopeEditor.vue'
import { getMailvelope } from '../crypto/mailvelope.js'
import { isPgpgMessage } from '../crypto/pgp.js'

import { NcReferencePickerModal } from '@nextcloud/vue/components/NcRichText'

import Send from 'vue-material-design-icons/SendOutline.vue'
import SendClock from 'vue-material-design-icons/SendClockOutline.vue'
import moment from '@nextcloud/moment'
import { TRIGGER_CHANGE_ALIAS, TRIGGER_EDITOR_READY } from '../ckeditor/signature/InsertSignatureCommand.js'
import { EDITOR_MODE_HTML, EDITOR_MODE_TEXT } from '../store/constants.js'
import useMainStore from '../store/mainStore.js'
import { mapStores, mapState } from 'pinia'
import { savePreference } from '../service/PreferenceService.js'
import addressParser from 'address-rfc2822'

const debouncedSearch = debouncePromise(findRecipient, 500)

const NO_ALIAS_SET = -1

Vue.use(Autosize)

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
		TextBlockModal,
		ChevronLeft,
		Delete,
		Download,
		IconUpload,
		IconFolder,
		IconPublic,
		IconLinkPicker,
		NcSelect,
		NcIconSvgWrapper,
		Paperclip,
		TextEditor,
		ListItemIcon,
		RecipientListItem,
		Send,
		SendClock,
		IconFormat,
		NcReferencePickerModal,
	},
	props: {
		fromAccount: {
			type: Number,
			default: () => undefined,
		},
		fromAlias: {
			type: Number,
			default: undefined,
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
		smartReply: {
			type: String,
			required: false,
			default: undefined,
		},
		sendAt: {
			type: Number,
			default: undefined,
		},
		attachmentsData: {
			type: Array,
			default: () => [],
		},
		error: {
			type: String,
			default: undefined,
		},
		canSaveDraft: {
			type: Boolean,
			default: false,
		},
		uploadingAttachments: {
			type: Boolean,
			default: false,
		},
		savingDraft: {
			type: Boolean,
			default: false,
		},
		draftSaved: {
			type: Boolean,
			default: false,
		},
		smimeSign: {
			type: Boolean,
			default: false,
		},
		smimeEncrypt: {
			type: Boolean,
			default: false,
		},
		isFirstOpen: {
			type: Boolean,
			required: true,
		},
		requestMdn: {
			type: Boolean,
			default: false,
		},
		accounts: {
			type: Array,
			required: true,
		},
	},
	data() {
		// Set default custom date time picker value to now + 1 hour
		const selectedDate = new Date()
		selectedDate.setHours(selectedDate.getHours() + 1)

		return {
			sending: false,
			textBlockSvg,
			showCC: this.cc.length > 0,
			showBCC: this.bcc.length > 0,
			selectedAlias: NO_ALIAS_SET, // Fixed in `beforeMount`
			autocompleteRecipients: this.to.concat(this.cc).concat(this.bcc),
			newRecipients: [],
			subjectVal: this.subject,
			bodyVal: this.editorBody,
			attachments: this.attachmentsData,
			noReply: this.to.some((to) => to.email?.startsWith('noreply@') || to.email?.startsWith('no-reply@')),
			saveDraftDebounced: debounce(5 * 1000, this.saveDraft),
			selectTo: this.to,
			selectCc: this.cc,
			selectBcc: this.bcc,
			bus: mitt(),
			encrypt: false,
			mailvelope: {
				available: false,
				keyRing: undefined,
				keysMissing: [],
			},
			editorMode: (this.body?.format !== 'html') ? EDITOR_MODE_TEXT : EDITOR_MODE_HTML,
			requestMdnVal: this.requestMdn,
			changeSignature: false,
			loadingIndicatorTo: false,
			loadingIndicatorCc: false,
			loadingIndicatorBcc: false,
			isAddAttachmentsOpen: false,
			isActionsOpen: false,
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
			wantsSmimeSign: this.smimeSign,
			wantsSmimeEncrypt: this.smimeEncrypt,
			isPickerOpen: false,
			isTextBlockPickerOpen: false,
			recipientSearchTerms: {},
			smimeSignAliases: [],
		}
	},
	computed: {
		...mapStores(useMainStore),
		...mapState(useMainStore, ['isScheduledSendingDisabled']),
		isPickerAvailable() {
			return parseInt(this.mainStore.getNcVersion) >= 26
		},
		aliases() {
			let cnt = 0
			const accounts = this.accounts.filter((a) => !a.isUnified)
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
					smimeCertificateId: account.smimeCertificateId,
					selectable: account.connectionStatus,
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
						smimeCertificateId: alias.smimeCertificateId,
						selectable: account.connectionStatus,
					}
				}),
			])
			return aliases.flat()
		},
		allRecipients() {
			return this.selectTo.concat(this.selectCc).concat(this.selectBcc)
		},
		dateToday() {
			return new Date(new Date().setDate(new Date().getDate()))
		},
		attachmentSizeLimit() {
			return this.mainStore.getPreference('attachment-size-limit')
		},
		selectableRecipients() {
			return uniqBy('email')(this.newRecipients
				.concat(this.autocompleteRecipients)
				.map((recipient) => ({ ...recipient, label: recipient.label || recipient.email })))
		},
		isForward() {
			return this.forwardFrom !== undefined
		},
		isReply() {
			return this.replyTo !== undefined
		},
		canSend() {
			if (this.wantsSmimeEncrypt && (!this.smimeCertificateForCurrentAlias || this.missingSmimeCertificatesForRecipients.length)) {
				return false
			}

			if (this.encrypt && this.mailvelope.keysMissing.length) {
				return false
			}

			return this.selectTo.length > 0 || this.selectCc.length > 0 || this.selectBcc.length > 0
		},
		editorPlainText() {
			return this.editorMode === EDITOR_MODE_TEXT
		},
		submitButtonTitle() {
			if (this.wantsSmimeEncrypt) {
				if (this.sendAtVal) {
					return t('mail', 'Encrypt with S/MIME and send later') + ` ${this.convertToLocalDate(this.sendAtVal)}`
				}
				return t('mail', 'Encrypt with S/MIME and send')
			}

			if (this.mailvelope.available && this.encrypt) {
				if (this.sendAtVal) {
					return t('mail', 'Encrypt with Mailvelope and send later') + ` ${this.convertToLocalDate(this.sendAtVal)}`
				}
				return t('mail', 'Encrypt with Mailvelope and send')
			}

			if (this.sendAtVal) {
				return t('mail', 'Send later') + ` ${this.convertToLocalDate(this.sendAtVal)}`
			}
			return t('mail', 'Send')
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

		/**
		 * The S/MIME certificate object of the current alias/account.
		 *
		 * @return {object|undefined} S/MIME certificate of current account or alias if one is selected
		 */
		smimeCertificateForCurrentAlias() {
			if (this.selectedAlias === NO_ALIAS_SET) {
				return undefined
			}

			return this.smimeCertificateForAlias(this.selectedAlias)
		},

		/**
		 * Whether the outgoing message should be signed with S/MIME.
		 *
		 * @return {boolean} True if the message should be signed
		 */
		shouldSmimeSign() {
			return this.wantsSmimeSign && !!this.smimeCertificateForCurrentAlias
		},

		/**
		 * Whether the outgoing message should be encrypted with S/MIME.
		 *
		 * @return {boolean} True if the message should be encrypted
		 */
		shouldSmimeEncrypt() {
			return this.wantsSmimeEncrypt && !!this.smimeCertificateForCurrentAlias && this.missingSmimeCertificatesForRecipients.length === 0
		},

		/**
		 * Return a list of recipients without a matching S/MIME certificate.
		 *
		 * @return {Array} Recipients without matching certificate
		 */
		missingSmimeCertificatesForRecipients() {
			const missingCertificates = []

			this.allRecipients.forEach((recipient) => {
				const recipientCertificate = this.mainStore.getSmimeCertificateByEmail(recipient.email)
				if (!recipientCertificate) {
					missingCertificates.push(recipient.email)
				}
			})

			return missingCertificates
		},

		textBlocks() {
			return this.mainStore.getSharedTextBlocks()?.map(textBlock => ({ title: textBlock.title, content: textBlock.content }))
				.concat(this.mainStore.getMyTextBlocks().map(textBlock => ({ title: textBlock.title, content: textBlock.content })))
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
		selectTo(val) {
			this.$emit('update:to', val)
		},
		selectCc(val) {
			this.$emit('update:cc', val)
		},
		selectBcc(val) {
			this.$emit('update:bcc', val)
		},
		subjectVal(val) {
			this.$emit('update:subject', val)
		},
		bodyVal(val) {
			this.$emit('update:editor-body', val)
		},
		attachments(val) {
			this.$emit('update:attachments-data', val)
		},
		sendAtVal(val) {
			this.$emit('update:send-at', val)
		},
		wantsSmimeSign(val) {
			this.$emit('update:smime-sign', val)
		},
		wantsSmimeEncrypt(val) {
			this.$emit('update:smime-encrypt', val)
		},
		requestMdnVal(val) {
			this.$emit('update:request-mdn', val)
		},
		selectedAlias: {
			handler() {
				const aliasEmailAddress = this.selectedAlias.emailAddress
				this.wantsSmimeSign = this.smimeSignAliases.indexOf(aliasEmailAddress) !== -1
			},
			immediate: true,
		},
	},
	async beforeMount() {
		this.setAlias()
		this.initBody()
		await this.onMailvelopeLoaded(await getMailvelope())
	},
	mounted() {
		if (!this.isReply && this.isFirstOpen) {
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
		for (const id of this.forwardedMessages) {
			const env = this.mainStore.getEnvelope(id)
			if (!env) {
				// TODO: also happens when the composer page is reloaded
				showError(t('mail', 'Message {id} could not be found', {
					id,
				}))
				continue
			}

			this.bus.emit('on-add-message-as-attachment', {
				id,
				fileName: env.subject + '.eml',
			})
		}

		// Set custom date and time picker value if initialized with custom send at value
		if (this.sendAt && this.isSendAtCustom) {
			this.selectedDate = new Date(this.sendAt)
		}

		this.smimeSignAliases = this.mainStore.getPreference('smime-sign-aliases', [])
		if (!this.mainStore.areTextBlocksFetched) {
			this.mainStore.fetchSharedTextBlocks()
			this.mainStore.fetchMyTextBlocks()
		}
	},
	beforeDestroy() {
		window.removeEventListener('mailvelope', this.onMailvelopeLoaded)
	},
	methods: {
		/**
		 * Called once a user leaves the recipient picker.
		 *
		 * If the user is typing something that looks like a valid email address, we clear the input (return true)
		 * because the related code in onNewAddr will add the value as a recipient.
		 *
		 * Otherwise, the user is still typing and we don't clear the input.
		 *
		 * @param {string} event usually to, cc or bcc
		 * @return {boolean}
		 */
		clearOnBlur(event) {
			if (this.recipientSearchTerms[event]) {
				return this.seemsValidEmailAddress(this.recipientSearchTerms[event])
			}
			return false
		},
		handleShow(event) {
			this.$emit('show-toolbar', event)
		},
		openPicker() {
			this.isPickerOpen = true
		},
		openTextBlockPicker() {
			this.isTextBlockPickerOpen = true
		},
		closePicker() {
			this.isPickerOpen = false
		},
		filterOption(option, label, search, list) {
			let select = []
			if (list === 'to') {
				select = this.selectTo
			} else if (list === 'cc') {
				select = this.selectCc

			} else if (list === 'bcc') {
				select = this.selectBcc
			}

			if (select.some((item) => item.email === option.email)) {
				return false // skip option if already selected
			}

			const searchInLowerCase = search.toLocaleLowerCase()

			return (label || '').toLocaleLowerCase().includes(searchInLowerCase)
				|| (option?.email || '').toLocaleLowerCase().includes(searchInLowerCase)
		},
		setAlias() {
			const previous = this.selectedAlias
			if (this.fromAccount && this.fromAlias) {
				this.selectedAlias = this.aliases.find((alias) => {
					return alias.id === this.fromAccount && alias.aliasId === this.fromAlias
				})
			} else if (this.fromAccount) {
				// Default alias of account: aliasId === null
				this.selectedAlias = this.aliases.find((alias) => {
					return alias.id === this.fromAccount && !alias.aliasId
				})
			} else {
				const currentAccountId = this.mainStore.getMailbox(this.$route.params.mailboxId)?.accountId
				if (currentAccountId) {
					this.selectedAlias = this.aliases.find((alias) => {
						return alias.id === currentAccountId
					})
				} else {
					this.selectedAlias = this.aliases[0]
				}
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
			if (this.replyTo && this.isFirstOpen) {
				body = buildReplyBody(
					this.editorPlainText ? toPlain(this.body) : toHtml(this.body),
					this.replyTo.from[0],
					this.replyTo.dateInt,
					this.mainStore.getPreference('reply-mode', 'top') === 'top',
				).value
			} else if (this.forwardFrom && this.isFirstOpen) {
				body = buildReplyBody(
					this.editorPlainText ? toPlain(this.body) : toHtml(this.body),
					this.forwardFrom.from[0],
					this.forwardFrom.dateInt,
					this.mainStore.getPreference('reply-mode', 'top') === 'top',
				).value
			} else {
				body = this.bodyVal
			}
			this.bodyVal = html(body).value
		},
		getMessageData() {
			const data = {
				// TODO: Rename account to accountId
				account: this.selectedAlias.id,
				accountId: this.selectedAlias.id,
				aliasId: this.selectedAlias.aliasId,
				to: this.selectTo,
				cc: this.selectCc,
				bcc: this.selectBcc,
				subject: this.subjectVal,
				attachments: this.attachments,
				inReplyToMessageId: this.inReplyToMessageId ?? (this.replyTo ? this.replyTo.messageId : undefined),
				isHtml: !this.encrypt && !this.editorPlainText,
				requestMdn: this.requestMdnVal,
				sendAt: this.sendAtVal ? Math.floor(this.sendAtVal / 1000) : undefined,
				smimeSign: this.shouldSmimeSign,
				smimeEncrypt: this.shouldSmimeEncrypt,
				smimeCertificateId: this.smimeCertificateForCurrentAlias?.id,
				isPgpMime: this.encrypt,
			}

			if (data.isHtml) {
				data.bodyHtml = this.bodyVal
			} else {
				data.bodyPlain = toPlain(html(this.bodyVal)).value
			}

			return data
		},
		saveDraft() {
			const draftData = this.getMessageData()
			if (draftData.subject === ''
				&& draftData.body?.value === ''
				&& draftData.cc.length === 0
				&& draftData.bcc.length === 0
				&& draftData.to.length === 0
				&& draftData.sendAt === undefined) {
				// this might happen after a call to reset()
				// where the text input gets reset as well
				// and fires an input event
				logger.debug('Nothing substantial to save, ignoring draft save')
				return
			}

			this.$emit('draft', draftData)
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
				this.selectedAlias.signatureAboveQuote,
			)

			this.changeSignature = false
		},
		onPicked(content) {
			this.closePicker()
			this.bus.emit('append-to-body-at-cursor', content)
		},
		onTextBlockInsert(content) {
			this.isTextBlockPickerOpen = false
			this.bus.emit('insert-text-block', content)
		},
		onEditorInput(text) {
			this.bodyVal = text
			this.saveDraftDebounced()
		},
		onEditorReady(editor) {
			this.bodyVal = editor.getData()
			this.insertSignature()
			if (this.smartReply) {
				this.bus.emit('append-to-body-at-cursor', this.smartReply)
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
			this.changeSignature = true

			this.$emit('update:from-account', alias.id)
			if (alias.aliasId) {
				this.$emit('update:from-alias', alias.aliasId)
			}

			if (this.wantsSmimeSign || this.wantsSmimeEncrypt) {
				if (!this.smimeCertificateForAlias(alias)) {
					this.wantsSmimeSign = false
					this.wantsSmimeEncrypt = false
					showWarning(t('mail', 'Sign or Encrypt with S/MIME was selected, but we don\'t have a certificate for the selected alias. The message will not be signed or encrypted.'))
				}
			}

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
			this.bus.emit('on-add-local-attachment')
			this.saveDraftDebounced()
		},
		onAddCloudAttachment() {
			this.bus.emit('on-add-cloud-attachment')
			this.saveDraftDebounced()
		},
		onAddCloudAttachmentLink() {
			this.bus.emit('on-add-cloud-attachment-link')
		},
		onAutocomplete(term, addressType) {
			if (term === undefined || term === '') {
				return
			}
			this.loadingIndicatorTo = addressType === 'to'
			this.loadingIndicatorCc = addressType === 'cc'
			this.loadingIndicatorBcc = addressType === 'bcc'
			this.recipientSearchTerms[addressType] = term

			// Autocomplete from own identifies (useful for testing)
			const accounts = this.accounts.filter((a) => !a.isUnified)
			const selfRecipients = accounts
				.filter(
					account => account.emailAddress.toLowerCase().indexOf(term.toLowerCase()) !== -1
					|| account.name.toLowerCase().indexOf(term.toLowerCase()) !== -1,
				)
				.map(account => ({
					email: account.emailAddress,
					label: account.name,
				}))
			this.autocompleteRecipients = uniqBy('email')(this.autocompleteRecipients.concat(selfRecipients))

			debouncedSearch(term).then((results) => {
				if (addressType === 'to') {
					this.loadingIndicatorTo = false
				} else if (addressType === 'cc') {
					this.loadingIndicatorCc = false
				} else if (addressType === 'bcc') {
					this.loadingIndicatorBcc = false
				}

				// Search results might not have labels
				for (const result of results) {
					if (!result.label) {
						result.label = result.email
					}
				}

				this.autocompleteRecipients = uniqBy('email')(this.autocompleteRecipients.concat(results))
			})
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
		handleMention(option) {
			this.editorMode = EDITOR_MODE_HTML
			this.onNewToAddr(option)
		},
		onNewToAddr(option) {
			this.onNewAddr(option, this.selectTo, 'to')
		},
		onNewCcAddr(option) {
			this.onNewAddr(option, this.selectCc, 'cc')
		},
		onNewBccAddr(option) {
			this.onNewAddr(option, this.selectBcc, 'bcc')
		},
		onNewAddr(option, list, type) {
			if (
				(option === null || option === undefined)
				&& this.recipientSearchTerms[type] !== undefined
				&& this.recipientSearchTerms[type] !== ''
			) {
				if (!this.seemsValidEmailAddress(this.recipientSearchTerms[type])) {
					return
				}
				option = {}
				option.email = this.recipientSearchTerms[type]
				option.label = this.recipientSearchTerms[type]
				this.recipientSearchTerms[type] = ''
			}

			if (list.some((recipient) => recipient.email === option?.email) || !option) {
				return
			}
			const recipient = { ...option }
			this.newRecipients.push(recipient)
			list.push(recipient)
			this.saveDraftDebounced()
		},
		async onSend(_, force = false) {
			if (this.encrypt) {
				logger.debug('get encrypted message from mailvelope')
				await this.$refs.mailvelopeEditor.pull()
			}

			this.$emit('send', {
				...this.getMessageData(),
				force,
			})
		},
		reset() {
			this.selectTo = []
			this.selectCc = []
			this.selectBcc = []
			this.subjectVal = ''
			this.bodyVal = '<p></p><p></p>'
			this.attachments = []
			this.autocompleteRecipients = []
			this.newRecipients = []
			this.requestMdnVal = false
			this.changeSignature = false
			this.sendAtVal = undefined

			this.setAlias()
			this.initBody()
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
		/**
		 * The S/MIME certificate object for an alias/account.
		 *
		 * @param {object} alias object
		 * @return {object|undefined} S/MIME certificate of account or alias if one is selected
		 */
		smimeCertificateForAlias(alias) {
			const certificateId = alias.smimeCertificateId
			if (!certificateId) {
				return undefined
			}
			return this.mainStore.getSmimeCertificate(certificateId)
		},

		smimeSignCheck(value) {
			this.wantsSmimeSign = value
			if (value) {
				this.smimeSignAliases.push(this.selectedAlias.emailAddress)
			} else {
				this.smimeSignAliases = this.smimeSignAliases
					.filter((alias) => alias !== this.selectedAlias.emailAddress)
			}
			savePreference('smime-sign-aliases', JSON.stringify(this.smimeSignAliases))
		},

		/**
		 * Create a new option for the to, cc and bcc selects.
		 *
		 * @param {string} value The string (email) typed by the user
		 * @return {{email: string, label: string}} The new option
		 */
		createRecipientOption(value) {
			if (!this.seemsValidEmailAddress(value)) {
				throw new Error('Skipping because it does not look like a valid email address')
			}
			return { email: value, label: value }
		},

		/**
		 * Return the subname for recipient suggestion.
		 *
		 * Empty if label and email are the same or
		 * if the suggestion is a group.
		 *
		 * @param {{email: string, label: string}} option object
		 * @return {string}
		 */
		getSubnameForRecipient(option) {
			if (option.source && option.source === 'groups') {
				return ''
			}

			if (option.label === option.email) {
				return ''
			}

			return option.email
		},

		/**
		 * True when value looks like a valid email address
		 *
		 * @param {string} value to check if email address
		 * @return {boolean}
		 */
		seemsValidEmailAddress(value) {
			try {
				addressParser.parse(value)
				return true
			} catch (error) {
				return false
			}
		},
	},
}
</script>

<style lang="scss" scoped>
.message-composer {
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
	padding: var(--default-grid-baseline) calc(var(--default-grid-baseline) * 2) 0 calc(var(--default-grid-baseline) * 2);

	&__label {
		display: flex;
		flex-direction: row;
		justify-content: space-between;
		align-items: flex-end;

		/** NcButton does not allow font weight styling */
		:deep(.button-vue__text) {
			font-weight: normal;
		}
	}

	&.mail-account {
		border-top: none;
		padding-top: calc(var(--default-grid-baseline) * 2);
	}

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
		justify-content: space-between;
		padding-top: calc(var(--default-grid-baseline) * 0.5);

		button {
			margin-top: 0;
			margin-bottom: 0;
			background-color: transparent;
			border: none;
			opacity: 0.5;
			padding: calc(var(--default-grid-baseline) * 2) calc(var(--default-grid-baseline) * 4);
		}

		.select {
			width: 100%;
		}
		.vs__search{
			width: 100%;
		}
		.v-select{
			flex-grow: 0.95;
		}
	}

	.subject {
		font-size: 15px;
		font-weight: bold;
		margin: var(--default-grid-baseline) 0 !important;
		padding: 0 !important;
		width: 100%;

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

		// Fix contenteditable not becoming focused upon clichint within it's
		// boundaries in safari
		-webkit-user-select: text;
		user-select: text;
	}
}

// Make composer editor expand
.message-editor {
	flex: 1 1 100%;
	min-height: 0;
	border-top: 1px solid var(--color-border);
}

.draft-status {
	padding: calc(var(--default-grid-baseline) * 0.5);
	opacity: 0.5;
	font-size: small;
	display: block;
}

.from-label,
.to-label,
.copy-toggle,
.cc-label,
.bcc-label {
	color: var(--color-text-maxcontrast);
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

.subject {
	border: none !important;
}

:deep([data-select="create"] .avatardiv--unknown) {
	background: var(--color-text-maxcontrast) !important;
}

#from{
	width: 100%;
	cursor: pointer;
}

:deep(.vs__actions){
	display: none;
}

:deep(.v-select.select){
	inset-inline-start: 0 !important;
}

:deep(.vs__dropdown-menu){
	padding: 0 !important;
}

:deep(.vs__dropdown-option){
	border-radius: 0  !important;
}

.submit-message.send.primary.icon-confirm-white {
	color: var(--color-main-background);
}

.button {
	background-color: transparent;
	border: none;
}

.send-button {
	display: flex;
	align-items: center;
	padding: calc(var(--default-grid-baseline) * 2) calc(var(--default-grid-baseline) * 4);
	margin-inline-start: var(--default-grid-baseline);
}

.send-button .send-icon {
	padding-inline-end: var(--default-grid-baseline);
}

.centered-content {
	margin-top: 0 !important;
}

.composer-actions-right {
	display: flex;
	align-items: center;
	flex-direction: row;
	justify-content: space-between;
	bottom: var(--default-grid-baseline);
}

.composer-actions--primary-actions {
	display: flex;
	flex-direction: row;
	padding-inline-start: calc(var(--default-grid-baseline) * 2);
	align-items: center;
}

.composer-actions--secondary-actions {
	display: flex;
	flex-direction: row;
	padding: 12px;
	gap: 5px;
}

.composer-actions--primary-actions .button {
	padding: 2px;
}

.composer-actions--secondary-actions .button{
	flex-shrink: 0;
}

.composer-actions-draft-status {
	padding-inline-start: 10px;
}

:deep(.vs__selected-options .vs__dropdown-toggle .vs--multiple ){
	width: 100%;
}

@media only screen and (max-width: 580px) {
	.composer-actions-right {
		align-items: end;
		flex-direction: column-reverse;
	}
	.composer-actions-draft-status {
		text-align: end;
		padding-inline-end: 15px;
	}
	.composer-actions--primary-actions {
		padding-inline-end: 5px;
	}
}

</style>
