<template>
	<div v-if="state === STATES.EDITING" class="message-composer">
		<div class="composer-fields mail-account">
			<label class="to-label transparency" for="from">
				{{ t('mail', 'from') }}
			</label>
			<Multiselect
				id="from"
				v-model="selectedAlias"
				:options="aliases"
				label="name"
				track-by="id"
				:custom-label="formatAliases"
				@keyup="onInputChanged"
			/>
		</div>
		<div class="composer-fields">
			<label class="to-label transparency" for="to">
				{{ t('mail', 'to') }}
			</label>
			<Multiselect
				id="to"
				v-model="selectTo"
				:options="selectableRecipients"
				:taggable="true"
				label="label"
				track-by="email"
				:multiple="true"
				@keyup="onInputChanged"
				@tag="onNewToAddr"
				@search-change="onAutocomplete"
			/>
			<a v-if="!showCC" href="#" @click.prevent="showCC = true">
				{{ t('mail', '+ CC/BCC') }}
			</a>
		</div>
		<div v-if="showCC" class="composer-fields">
			<label for="cc" class="cc-label transparency">
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
				@keyup="onInputChanged"
				@tag="onNewCcAddr"
				@search-change="onAutocomplete"
			/>
		</div>
		<div v-if="showCC" class="composer-fields">
			<label for="bcc" class="bcc-label transparency">
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
				@keyup="onInputChanged"
				@tag="onNewBccAddr"
				@search-change="onAutocomplete"
			/>
		</div>
		<div class="composer-fields">
			<label for="subject" class="subject-label transparency">
				{{ t('mail', 'Subject') }}
			</label>
			<input
				id="subject"
				v-model="subjectVal"
				type="text"
				name="subject"
				class="subject"
				autocomplete="off"
				:placeholder="t('mail', 'Subject')"
				@keyup="onInputChanged"
			/>
		</div>
		<div v-if="noSubject" class="warning">
			{{ t('mail', 'This mail does not have a subject yet.') }}
		</div>
		<div v-if="noReply" class="warning noreply-box">
			{{ t('mail', 'Note that the mail came from a noreply address so	your reply will probably not be read.') }}
		</div>
		<div class="composer-fields">
			<!--@keypress="onBodyKeyPress"-->
			<TextEditor
				v-if="editorPlainText"
				key="editor-plain"
				v-model="bodyVal"
				name="body"
				class="message-body"
				@input="onInputChanged"
			></TextEditor>
			<TextEditor
				v-else
				key="editor-rich"
				v-model="bodyVal"
				:html="true"
				name="body"
				class="message-body"
				@input="onInputChanged"
			></TextEditor>
		</div>
		<div class="composer-actions">
			<div>
				<ComposerAttachments v-model="attachments" @upload="onAttachmentsUploading" />
			</div>
			<div>
				<input class="submit-message send primary" type="submit" :value="submitButtonTitle" @click="onSend" />
			</div>
		</div>
		<Actions>
			<ActionText icon="icon-info">{{ t('mail', 'Message options') }}</ActionText>
			<ActionCheckbox :checked.sync="editorPlainText" :text="t('mail', 'Plain text')">{{
				t('mail', 'Plain text')
			}}</ActionCheckbox>
		</Actions>
		<span v-if="savingDraft === true" id="draft-status">{{ t('mail', 'Saving draft …') }}</span>
		<span v-else-if="savingDraft === false" id="draft-status">{{ t('mail', 'Draft saved') }}</span>
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
		<h2 v-if="!isReply">{{ t('mail', 'Message sent!') }}</h2>
		<h2 v-else>{{ t('mail', 'Reply sent!') }}</h2>
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
import ActionCheckbox from '@nextcloud/vue/dist/Components/ActionCheckbox'
import ActionText from '@nextcloud/vue/dist/Components/ActionText'
import Multiselect from '@nextcloud/vue/dist/Components/Multiselect'
import {translate as t} from '@nextcloud/l10n'
import Vue from 'vue'

