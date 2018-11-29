<template>
	<div v-if="state === STATES.EDITING"
		 class="message-composer">
		<div class="composer-fields mail-account">
			<label class="to-label transparency" for="from">
				{{ t('mail', 'from') }}
			</label>
			<Multiselect :options="aliases"
						 id="from"
						 v-model="selectedAlias"
						 @keyup="onInputChanged"
						 label="name" track-by="id"
						 :customLabel="formatAliases" />
		</div>
		<div class="composer-fields">
			<label class="to-label transparency" for="to">
				{{ t('mail', 'to') }}
			</label>
			<Multiselect :options="selectableRecipients"
						 id="to"
					     @keyup="onInputChanged"
						 :taggable="true"
						 @tag="onNewToAddr"
						 @search-change="onAutocomplete"
						 v-model="selectTo"
						 label="label"
						 track-by="email"
						 :multiple="true" />
			<a v-if="!showCC"
			   href="#"
			   @click.prevent="showCC = true">
				{{ t ('mail', '+ cc/bcc') }}
			</a>
		</div>
		<div class="composer-fields"
			 v-if="showCC">
			<label for="cc" class="cc-label transparency">
				{{ t('mail', 'cc') }}
			</label>
			<Multiselect :options="selectableRecipients"
						 id="cc"
						 @keyup="onInputChanged"
						 :taggable="true"
						 @tag="onNewCcAddr"
						 @search-change="onAutocomplete"
						 v-model="selectCc"
						 label="label" track-by="email"
						 :multiple="true" />
		</div>
		<div class="composer-fields"
			 v-if="showCC">
			<label for="bcc" class="bcc-label transparency">
				{{ t('mail', 'bcc') }}
			</label>
			<Multiselect :options="selectableRecipients"
						 id="bcc"
						 @keyup="onInputChanged"
						 :taggable="true"
						 @tag="onNewBccAddr"
						 @search-change="onAutocomplete"
						 v-model="selectBcc"
						 label="label" track-by="email"
						 :multiple="true" />
		</div>
		<div class="composer-fields">
			<label for="subject" class="subject-label transparency">
				{{ t('mail', 'Subject') }}
			</label>
			<input type="text"
				   id="subject"
				   name="subject"
				   v-model="subjectVal"
				   v-on:keyup="onInputChanged"
				   class="subject" autocomplete="off"
				   :placeholder="t('mail', 'Subject')"/>
		</div>
		<div v-if="noReply"
			 class="warning noreply-box">
			{{ t('mail', 'Note that the mail came from a noreply address so	your reply will probably not be read.') }}
		</div>
		<div class="composer-fields">
			<textarea name="body"
					  class="message-body"
					  v-autosize
					  v-model="bodyVal"
					  v-on:keyup="onInputChanged"
					  :placeholder="t('mail', 'Message …')">{{message}}</textarea>
		</div>
		<div class="submit-message-wrapper">
			<input class="submit-message send primary"
				   type="submit"
				   :value="submitButtonTitle"
				   v-on:click="onSend">
		</div>
		<ComposerAttachments v-model="attachments" />
		<span id="draft-status" v-if="savingDraft === true">{{ t('mail', 'Saving draft …') }}</span>
		<span id="draft-status" v-else-if="savingDraft === false">{{ t('mail', 'Draft saved') }}</span>
	</div>
	<Loading v-else-if="state === STATES.SENDING"
			 :hint="t('mail', 'Sending …')" />
	<div v-else-if="state === STATES.ERROR"
		 class="emptycontent">
		<h2>{{ t('mail', 'Error sending your message') }}</h2>
		<p v-if="errorText">{{ errorText }}</p>
		<button v-on:click="state = STATES.EDITING"
				class="button">{{ t('mail', 'Go back') }}</button>
		<button v-on:click="onSend"
		        class="button primary">{{ t('mail', 'Retry') }}</button>
	</div>
	<div v-else
		 class="emptycontent">
		<h2 v-if="!isReply">{{ t('mail', 'Message sent!') }}</h2>
		<h2 v-else>{{ t('mail', 'Reply sent!') }}</h2>
		<button v-on:click="reset"
				v-if="!isReply"
				class="button primary">{{ t('mail', 'Write another message') }}</button>
	</div>
</template>

