<template>
	<div v-if="state === STATES.EDITING" class="message-composer">
		<div class="composer-fields mail-account">
			<label class="from-label" for="from">
				{{ t('mail', 'From') }}
			</label>
			<Multiselect
				id="from"
				v-model="selectedAlias"
				:options="aliases"
				label="name"
				track-by="id"
				:searchable="false"
				:hide-selected="true"
				:custom-label="formatAliases"
				:placeholder="t('mail', 'Select account')"
				:clear-on-select="false"
				@keyup="onInputChanged"
			/>
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
				:multiple="true"
				:placeholder="t('mail', 'Contact or email address …')"
				:clear-on-select="false"
				:show-no-options="false"
				:preserve-search="true"
				@keyup="onInputChanged"
				@tag="onNewToAddr"
				@search-change="onAutocomplete"
			/>
			<a v-if="!showCC" class="copy-toggle" href="#" @click.prevent="showCC = true">
				{{ t('mail', '+ CC/BCC') }}
			</a>
		</div>
		<div v-if="showCC" class="composer-fields">
			<label for="cc" class="cc-label">
				{{ t('mail', 'CC') }}
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
				:clear-on-select="false"
				:show-no-options="false"
				:preserve-search="true"
				@keyup="onInputChanged"
				@tag="onNewCcAddr"
				@search-change="onAutocomplete"
			>
				<span slot="noOptions">{{ t('mail', 'No contacts found.') }}</span>
			</Multiselect>
		</div>
		<div v-if="showCC" class="composer-fields">
			<label for="bcc" class="bcc-label">
				{{ t('mail', 'BCC') }}
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
				@keyup="onInputChanged"
				@tag="onNewBccAddr"
				@search-change="onAutocomplete"
			>
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
				@keyup="onInputChanged"
			/>
		</div>
		<div v-if="noReply" class="warning noreply-box">
			{{ t('mail', 'This message came from a noreply address so your reply will probably not be read.') }}
		</div>
		<div class="composer-fields">
			<!--@keypress="onBodyKeyPress"-->
			<TextEditor
				v-if="editorPlainText"
				key="editor-plain"
				v-model="bodyVal"
				name="body"
				class="message-body"
				:placeholder="t('mail', 'Write message …')"
				:focus="isReply"
				@input="onInputChanged"
			></TextEditor>
			<TextEditor
				v-else
				key="editor-rich"
				v-model="bodyVal"
				:html="true"
				name="body"
				class="message-body"
				:placeholder="t('mail', 'Write message …')"
				:focus="isReply"
				@input="onInputChanged"
			></TextEditor>
		</div>
		<div class="composer-actions">
			<ComposerAttachments v-model="attachments" :bus="bus" @upload="onAttachmentsUploading" />
			<div class="composer-actions-right">
				<p class="composer-actions-draft">
					<span v-if="savingDraft === true" id="draft-status">{{ t('mail', 'Saving draft …') }}</span>
					<span v-else-if="savingDraft === false" id="draft-status">{{ t('mail', 'Draft saved') }}</span>
				</p>
				<Actions>
					<ActionButton icon="icon-upload" @click="onAddLocalAttachment">{{
						t('mail', 'Upload attachment')
					}}</ActionButton>
					<ActionButton icon="icon-folder" @click="onAddCloudAttachment">{{
						t('mail', 'Add attachment from Files')
					}}</ActionButton>
					<ActionCheckbox
						:checked="!editorPlainText"
						:text="t('mail', 'Enable formatting')"
						@check="editorPlainText = false"
						@uncheck="editorPlainText = true"
						>{{ t('mail', 'Enable formatting') }}</ActionCheckbox
					>
				</Actions>
				<div>
					<input
						class="submit-message send primary icon-confirm-white"
						type="submit"
						:value="submitButtonTitle"
						:disabled="!canSend"
						@click="onSend"
					/>
				</div>
			</div>
		</div>
	</div>
	<Loading v-else-if="state === STATES.UPLOADING" :hint="t('mail', 'Uploading attachments …')" />
	<Loading v-else-if="state === STATES.SENDING" :hint="t('mail', 'Sending …')" />
	<div v-else-if="state === STATES.ERROR" class="emptycontent">
		<h2>{{ t('mail', 'Error sending your message') }}</h2>
		<p v-if="errorText">{{ errorText }}</p>
		<button class="button" @click="state = STATES.EDITING">{{ t('mail', 'Go back') }}</button>
		<button class="button primary" @click="onSend">{{ t('mail', 'Retry') }}</button>
	</div>
	<div v-else class="emptycontent">
		<h2>{{ t('mail', 'Message sent!') }}</h2>
		<button v-if="!isReply" class="button primary" @click="reset">
			{{ t('mail', 'Write another message') }}
		</button>
	</div>
