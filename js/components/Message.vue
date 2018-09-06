<template>
	<div class="app-content-details">
		<div id="mail-message-header" class="section">
			<h2 :title="subject">{{subject}}</h2>
			<p class="transparency">
				<AddressList :entries="from"/>
				to <!-- TODO: translate -->
				<AddressList :entries="to"/>
				<template v-if="cc.length">
					(cc <!-- TODO: translate -->
					<AddressList :entries="cc"/><!--
					-->)
				</template>
			</p>
		</div>
		<div class="mail-message-body">
			<div id="mail-content">
				<MessageHTMLBody v-if="hasHtmlBody"/>
				<MessagePlainTextBody v-else
									  :body="body"
									  :signature="signature"/>
			</div>
			<div class="mail-message-attachments"></div>
			<div id="reply-composer"></div>
			<input type="button" id="forward-button" value="Forward">
		</div>
		<Composer/>
	</div>
</template>

<script>
	import AddressList from "./AddressList";
	import Composer from "./Composer";
	import MessageHTMLBody from "./MessageHTMLBody";
	import MessagePlainTextBody from "./MessagePlainTextBody";

	export default {
		name: "Message",
		components: {
			AddressList,
			Composer,
			MessageHTMLBody,
			MessagePlainTextBody,
		},
		data () {
			return {
				from: [
					{
						label: 'Backbone Marionette',
						email: 'backbone.marionette@frameworks.js',
					}
				],
				to: [
					{
						label: 'React',
						email: 'react@frameworks.js',
					},
					{
						label: 'Angular',
						email: 'angular@frameworks.js',
					}
				],
				cc: [
					{
						label: 'Underscore Jayes',
						email: 'underscore@frameworks.js',
					}
				],
				subject: 'Do you enjoy the Vue?',
				hasHtmlBody: false,
				body: 'Henlo!',
				signature: 'Backbone Marionette',
			};
		}
	}
</script>
