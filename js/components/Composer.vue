<template>
	<div class="message-composer">
		<select class="mail-account"
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
				   :value="to | addressListPlain"
				   :disabled="sending"
				   class="to recipient-autocomplete"/>
			<label class="to-label transparency" for="to">{{ t('mail', 'to')
				}}</label>
			<div :class="{ 'composer-cc-bcc': true, hidden: !hasCC }">
				<input type="text"
					   id="cc"
					   class="cc recipient-autocomplete"
					   :value="cc | addressListPlain"
					   :disabled="sending"/>
				<label for="cc" class="cc-label transparency">
					{{ t('mail', 'cc') }}
				</label>
				<input type="text"
					   id="bcc"
					   class="bcc recipient-autocomplete"
					   :value="bcc | addressListPlain"
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
				   :value="subject"
				   :disabled="sending"
				   class="subject" autocomplete="off"
				   :placeholder=" t ('mail', 'Subject')"/>

			<textarea name="body"
					  class="message-body"
					  v-autosize
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
	</div>
</template>

<script>
	import Autosize from 'vue-autosize';
	import Vue from 'vue';

	Vue.use(Autosize);

	export default {
		name: "Composer",
		props: {
			draft: {
				type: Function,
				required: true
			},
			send: {
				type: Function,
				required: true
			}
		},
		data () {
			return {
				hasCC: true,
				to: [],
				cc: [],
				bcc: [],
				subject: '',
				noReply: false,
				message: '',
				submitButtonTitle: t('mail', 'Send'),
				sending: false,
			}
		},
		computed: {
			aliases () {
				return this.$store.getters.getAccounts()
			}
		},
		filters: {
			addressListPlain (addresses) {
				return addresses.join('; ')
			}
		},
		methods: {
			onSend () {
				this.sending = true

				return this.send()
					.then(() => console.info('message sent'))
					.catch(e => console.error('could not send message', e))
					.then(() => this.sending = false)
			}
		}
	}
</script>
