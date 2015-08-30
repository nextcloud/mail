<?php
style('mail','mail');
style('mail','mobile');
script('mail','vendor/autosize/jquery.autosize');
script('mail', 'send-mail');
?>

<script id="mail-account-manager" type="text/x-handlebars-template">
	<select class="mail_account">
		{{#each this}}
		<option value="{{accountId}}"><?php p($l->t('from')); ?> {{name}} &lt;{{emailAddress}}&gt;</option>
		{{/each}}
	</select>
</script>

<script id="mail-attachment-template" type="text/x-handlebars-template">
	<span><%= displayName %></span><div class="new-message-attachments-action svg icon-delete"></div>
</script>

<script id="mail-composer" type="text/x-handlebars-template">
	<div class="message-composer">
		{{#unless isReply}}
		<select class="mail-account">
			{{#each aliases}}
			<option value="{{accountId}}"><?php p($l->t('from')); ?> {{name}} &lt;{{emailAddress}}&gt;</option>
			{{/each}}
		</select>
		{{/unless}}
		<div class="composer-fields">
			<a href="#" class="composer-cc-bcc-toggle transparency 
                                {{#ifHasCC replyCc replyCcList}}
				hidden
				{{/ifHasCC}}"><?php p($l->t('+ cc/bcc')); ?></a>
			<input type="text" name="to"p($_['mailto'])
                            {{#if replyToList}}
                            value="{{printAddressListPlain replyToList}}"
                            {{else}}
                            value="{{to}}"
                            {{/if}}
                            class="to recipient-autocomplete" />
			<label class="to-label" for="to" class="transparency"><?php p($l->t('to')); ?></label>
			<div class="composer-cc-bcc
                            {{#unlessHasCC replyCc replyCcList}}
                            hidden
                            {{/unlessHasCC}}">
				<input type="text" name="cc"
                                    {{#if replyCc}}
                                    value="{{replyCc}}"
                                    {{else}}
                                        {{#if replyCcList}}
                                        value="{{printAddressListPlain replyCcList}}"
                                        {{else}}
                                        value="{{cc}}"
                                        {{/if}}
                                    {{/if}}
                                    class="cc recipient-autocomplete" />
				<label for="cc" class="cc-label transparency"><?php p($l->t('cc')); ?></label>
				<input type="text" name="bcc" value="{{bcc}}" class="bcc recipient-autocomplete" />
				<label for="bcc" class="bcc-label transparency"><?php p($l->t('bcc')); ?></label>
			</div>
			{{#unless isReply}}
			<input type="text" name="subject" value="{{subject}}" class="subject"
				placeholder="<?php p($l->t('Subject')); ?>" />
			{{/unless}}
			<textarea name="body" class="message-body
						{{#if isReply}} reply{{/if}}"
				placeholder="<?php p($l->t('Message …')); ?>">{{message}}</textarea>
			<input class="submit-message send primary" type="submit" value="{{submitButtonTitle}}" disabled>
			<div class="new-message-attachments">
			</div>
		</div>
		<div id="nav-buttons" class="hidden">
			<input type="button" id="nav-to-mail" value="<?php p($l->t('Open Mail App')); ?>">
			<input type="button" id="back-in-time" value="<?php p($l->t('Back to website')); ?>">
		</div>
	</div>
</script>
<script id="mail-attachments-template" type="text/x-handlebars-template">
	<ul></ul>
	<input type="button" id="mail_new_attachment" value="<?php p($l->t('Add attachment from Files')); ?>">
</script>

<div id="app" data-mailto="<?php p($_['mailto']) ?>">
	<div id="app-content" class="compose">
	</div>
</div>