<script>
	import _ from 'lodash'
	import Autosize from 'vue-autosize'
	import debouncePromise from 'debounce-promise'
	import {Multiselect} from 'nextcloud-vue'
	import Vue from 'vue'

	import {findRecipient} from '../service/AutocompleteService'
	import Loading from './Loading'
	import ComposerAttachments from './ComposerAttachments'

	const debouncedSearch = debouncePromise(findRecipient, 500)

	Vue.use(Autosize)

	const STATES = Object.seal({
		EDITING: 0,
		SENDING: 1,
		ERROR: 2,
		FINISHED: 3,
	})

	export default {
		name: 'Composer',
		components: {
			ComposerAttachments,
			Loading,
			Multiselect,
		},
		props: {
			replyTo: {
				type: Object,
			},
			fromAccount: {
				type: Number,
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
				default: ''
			},
			draft: {
				type: Function,
				required: true,
			},
			send: {
				type: Function,
				required: true,
			}
		},
		data () {
			return {
				showCC: this.cc.length > 0,
				selectedAlias: -1, // Fixed in `beforeMount`
				autocompleteRecipients: this.to.concat(this.cc).concat(this.bcc),
				newRecipients: [],
				subjectVal: this.subject,
				bodyVal: this.body,
				attachments: [],
				noReply: this.to.some(to =>
					to.email.startsWith('noreply@') || to.email.startsWith('no-reply@')
				),
				message: '',
				submitButtonTitle: t('mail', 'Send'),
				draftsPromise: Promise.resolve(),
				savingDraft: undefined,
				saveDraftDebounced: _.debounce(this.saveDraft, 700),
				state: STATES.EDITING,
				errorText: undefined,
				STATES,
				selectTo: this.to,
				selectCc: this.cc,
				selectBcc: this.bcc,
			}
		},
		beforeMount () {
			if (this.fromAccount) {
				this.selectedAlias = this.aliases.find(alias => alias.id === this.fromAccount)
			}
			this.selectedAlias = this.aliases[0]
		},
		computed: {
			aliases () {
				return this.$store.getters.getAccounts()
					.filter(a => !a.isUnified)
			},
			selectableRecipients () {
				return this.newRecipients.concat(this.autocompleteRecipients)
			},
			isReply () {
				return !_.isUndefined(this.replyTo)
			}
		},
		methods: {
			recipientToRfc822 (recipient) {
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
			getMessageData () {
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
					}
				}
			},
			saveDraft (data) {
				this.savingDraft = true
				this.draftsPromise = this.draftsPromise
					.then(uid => this.draft(data(uid)))
					.catch(console.error.bind(this))
					.then(uid => {
						this.savingDraft = false
						return uid
					})
			},
			onInputChanged () {
				this.saveDraftDebounced(this.getMessageData())
			},
			onAutocomplete (term) {
				if (_.isUndefined(term) || term === '') {
					return
				}
				debouncedSearch(term)
					.then(results => {
						this.autocompleteRecipients = _.uniqBy(
							this.autocompleteRecipients.concat(results),
							'email',
						)
					})
			},
			onNewToAddr (addr) {
				this.onNewAddr(addr, this.selectTo)
			},
			onNewCcAddr (addr) {
				this.onNewAddr(addr, this.selectCc)
			},
			onNewBccAddr (addr) {
				this.onNewAddr(addr, this.selectBcc)
			},
			onNewAddr (addr, list) {
				const res = {
					label: addr, // TODO: parse if possible
					email: addr, // TODO: parse if possible
				}
				this.newRecipients.push(res)
				list.push(res)
			},
			onSend () {
				this.state = STATES.SENDING

				return this.draftsPromise
					.then(this.getMessageData())
					.then(data => this.send(data))
					.then(() => console.info('message sent'))
					.then(() => this.state = STATES.FINISHED)
					.catch(e => {
						console.error('could not send message', e)
						if (e && e.toString) {
							this.errorText = e.toString()
						}
						this.state = STATES.ERROR
					})
			},
			reset () {
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
				return `${alias.name} <${alias.emailAddress}>`
			}
		}
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

	.composer-fields.mail-account > .multiselect {
		max-width: none;
		min-height: auto;
		width: 350px;
	}

	.composer-fields {
		display: flex;
		align-items: center;
		border-top: 1px solid #eee;
		padding-right: 30px;
	}
	.composer-fields .multiselect,
	.composer-fields input,
	.composer-fields textarea {
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
	textarea.message-body {
		padding: 12px;
		margin: 0;
	}

	#to {
		padding-right: 60px; /* for cc-bcc-toggle */
	}

	input.cc,
	input.bcc,
	input.subject,
	textarea.message-body {
		border-top: none;
	}

	input.subject {
		font-size: 20px;
		font-weight: 300;
	}

	textarea.message-body {
		min-height: 300px;
		resize: none;
		padding-right: 25%;
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
		opacity: .5;
		width: 90px;
		text-align: right;
	}

	label.bcc-label {
		top: initial;
		bottom: 0;
	}

	textarea.reply {
		min-height: 100px;
	}

	input.submit-message,
	.submit-message-wrapper {
		position: fixed;
		bottom: 10px;
		right: 15px;
	}

	.submit-message-wrapper {
		position: fixed;
		height: 36px;
		width: 60px;
	}

	.submit-message.send {
		padding: 12px;
	}

</style>

<style>
	.multiselect .multiselect__tags {
		border: none !important;
	}
</style>