import TextEditor from './TextEditor'
import {findRecipient} from '../service/AutocompleteService'
import Loading from './Loading'
import Logger from '../logger'
import ComposerAttachments from './ComposerAttachments'

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
		ActionCheckbox,
		ActionText,
		ComposerAttachments,
		Loading,
		Multiselect,
		TextEditor,
	},
	props: {
		replyTo: {
			type: Object,
			default: () => undefined,
		},
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
			bodyVal: this.isPlainText ? this.body.replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1<br>$2') : this.body,
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
		}
	},
	computed: {
		aliases() {
			return this.$store.getters.getAccounts().filter(a => !a.isUnified)
		},
		selectableRecipients() {
			return this.newRecipients.concat(this.autocompleteRecipients)
		},
		isReply() {
			return this.replyTo !== undefined
		},
		noSubject() {
			return this.subjectVal === '' && this.bodyVal !== ''
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
	methods: {
		recipientToRfc822(recipient) {
			if (recipient.email === recipient.label) {
				// From mailto or sender without proper label
				return recipient.email
			} else if (recipient.label === '') {
				// Invalid label
				return recipient.email
			} else if (recipient.email.search(/^[a-zA-Z]+:[a-zA-Z]+$/) === 0) {
				// Group integration
				return recipient.email
			} else {
				// Proper layout with label
				return `"${recipient.label}" <${recipient.email}>`
			}
		},
		getMessageData() {
			return uid => {
				return {
					account: this.selectedAlias.id,
					to: this.selectTo.map(this.recipientToRfc822).join(', '),
					cc: this.selectCc.map(this.recipientToRfc822).join(', '),
					bcc: this.selectBcc.map(this.recipientToRfc822).join(', '),
					draftUID: uid,
					subject: this.subjectVal,
					body: this.bodyVal,
					attachments: this.attachments,
					folderId: this.replyTo ? this.replyTo.folderId : undefined,
					messageId: this.replyTo ? this.replyTo.messageId : undefined,
					isHtml: !this.editorPlainText,
				}
			}
		},
		saveDraft(data) {
			this.savingDraft = true
			this.draftsPromise = this.draftsPromise
				.then(uid => this.draft(data(uid)))
				.catch(Logger.error)
				.then(uid => {
					this.savingDraft = false
					return uid
				})
		},
		onInputChanged() {
			this.saveDraftDebounced(this.getMessageData())
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
				.catch(error => Logger.error('could not upload attachments', {error}))
				.then(() => Logger.debug('attachments uploaded'))
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
				.then(this.getMessageData())
				.then(data => this.send(data))
				.then(() => Logger.info('message sent'))
				.then(() => (this.state = STATES.FINISHED))
				.catch(error => {
					Logger.error('could not send message', {error})
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

			return body + '\n\n--\n\n' + alias.signature
		},
	},
}
</script>

<style scoped>
.message-composer {
	margin: 0;
	margin-bottom: 10px; /* line up with the send button */
	z-index: 100;
}

#reply-composer .message-composer {
	margin: 0;
}

.composer-actions {
	display: flex;
	flex-direction: row;
	align-items: flex-end;
}

.composer-fields.mail-account > .multiselect {
	max-width: none;
	min-height: auto;
	width: 350px;
}

.composer-fields {
	display: flex;
	align-items: center;
	border-top: 1px solid var(--color-border);
	padding-right: 30px;
}
.composer-fields .multiselect,
.composer-fields input,
.composer-fields TextEditor {
	flex-grow: 1;
	max-width: none;
	border: none;
	border-radius: 0px;
}
.noreply-box {
	margin-top: 0;
	background: #fdffc3;
	padding-left: 64px;
}

#to,
#cc,
#bcc,
input.subject,
.message-body {
	padding: 12px;
	margin: 0;
}

#to {
	padding-right: 60px; /* for cc-bcc-toggle */
}

input.cc,
input.bcc,
input.subject,
.message-body {
	border-top: none;
}

input.subject {
	font-size: 20px;
	font-weight: 300;
}

.message-body {
	min-height: 300px;
	width: 100%;
	padding-right: 25%;
	border: none !important;
	outline: none !important;
	box-shadow: none !important;
}

#draft-status {
	padding: 5px;
	opacity: 0.5;
	font-size: small;
}

label.to-label,
label.cc-label,
label.bcc-label,
label.subject-label {
	padding: 12px;
	padding-left: 30px;
	cursor: text;
	opacity: 0.5;
	width: 90px;
	text-align: right;
}

label.bcc-label {
	top: initial;
	bottom: 0;
}

.reply {
	min-height: 100px;
}
</style>

<style>
.multiselect .multiselect__tags {
	border: none !important;
}
</style>
