<script id="mail-folder-template" type="text/x-handlebars-template">
	<h2 class="mail_account">{{email}}</h2>
	<ul class="mail_folders" data-account_id="{{id}}">
		{{#each folders}}
		<li data-folder_id="{{id}}"
			{{#if unseen}} class="unread"{{/if}}
		>
		<a>
			{{name}}
			{{#if unseen}}
			<span class="utils">{{unseen}}</span>
			{{/if}}
		</a>
	</li>
	{{/each}}
	</ul>
</script>

<div id="app">
	<div id="app-navigation">
		<img class="loading" src="<?php print_unescaped(OCP\Util::imagePath('core', 'loading.gif')); ?>" />
	</div>
	<div id="app-content">
		<form id="new-message" >
			<input type="button" id="mail_new_message" value="<?php p($l->t('New Message')); ?>" style="display: none">
			<div id="new-message-fields" style="display: none">
				<input type="text" name="to" id="to" placeholder="<?php p($l->t('To')); ?>"/>
				<input type="text" name="subject" id="subject" placeholder="<?php p($l->t('Subject')); ?>"/>
				<textarea name="body" id="new-message-body" ></textarea>
				<input id="new-message-send" class="send" type="submit" value="<?php p($l->t('Send')) ?>">
			</div>
		</form>

		<img class="loading" id="messages-loading" src="<?php print_unescaped(OCP\Util::imagePath('core', 'loading.gif')); ?>" />

		<table id="mail_messages">
			<tr class="template mail_message_summary" data-message-id="0">
				<td class="mail_message_summary_from"></td>
				<td class="mail_message_summary_subject"></td>
			</tr>
			<tr class="template_loading mail_message_loading">
				<td></td>
				<td>
					<img src="<?php print_unescaped(OCP\Util::imagePath('core', 'loading.gif')); ?>" />
				</td>
				<td></td>
			</tr>
		</table>
	</div>
</div>
