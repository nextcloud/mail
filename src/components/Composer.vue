<template>
	<div v-if="state === STATES.EDITING"
		 class="message-composer">
		<select class="mail-account"
				v-model="selectedAlias"
				v-on:keyup="onInputChanged">
			<option v-for="alias in aliases" :value="alias.id">
				{{ t('mail', 'from') }} {{alias.name}} &lt;{{alias.emailAddress}}&gt;
			</option>
		</select>
		<div class="composer-fields">
			<a href="#" :class="{
				'composer-cc-bcc-toggle': true,
				transparency: true,
				hidden: hasCC,
				}">{{ t ('mail', '+ cc/bcc') }}</a>
			<input type="text"
				   id="to"
				   v-model="toVal"
				   v-on:keyup="onInputChanged"
				   class="to recipient-autocomplete"/>
			<label class="to-label transparency" for="to">{{ t('mail', 'to')
				}}</label>
			<div :class="{ 'composer-cc-bcc': true, hidden: !hasCC }">
				<input type="text"
					   id="cc"
					   class="cc recipient-autocomplete"
					   v-model="ccVal"
					   v-on:keyup="onInputChanged">
				<label for="cc" class="cc-label transparency">
					{{ t('mail', 'cc') }}
				</label>
				<input type="text"
					   id="bcc"
					   class="bcc recipient-autocomplete"
					   v-model="bccVal"
					   v-on:keyup="onInputChanged">
				<label for="bcc" class="bcc-label transparency">
					{{ t('mail', 'bcc') }}
				</label>
			</div>

			<div v-if="noReply"
				 class="warning noreply-box">
				{{ t('mail', 'Note that the mail came from a noreply address so	your reply will probably not be read.') }}
			</div>
			<input v-else
				   type="text"
				   name="subject"
				   v-model="subjectVal"
				   v-on:keyup="onInputChanged"
				   class="subject" autocomplete="off"
				   :placeholder="t('mail', 'Subject')"/>

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
	import Vue from 'vue'

	import Loading from './Loading'
	import ComposerAttachments from './ComposerAttachments'

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
		},
		props: {
			replyTo: {
				type: Object,
			},
			to: {
				type: Array,
				default: () => [],
			},
			cc: {
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
				hasCC: true,
				selectedAlias: this.$route.params.accountId, // TODO: fix for unified inbox
				toVal: this.addressListPlain(this.to),
				ccVal: this.addressListPlain(this.cc),
				bccVal: '',
				subjectVal: this.subject,
				bodyVal: this.body,
				attachments: [],
				noReply: false,
				message: '',
				submitButtonTitle: t('mail', 'Send'),
				draftsPromise: Promise.resolve(),
				savingDraft: undefined,
				saveDraftDebounced: _.debounce(this.saveDraft, 700),
				state: STATES.EDITING,
				errorText: undefined,
				STATES
			}
		},
		computed: {
			aliases () {
				return this.$store.getters.getAccounts()
			},
			isReply () {
				return !_.isUndefined(this.replyTo)
			}
		},
		methods: {
			addressListPlain (addresses) {
				return addresses
					.map(addr => {
						if (addr.label && addr.label !== addr.email) {
							return `"${addr.label}" <${addr.email}>`
						} else {
							return addr.email
						}
					})
					.join('; ')
			},
			getMessageData () {
				return uid => {
					return {
						account: this.selectedAlias,
						to: this.toVal,
						cc: this.ccVal,
						bcc: this.bccVal,
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
				this.toVal = ''
				this.ccVal = ''
				this.bccVal = ''
				this.subjectVal = ''
				this.bodyVal = ''
				this.attachments = []
				this.errorText = undefined
				this.state = STATES.EDITING
			}
		}
	}
</script>

<style>
	#draft-status {
		padding: 5px;
		opacity: 0.5;
		font-size: small;
	}
</style>
