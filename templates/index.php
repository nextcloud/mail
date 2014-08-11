<script id="mail-account-manager" type="text/x-handlebars-template">
	<select class="mail_account">
		<!--<option value="allAccounts"><?php p($l->t('All accounts')) ?></option>-->
		{{#each this}}
		<option value="{{accountId}}">{{emailAddress}}</option>
		{{/each}}
		<option value="addAccount"><?php p($l->t('+ Add account')) ?></option>
	</select>
</script>
<script id="mail-folder-template" type="text/x-handlebars-template">
	{{#each this}}
	<ul class="mail_folders" data-account_id="{{id}}">
		{{#each folders}}
		<li data-folder_id="{{id}}"
		{{#if unseen}}
		class="unread"
		{{/if}}
		{{#if isEmpty}}
		class="empty"
		{{/if}}
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
	{{/each}}
</script>
<script id="mail-messages-template" type="text/x-handlebars-template">
	{{#each this}}
	<div id="mail-message-summary-{{id}}" class="mail_message_summary {{#if flags.unseen}}unseen{{/if}}" data-message-id="{{id}}">
		<div class="mail-message-header">
			<div class="sender-image">
				{{#if senderImage}}
				<img src="{{senderImage}}" width="32px" height="32px" />
				{{else}}
				<div class="avatar" data-user="{{from}}" data-size="32"></div>
				{{/if}}
			</div>
			{{#if flags.flagged}}
			<div class="star icon-starred" data-starred="true"></div>
			{{else}}
			<div class="star icon-star" data-starred="false"></div>
			{{/if}}
			<div class="mail_message_summary_from" title="{{fromEmail}}">{{from}}</div>
			<div class="mail_message_summary_subject{{#if flags.hasAttachments}} icon-public{{/if}}">
				{{subject}}
			</div>
			<div class="date">
					<span class="modified"
						  title="{{formatDate dateInt}}"
						  style="color:{{colorOfDate dateInt}}">{{relativeModifiedDate dateInt}}</span>
			</div>
			<div class="icon-delete action delete"></div>
		</div>
	</div>
	{{/each}}
</script>
<script id="mail-message-template" type="text/x-handlebars-template">
	<div class="mail-message-body">
		<div id="mail-content">
			{{#if hasHtmlBody}}
			<div class="icon-loading">
				<iframe src="{{htmlBodyUrl}}" sandbox="allow-top-navigation allow-same-origin" seamless>
				</iframe>
			</div>
			{{else}}
			{{{body}}}
			{{/if}}
		</div>
		{{#if signature}}
		<div class="mail-signature">
			{{{signature}}}
		</div>
		{{/if}}

		<div class="mail-message-attachments">
			{{#if attachment}}
			<ul>
				<li class="mail-message-attachment mail-message-attachment-single" data-attachment-id="{{attachment.id}}" data-attachment-mime="{{attachment.mime}}">
					<img class="attachment-icon" src="{{attachment.mimeUrl}}" />
					{{attachment.fileName}} <span class="attachment-size">({{humanFileSize attachment.size}})</span><br/>
					<a class="button icon-download attachment-download" href="{{attachment.downloadUrl}}"><?php p($l->t('Download attachment')); ?></a>
					<button class="icon-upload attachment-save-to-cloud"><?php p($l->t('Save to Files')); ?></button>
				</li>
			</ul>
			{{/if}}
			{{#if attachments}}
			<ul>
				{{#each attachments}}
				<li class="mail-message-attachment" data-attachment-id="{{id}}" data-attachment-mime="{{mime}}">
					<a class="button icon-download attachment-download" href="{{downloadUrl}}" title="<?php p($l->t('Download attachment')); ?>"></a>
					<button class="icon-upload attachment-save-to-cloud" title="<?php p($l->t('Save to Files')); ?>"></button>
					<img class="attachment-icon" src="{{mimeUrl}}" />
					{{fileName}} <span class="attachment-size">({{humanFileSize size}})</span>
				</li>
				{{/each}}
			</ul>
			<p>
				<button class="icon-upload attachments-save-to-cloud"><?php p($l->t('Save all to Files')); ?></button>
			</p>
			{{/if}}
		</div>

		<div class="reply-message-fields">
			<a href="#" id="reply-message-cc-bcc-toggle"
			   class="transparency"><?php p($l->t('+ cc')); ?></a>

			<input type="text" name="to" id="to"
				   placeholder="<?php p($l->t('Recipient')); ?>"
				   value="{{fromEmail}}"/>

			<div id="reply-message-cc-bcc"
			{{#unless cc}}
			class="hidden"
			{{/unless}}
			>
			<input type="text" name="cc" id="cc"
				   placeholder="<?php p($l->t('cc')); ?>" value="{{cc}}" />
			<!--<input type="text" name="bcc" id="bcc"
				placeholder="<?php p($l->t('bcc')); ?>" />-->
		</div>

		<textarea name="body" class="reply-message-body"
				  placeholder="<?php p($l->t('Reply …')); ?>"></textarea>
		<input class="reply-message-send" type="submit" value="<?php p($l->t('Reply')) ?>">
	</div>
	<div class="reply-message-more">
		<!--<a href="#" class="reply-message-forward transparency"><?php p($l->t('Forward')) ?></a>-->
		<!-- TODO: add attachment picker -->
	</div>
	</div>
</script>
<script id="mail-attachment-template" type="text/x-handlebars-template">
	<span>{{displayName}}</span><div class="new-message-attachments-action svg icon-delete" data-attachment-id="{{id}}"></div>
</script>
<div id="app">
	<div id="app-navigation" class="icon-loading">
		<div id="accountManager"></div>
		<input type="button" id="mail_new_message" class="primary"
			value="<?php p($l->t('New Message')); ?>" style="display: none">
		<div><span id="app-navigation-msg" class="msg"></span></div>
		<div id="folders"></div>
	</div>
	<div id="app-content">
		<div id="new-message" style="display: none">
			<div id="new-message-fields">
				<a href="#" id="new-message-cc-bcc-toggle"
				   class="transparency"><?php p($l->t('+ cc/bcc')); ?></a>
				<input type="text" name="to" id="to"
					   placeholder="<?php p($l->t('Recipient')); ?>" />
				<div id="new-message-cc-bcc">
					<input type="text" name="cc" id="cc"
						   placeholder="<?php p($l->t('cc')); ?>" />
					<input type="text" name="bcc" id="bcc"
						   placeholder="<?php p($l->t('bcc')); ?>" />
				</div>
				<input type="text" name="subject" id="subject"
					   placeholder="<?php p($l->t('Subject')); ?>" />
				<textarea name="body" id="new-message-body"
						  placeholder="<?php p($l->t('Message …')); ?>"></textarea>
				<input id="new-message-send" class="send" type="submit" value="<?php p($l->t('Send')) ?>">
			</div>
			<div id="new-message-attachments">
				<ul></ul>
				<input type="button" id="mail_new_attachment" value="<?php p($l->t('Add attachment from Files')); ?>">
			</div>
			<div><span id="new-message-msg" class="msg"></div>
		</div>

		<div id="mail_messages" class="icon-loading">
			<div id="mail-message-list"></div>
			<input type="button" id="load-more-mail-messages" value="<?php p($l->t('Load more …')); ?>">
		</div>

		<div id="mail-message" class="icon-loading">
		</div>
	</div>

	<form id="mail-setup" class="hidden">
		<fieldset>
			<h2><?php p($l->t('Connect your mail account')) ?></h2>

			<p class="grouptop">
				<input type="text" name="mail-account-name" id="mail-account-name"
					   placeholder="<?php p($l->t('Name')); ?>"
					   value="<?php p(\OCP\User::getDisplayName(\OCP\User::getUser())); ?>"
					   autofocus autocomplete="off" required/>
				<label for="mail-address" class="infield"><?php p($l->t('Mail Address')); ?></label>
			</p>
			<p class="groupmiddle">
				<input type="email" name="mail-address" id="mail-address"
					   placeholder="<?php p($l->t('Mail Address')); ?>"
					   value="<?php p(\OCP\Config::getUserValue(\OCP\User::getUser(), 'settings', 'email', '')); ?>"
					   autofocus autocomplete="off" required/>
				<label for="mail-address" class="infield"><?php p($l->t('Mail Address')); ?></label>
			</p>
			<p class="groupbottom">
				<input type="password" name="mail-password" id="mail-password"
					   placeholder="<?php p($l->t('IMAP Password')); ?>" value="" />
				<label for="mail-password" class="infield"><?php p($l->t('IMAP Password')); ?></label>
			</p>
			<img id="connect-loading" src="<?php print_unescaped(OCP\Util::imagePath('core', 'loading.gif')); ?>" style="display:none;" />
			<input type="submit" id="auto_detect_account" class="connect primary" value="<?php p($l->t('Connect')); ?>"/>
		</fieldset>
	</form>


</div>
