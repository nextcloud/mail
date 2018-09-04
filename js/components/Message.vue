<template>
	<div class="app-content-detail">
		<div id="mail-message-header" class="section">
			<h2 :title="subject">{{subject}}</h2>
			<p class="transparency">
				<!--{{printAddressList from}}
				{{ t 'to' }}
				{{printAddressList to}}
				{{#if cc.length}}
				({{ t 'cc' }} {{printAddressList cc}})
				{{/if}}-->
			</p>
		</div>
		<div class="mail-message-body">
			<div id="mail-content">
				<!--{{#if hasHtmlBody}}
				<div id="show-images-text">
					{{ t 'The images have been blocked to protect your privacy.'
					}}
					<button id="show-images-button">{{ t 'Show images from this
						sender' }}
					</button>
				</div>
				<div class="icon-loading">
					<iframe src="{{htmlBodyUrl}}" seamless>
					</iframe>
				</div>
				{{else}}
				{{{body}}}
				{{/if}}-->
			</div>
			<!-- {{#if signature}}
			<div class="mail-signature">
				{{{signature}}}
			</div>
			{{/if}} -->

			<div class="mail-message-attachments"></div>
			<div id="reply-composer"></div>
			<input type="button" id="forward-button" value="Forward">
		</div>
	</div>
</template>

<script>
	export default {
		name: "Message",
		data() {
			return {
				subject: 'Do you enjoy the Vue?'
			};
		}
	}
</script>