</template>

<script>
import debounce from 'lodash/fp/debounce'
import uniqBy from 'lodash/fp/uniqBy'
import Autosize from 'vue-autosize'
import debouncePromise from 'debounce-promise'
import Actions from '@nextcloud/vue/dist/Components/Actions'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import ActionCheckbox from '@nextcloud/vue/dist/Components/ActionCheckbox'
import Multiselect from '@nextcloud/vue/dist/Components/Multiselect'
import {translate as t} from '@nextcloud/l10n'
import Vue from 'vue'

import ComposerAttachments from './ComposerAttachments'
import {findRecipient} from '../service/AutocompleteService'
import {htmlToText, textToSimpleHtml} from '../util/HtmlHelper'
import Loading from './Loading'
import logger from '../logger'
import TextEditor from './TextEditor'

const debouncedSearch = debouncePromise(findRecipient, 500)

Vue.use(Autosize)

const STATES = Object.seal({
	EDITING: 0,
	UPLOADING: 1,
	SENDING: 2,
	ERROR: 3,
	FINISHED: 4,
})

export default {
	name: 'Composer',
	components: {
		Actions,
		ActionButton,
		ActionCheckbox,
		ComposerAttachments,
		Loading,
		Multiselect,
		TextEditor,
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
			type: String,
			default: '',
		},
		draft: {
			type: Function,
			required: true,
		},
		send: {
			type: Function,
			required: true,
		},
		isPlainText: {
			type: Boolean,
			default: true,
		},
	},
	data() {
		return {
			showCC: this.cc.length > 0,
			selectedAlias: -1, // Fixed in `beforeMount`
			autocompleteRecipients: this.to.concat(this.cc).concat(this.bcc),
			newRecipients: [],
			subjectVal: this.subject,
			bodyVal: this.isPlainText ? textToSimpleHtml(this.body) : this.body,
			attachments: [],
			noReply: this.to.some(to => to.email.startsWith('noreply@') || to.email.startsWith('no-reply@')),
			submitButtonTitle: t('mail', 'Send'),
			draftsPromise: Promise.resolve(),
			attachmentsPromise: Promise.resolve(),
			savingDraft: undefined,
			saveDraftDebounced: debounce(700)(this.saveDraft),
			state: STATES.EDITING,
			errorText: undefined,
			STATES,
			selectTo: this.to,
			selectCc: this.cc,
			selectBcc: this.bcc,
			editorPlainText: this.isPlainText,
			bus: new Vue(),
		}
	},
	computed: {
		aliases() {
			return this.$store.getters.accounts.filter(a => !a.isUnified)
		},
		selectableRecipients() {
			return this.newRecipients
				.concat(this.autocompleteRecipients)
				.map(recipient => ({...recipient, label: recipient.label || recipient.email}))
		},
		isReply() {
			return this.to.length > 0
		},
		noSubject() {
			return this.subjectVal === '' && this.bodyVal !== ''
		},
		canSend() {
			return this.selectTo.length > 0 || this.selectCc.length > 0 || this.selectBcc.length > 0
		},
	},
	watch: {
		selectedAlias(val) {
			if (val) {
				// TODO: warn user before formatting is lost?
				this.editorPlainText = val.editorMode === 'plaintext'
			}
		},
	},
	beforeMount() {
		if (this.fromAccount) {
			this.selectedAlias = this.aliases.find(alias => alias.id === this.fromAccount)
		} else {
			this.selectedAlias = this.aliases[0]
		}

		this.bodyVal = this.bodyWithSignature(this.selectedAlias, this.bodyVal)
	},
	mounted() {
		this.$refs.toLabel.$el.focus()
	},
	methods: {
		recipientToRfc822(recipient) {
			if (recipient.email === recipient.label) {
				// From mailto or sender without proper label
				return recipient.email
			} else if (recipient.label === '') {
				// Invalid label
				return recipient.email
			} else if (recipient.email.search(/^[a-zA-Z]+:/) === 0) {
				// Group integration
				return recipient.email
			} else {
				// Proper layout with label
				return `"${recipient.label}" <${recipient.email}>`
			}
		},
		getMessageData(uid) {
			return {
				account: this.selectedAlias.id,
				to: this.selectTo.map(this.recipientToRfc822).join(', '),
				cc: this.selectCc.map(this.recipientToRfc822).join(', '),
				bcc: this.selectBcc.map(this.recipientToRfc822).join(', '),
				draftUID: uid,
				subject: this.subjectVal,
				body: this.editorPlainText ? htmlToText(this.bodyVal) : this.bodyVal,
				attachments: this.attachments,
				folderId: this.replyTo ? this.replyTo.folderId : undefined,
				messageId: this.replyTo ? this.replyTo.messageId : undefined,
				isHtml: !this.editorPlainText,
			}
		},
		saveDraft(data) {
			this.savingDraft = true
			this.draftsPromise = this.draftsPromise
				.then(uid => this.draft(data(uid)))
				.catch(logger.error.bind(logger))
				.then(uid => {
					this.savingDraft = false
					return uid
				})
		},
		onInputChanged() {
			this.saveDraftDebounced(this.getMessageData)
		},
		onAddLocalAttachment() {
			this.bus.$emit('onAddLocalAttachment')
		},
		onAddCloudAttachment() {
			this.bus.$emit('onAddCloudAttachment')
		},
		onAutocomplete(term) {
			if (term === undefined || term === '') {
				return
			}
			debouncedSearch(term).then(results => {
				this.autocompleteRecipients = uniqBy('email')(this.autocompleteRecipients.concat(results))
			})
		},
		onAttachmentsUploading(uploaded) {
			this.attachmentsPromise = this.attachmentsPromise
				.then(() => uploaded)
				.catch(error => logger.error('could not upload attachments', {error}))
				.then(() => logger.debug('attachments uploaded'))
		},
		onBodyKeyPress(event) {
			// CTRL+Enter sends the message
			if (event.keyCode === 13 && event.ctrlKey) {
				return this.onSend()
			}
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
		onSend() {
			this.state = STATES.UPLOADING

			return this.attachmentsPromise
				.then(() => (this.state = STATES.SENDING))
				.then(() => this.draftsPromise)
				.then(this.getMessageData)
				.then(data => this.send(data))
				.then(() => logger.info('message sent'))
				.then(() => (this.state = STATES.FINISHED))
				.catch(error => {
					logger.error('could not send message', {error})
					if (error && error.toString) {
						this.errorText = error.toString()
					}
					this.state = STATES.ERROR
				})
		},
		reset() {
			this.selectTo = []
			this.selectCc = []
			this.selectBcc = []
			this.subjectVal = ''
			this.bodyVal = ''
			this.attachments = []
			this.errorText = undefined
			this.state = STATES.EDITING
		},
		/**
		 * Format aliases for the Multiselect
		 * @returns {string}
		 */
		formatAliases(alias) {
			if (!alias.name) {
				return alias.emailAddress
			}

			return `${alias.name} <${alias.emailAddress}>`
		},
		bodyWithSignature(alias, body) {
			if (!alias || !alias.signature) {
				return body
			}

			return `${body} <br>--<br> ${textToSimpleHtml(alias.signature)}`
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
	background: linear-gradient(transparent, var(--color-main-background-translucent) 50%);
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
	padding: 24px 12px;
}

.noreply-box {
	padding: 5px 12px;
	border-radius: 0;
}

.message-body {
	min-height: 300px;
	width: 100%;
	margin: 0;
	padding: 12px;
	padding-top: 0;
	border: none !important;
	outline: none !important;
	box-shadow: none !important;
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
	padding: 12px;
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

.send {
	padding: 12px 18px 13px 36px;
	background-position: 12px center;
	margin-left: 4px;
}
</style>

<style>
.multiselect .multiselect__tags {
	border: none !important;
}
</style>
