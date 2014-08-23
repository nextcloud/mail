<script id="mail-account-manager" type="text/x-handlebars-template">
	<select class="mail_account">
		{{#each this}}
		<option value="{{accountId}}">{{emailAddress}}</option>
		{{/each}}
	</select>
</script>
<script id="mail-attachment-template" type="text/x-handlebars-template">
	<span>{{displayName}}</span><div class="new-message-attachments-action svg icon-delete" data-attachment-id="{{id}}"></div>
</script>
<div id="app">
	<div id="app-content" class="compose">
		<div><span id="app-navigation-msg" class="msg"></span></div>
		<div id="new-message">
			<div id="accountManager"></div>
			<div id="new-message-fields">
				<a href="#" id="new-message-cc-bcc-toggle"
				   class="transparency"><?php p($l->t('+ cc/bcc')); ?></a>
				<input type="text" name="to" id="to"
					   placeholder="<?php p($l->t('Recipient')); ?>"
					   value="<?php p($_['mailto']) ?>" />
				<div id="new-message-cc-bcc">
					<input type="text" name="cc" id="cc"
						   placeholder="<?php p($l->t('cc')); ?>"
						   value="<?php p($_['cc']) ?>" />
					<input type="text" name="bcc" id="bcc"
						   placeholder="<?php p($l->t('bcc')); ?>"
						   value="<?php p($_['bcc']) ?>" />
				</div>
				<input type="text" name="subject" id="subject"
					   placeholder="<?php p($l->t('Subject')); ?>"
					   value="<?php p($_['subject']) ?>" />
				<textarea name="body" id="new-message-body"
						  placeholder="<?php p($l->t('Message …')); ?>"><?php p($_['body']); ?></textarea>
				<input id="new-message-send" class="send" type="submit" value="<?php p($l->t('Send')) ?>">
			</div>
			<div id="new-message-attachments">
				<ul></ul>
				<input type="button" id="mail_new_attachment" value="<?php p($l->t('Add attachment from Files')); ?>">
			</div>
			<div><span id="new-message-msg" class="msg"></div>
			<div id="nav-buttons" class="hidden">
				<input type="button" id="nav-to-mail" value="<?php p($l->t('Open Mail App')); ?>">
				<input type="button" id="back-in-time" value="<?php p($l->t('Back')); ?>">
			</div>
		</div>

	</div>

</div>
