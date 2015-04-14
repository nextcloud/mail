<script id="mail-folder-template" type="text/x-handlebars-template">
	<li data-folder_id="{{id}}" data-no_select="{{noSelect}}"
		class="
		{{#if unseen}}unread{{/if}}
		{{#if specialRole}} special-{{specialRole}}{{/if}}
		{{#if folders}} collapsible{{/if}}
		{{#if open}} open{{/if}}
		">
		{{#if folders}}<button class="collapse"></button>{{/if}}
		<a class="folder {{#if specialRole}} icon-{{specialRole}}{{/if}}">
			{{name}}
			{{#if unseen}}
			<span class="utils">{{unseen}}</span>
			{{/if}}
		</a>
		<ul>
			{{#each folders}}
			<li data-folder_id="{{id}}"
				class="
		{{#if unseen}}unread{{/if}}
		{{#if specialRole}} special-{{specialRole}}{{/if}}
		">
				<a class="folder {{#if specialRole}} icon-{{specialRole}}{{/if}}">
					{{name}}
					{{#if unseen}}
					<span class="utils">{{unseen}}</span>
					{{/if}}
				</a>
				{{/each}}
		</ul>
	</li>
</script>
<script id="mail-account-template" type="text/x-handlebars-template">
	<h2 class="mail_account_email">{{email}}</h2>
	<ul id="mail_folders" class="mail_folders with-icon" data-account_id="{{id}}">
	</ul>
</script>
<script id="mail-messages-template" type="text/x-handlebars-template">
	<div class="mail_message_summary {{#if flags.unseen}}unseen{{/if}} {{#if active}}active{{/if}}" data-message-id="{{id}}">
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

			{{#if flags.answered}}
			<div class="icon-reply"></div>
			{{/if}}

			{{#if flags.hasAttachments}}
			<div class="icon-public icon-attachment"></div>
			{{/if}}

			<div class="mail_message_summary_from" title="{{fromEmail}}">{{from}}</div>
			<div class="mail_message_summary_subject">
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
</script>
<script id="mail-message-template" type="text/x-handlebars-template">
	<div id="mail-message-header" class="section">
		<h2>{{subject}}</h2>
		<p class="transparency">
			<span title="{{fromEmail}}">{{from}}</span>
			{{#if toList}}
			<?php p($l->t('to')); ?>
			{{printAddressList toList}}
			{{/if}}
			{{#if ccList}}
			(<?php p($l->t('cc')); ?>
			{{printAddressList ccList}}
			)
			{{/if}}
		</p>
	</div>
	<div class="mail-message-body">
		<div id="mail-content">
			{{#if hasHtmlBody}}
			<div class="icon-loading">
				<iframe src="{{htmlBodyUrl}}" sandbox="allow-popups allow-same-origin" seamless>
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
				<li class="mail-message-attachment mail-message-attachment-single" data-message-id="{{attachment.messageId}}" data-attachment-id="{{attachment.id}}" data-attachment-mime="{{attachment.mime}}">
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
				<li class="mail-message-attachment" data-message-id="{{messageId}}" data-attachment-id="{{id}}" data-attachment-mime="{{mime}}">
					<a class="button icon-download attachment-download" href="{{downloadUrl}}" title="<?php p($l->t('Download attachment')); ?>"></a>
					<button class="icon-upload attachment-save-to-cloud" title="<?php p($l->t('Save to Files')); ?>"></button>
					<img class="attachment-icon" src="{{mimeUrl}}" />
					{{fileName}} <span class="attachment-size">({{humanFileSize size}})</span>
				</li>
				{{/each}}
			</ul>
			<p>
				<button data-message-id="{{id}}" class="icon-upload attachments-save-to-cloud"><?php p($l->t('Save all to Files')); ?></button>
			</p>
			{{/if}}
		</div>

		<div class="reply-message-fields">
			<a href="#" id="reply-message-cc-bcc-toggle"
				{{#if replyCcList}}
				class="hidden"
				{{/if}}
				class="transparency"><?php p($l->t('+ cc')); ?></a>

			<input type="text" name="to" id="to" class="recipient-autocomplete"
				value="{{printAddressListPlain replyToList}}"/>
			<label id="to-label" for="to" class="transparency"><?php p($l->t('to')); ?></label>

			<div id="reply-message-cc-bcc"
			{{#unless replyCcList}}
			class="hidden"
			{{/unless}}
			>
				<input type="text" name="cc" id="cc" class="recipient-autocomplete"
					value="{{printAddressListPlain replyCcList}}" />
				<label id="cc-label" for="cc" class="transparency"><?php p($l->t('cc')); ?></label>
				<!--
				<input type="text" name="bcc" id="bcc" class="recipient-autocomplete" />
				<label id="bcc-label" for="bcc" class="transparency"><?php p($l->t('bcc')); ?></label>
				-->
		</div>

		<textarea name="body" class="reply-message-body"
			placeholder="<?php p($l->t('Reply …')); ?>"></textarea>
		<input class="reply-message-send primary" type="submit" value="<?php p($l->t('Reply')) ?>">
		<div><span id="reply-msg" class="msg"></div>
	</div>
	<div class="reply-message-more">
		<!--<a href="#" class="reply-message-forward transparency"><?php p($l->t('Forward')) ?></a>-->
		<!-- TODO: add attachment picker -->
	</div>
</script>
<script id="mail-attachment-template" type="text/x-handlebars-template">
	<span>{{displayName}}</span><div class="new-message-attachments-action svg icon-delete"></div>
</script>
<script id="mail-settings-template" type="text/x-handlebars-template">
<div id="mailsettings">
	<ul class="mailaccount-list">
		{{#each this}}
		<li id="mail-account-{{accountId}}" data-account-id="{{accountId}}">
			<span class="mail-account-name">{{emailAddress}}</span>
			<span class="actions">
				<a class="icon-delete delete action" original-title="Delete"></a>
			</span>
		</li>
		{{/each}}
	</ul>
	<input id="new_mail_account" type="submit" value="<?php p($l->t('Add mail account')); ?>" class="new-button">
</div>
</script>
<script id="new-message-template" type="text/x-handlebars-template">
	<div id="new-message">
		<select class="mail_account">
			{{#each aliases}}
			<option value="{{accountId}}"><?php p($l->t('from')); ?> {{name}} &lt;{{emailAddress}}&gt;</option>
			{{/each}}
		</select>
		<div id="new-message-fields">
			<a href="#" id="new-message-cc-bcc-toggle"
				class="transparency"><?php p($l->t('+ cc/bcc')); ?></a>
			<input type="text" name="to" id="to" class="recipient-autocomplete" />
			<label id="to-label" for="to" class="transparency"><?php p($l->t('to')); ?></label>
			<div id="new-message-cc-bcc">
				<input type="text" name="cc" id="cc" class="recipient-autocomplete" />
				<label id="cc-label" for="cc" class="transparency"><?php p($l->t('cc')); ?></label>
				<input type="text" name="bcc" id="bcc" class="recipient-autocomplete" />
				<label id="bcc-label" for="bcc" class="transparency"><?php p($l->t('bcc')); ?></label>
			</div>
			<input type="text" name="subject" id="subject"
				placeholder="<?php p($l->t('Subject')); ?>" />
			<textarea name="body" id="new-message-body"
				placeholder="<?php p($l->t('Message …')); ?>"></textarea>
			<input id="new-message-send" class="send primary" type="submit" value="<?php p($l->t('Send')) ?>">
		</div>
		<div id="new-message-attachments">
		</div>
		<div><span id="new-message-msg" class="msg"></div>
	</div>
</script>
<script id="mail-attachments-template" type="text/x-handlebars-template">
	<ul></ul>
	<input type="button" id="mail_new_attachment" value="<?php p($l->t('Add attachment from Files')); ?>">
</script>
<script id="message-list-template" type="text/x-handlebars-template">
	<input type="button" id="load-new-mail-messages" value="<?php p($l->t('Check messages …')); ?>">
	<div id="emptycontent" style="display: none;"><?php p($l->t('No messages in this folder!')); ?></div>
	<div id="mail-message-list"></div>
	<input type="button" id="load-more-mail-messages" value="<?php p($l->t('Load more …')); ?>">
</script>
<div id="app">
	<div id="app-navigation" class="icon-loading">
		<ul>
			<li>
			<input type="button" id="mail_new_message" class="icon-add"
				value="<?php p($l->t('New message')); ?>" style="display: none">
			</li>
			<li>
				<div id="folders"></div>
			</li>
		</ul>
		<div id="app-settings">
			<div id="app-settings-header">
				<button class="settings-button"
					data-apps-slide-toggle="#app-settings-content"></button>
			</div>
			<div id="app-settings-content"> </div>
		</div>
	</div>
	<div id="app-content">
		<div id="mail_messages" class="icon-loading">
		</div>

		<div id="mail-message" class="icon-loading">
		</div>
	</div>

	<form id="mail-setup" class="hidden" method="post">
		<fieldset>
			<h2><?php p($l->t('Connect your mail account')) ?></h2>

			<p class="grouptop">
				<input type="text" name="mail-account-name" id="mail-account-name"
					placeholder="<?php p($l->t('Name')); ?>"
					value="<?php p(\OCP\User::getDisplayName(\OCP\User::getUser())); ?>"
					autofocus />
				<label for="mail-address" class="infield"><?php p($l->t('Mail Address')); ?></label>
			</p>
			<p class="groupmiddle">
				<input type="email" name="mail-address" id="mail-address"
					placeholder="<?php p($l->t('Mail Address')); ?>"
					value="<?php p(\OCP\Config::getUserValue(\OCP\User::getUser(), 'settings', 'email', '')); ?>"
					required />
				<label for="mail-address" class="infield"><?php p($l->t('Mail Address')); ?></label>
			</p>
			<p class="groupbottom">
				<input type="password" name="mail-password" id="mail-password"
					placeholder="<?php p($l->t('IMAP Password')); ?>" value=""
					required />
				<label for="mail-password" class="infield"><?php p($l->t('IMAP Password')); ?></label>
			</p>


			<a id="mail-setup-manual-toggle" class="icon-caret-dark"><?php p($l->t('Manual configuration')); ?></a>

			<div id="mail-setup-manual" style="display:none;">
				<p class="grouptop">
					<input type="text" name="mail-imap-host" id="mail-imap-host"
						placeholder="<?php p($l->t('IMAP Host')); ?>"
						value="" />
					<label for="mail-imap-host" class="infield"><?php p($l->t('IMAP Host')); ?></label>
				</p>
				<p class="groupmiddle" id="mail-imap-ssl">
						<label for="mail-imap-sslmode"><?php p($l->t('IMAP security')); ?></label>
						<select name="mail-imap-sslmode" id="mail-imap-sslmode" title="<?php p($l->t('IMAP security')); ?>">
							<option value="none"><?php p($l->t('none')); ?></option>
							<option value="ssl"><?php p($l->t('ssl')); ?></option>
							<option value="tls"><?php p($l->t('tls')); ?></option>
						</select>
				</p>
				<p class="groupmiddle"> 
					<input type="email" name="mail-imap-port" id="mail-imap-port"
						placeholder="<?php p($l->t('IMAP Port')); ?>"
						value="143" />
					<label for="mail-imap-port" class="infield"><?php p($l->t('IMAP Port')); ?></label>
				</p>
				<p class="groupmiddle">
					<input type="email" name="mail-imap-user" id="mail-imap-user"
						placeholder="<?php p($l->t('IMAP User')); ?>"
						value="" />
					<label for="mail-imap-user" class="infield"><?php p($l->t('IMAP User')); ?></label>
				</p>
				<p class="groupbottom">
					<input type="password" name="mail-imap-password" id="mail-imap-password"
						placeholder="<?php p($l->t('IMAP Password')); ?>" value=""
						required />
					<label for="mail-imap-password" class="infield"><?php p($l->t('IMAP Password')); ?></label>
				</p>

				<p class="grouptop">
					<input type="text" name="mail-smtp-host" id="mail-smtp-host"
						placeholder="<?php p($l->t('SMTP Host')); ?>"
						value="" />
					<label for="mail-smtp-host" class="infield"><?php p($l->t('SMTP Host')); ?></label>
				</p>
				<p class="groupmiddle" id="mail-smtp-ssl">
					<label for="mail-smtp-sslmode"><?php p($l->t('SMTP security')); ?></label>
					<select name="mail-smtp-sslmode" id="mail-smtp-sslmode" title="<?php p($l->t('SMTP security')); ?>">
						<option value="none"><?php p($l->t('none')); ?></option>
						<option value="ssl"><?php p($l->t('ssl')); ?></option>
						<option value="tls"><?php p($l->t('tls')); ?></option>
					</select>
				</p>
				<p class="groupmiddle">
					<input type="email" name="mail-smtp-port" id="mail-smtp-port"
						placeholder="<?php p($l->t('SMTP Port')); ?>"
						value="25" />
					<label for="mail-smtp-port" class="infield"><?php p($l->t('SMTP Port (default 25, ssl 465)')); ?></label>
				</p>
				<p class="groupmiddle">
					<input type="email" name="mail-smtp-user" id="mail-smtp-user"
						placeholder="<?php p($l->t('SMTP User')); ?>"
						value="" />
					<label for="mail-smtp-user" class="infield"><?php p($l->t('SMTP User')); ?></label>
				</p>
				<p class="groupbottom">
					<input type="password" name="mail-smtp-password" id="mail-smtp-password"
						placeholder="<?php p($l->t('SMTP Password')); ?>" value=""
						required />
					<label for="mail-smtp-password" class="infield"><?php p($l->t('SMTP Password')); ?></label>
				</p>
			</div>


			<img id="connect-loading" src="<?php print_unescaped(OCP\Util::imagePath('core', 'loading.gif')); ?>" style="display:none;" />
			<input type="submit" id="auto_detect_account" class="connect primary" value="<?php p($l->t('Connect')); ?>"/>
		</fieldset>
	</form>


</div>
