<template>
	<div class="message-composer">
		<select class="mail-account"
				v-model="selectedAlias"
				v-on:keyup="onInputChanged"
				:disabled="sending">
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
				   :disabled="sending"
				   class="to recipient-autocomplete"/>
			<label class="to-label transparency" for="to">{{ t('mail', 'to')
				}}</label>
			<div :class="{ 'composer-cc-bcc': true, hidden: !hasCC }">
				<input type="text"
					   id="cc"
					   class="cc recipient-autocomplete"
					   v-model="ccVal"
					   v-on:keyup="onInputChanged"
					   :disabled="sending"/>
				<label for="cc" class="cc-label transparency">
					{{ t('mail', 'cc') }}
				</label>
				<input type="text"
					   id="bcc"
					   class="bcc recipient-autocomplete"
					   v-model="bccVal"
					   v-on:keyup="onInputChanged"
					   :disabled="sending"/>
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
				   :disabled="sending"
				   class="subject" autocomplete="off"
				   :placeholder=" t ('mail', 'Subject')"/>

			<textarea name="body"
					  class="message-body"
					  v-autosize
					  v-model="bodyVal"
					  v-on:keyup="onInputChanged"
					  :disabled="sending"
					  :placeholder="t('mail', 'Message …')">{{message}}</textarea>
		</div>
		<div class="submit-message-wrapper">
			<input class="submit-message send primary"
				   type="submit"
				   :value="submitButtonTitle"
				   :disabled="sending"
				   v-on:click="onSend">
		</div>
		<div class="new-message-attachments">
		</div>
		<span id="draft-status" v-if="savingDraft === true">{{ t('mail', 'Saving draft …') }}</span>
		<span id="draft-status" v-else-if="savingDraft === false">{{ t('mail', 'Draft saved') }}</span>
	</div>
</template>

<script>
	import _ from 'lodash'
	import Autosize from 'vue-autosize'
	import Vue from 'vue'

	Vue.use(Autosize)

	export default {
		name: 'Composer',
		props: {
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
				selectedAlias: this.$route.params.accountId,
				toVal: this.addressListPlain(this.to),
				ccVal: this.addressListPlain(this.cc),
				bccVal: '',
				subjectVal: this.subject,
				bodyVal: '',
				noReply: false,
				message: '',
				submitButtonTitle: t('mail', 'Send'),
				draftsPromise: Promise.resolve(),
				sending: false,
				savingDraft: undefined,
				saveDraftDebounced: _.debounce(this.saveDraft, 700)
			}
		},
		computed: {
			aliases () {
				return this.$store.getters.getAccounts()
			}
		},
		filters: {

		},
		methods: {
			addressListPlain (addresses) {
				return addresses.join('; ')
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
						attachments: [], // TODO
					}
				}
			},
			saveDraft (data) {
				this.savingDraft = true
				this.draftsPromise = this.draftsPromise
					.then(uid => this.draft(data(uid)))
					.catch(console.error.bind(this))
					.then(() => this.savingDraft = false)
			},
			onInputChanged () {
				this.saveDraftDebounced(this.getMessageData())
			},
			onSend () {
				this.sending = true

				return this.draftsPromise
					.then(this.getMessageData())
					.then(data => this.send(data))
					.then(() => console.info('message sent'))
					.catch(e => console.error('could not send message', e))
					.then(() => this.sending = false)
					.then(() => this.reset())
			},
			reset () {
				this.toVal = ''
				this.ccVal = ''
				this.bccVal = ''
				this.subjectVal = ''
				this.bodyVal = ''
				this.attachments = []
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
