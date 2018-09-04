<template>
	<div class="message-composer">
		<select class="mail-account">
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
			<input type="text" name="to"
				   :value="to | addressListPlain"
				   class="to recipient-autocomplete"/>
			<label class="to-label transparency" for="to">{{ t('mail', 'to')
				}}</label>
			<div :class="{ 'composer-cc-bcc': true, hidden: !hasCC }">
				<input type="text"
					   name="cc"
					   class="cc recipient-autocomplete"
					   :value="cc | addressListPlain"
				/>
				<label for="cc" class="cc-label transparency">
					{{ t('mail', 'cc') }}
				</label>
				<input type="text"
					   name="bcc"
					   class="bcc recipient-autocomplete"
					   :value="bcc | addressListPlain"
				/>
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
				   class="subject" autocomplete="off"
				   :placeholder=" t ('mail', 'Subject')"/>

			<textarea name="body"
					  class="message-body"
					  :placeholder="t('mail', 'Message …')">{{message}}</textarea>
		</div>
		<div class="submit-message-wrapper">
			<input class="submit-message send primary" type="submit"
				   :value="submitButtonTitle" disabled>
			<div class="submit-message-wrapper-inside"></div>
		</div>
		<div class="new-message-attachments">
		</div>
	</div>
</template>

<script>
	export default {
		name: "Composer",
		data () {
			return {
				aliases: [],
				hasCC: true,
				to: [],
				cc: [],
				bcc: [],
				subject: '',
				noReply: false,
				message: 'helllooooooo',
				submitButtonTitle: 'Send',
			}
		},
		filters: {
			addressListPlain (val) {
				return 'todo';
			}
		}
	}
</script>

<style scoped>

</style>