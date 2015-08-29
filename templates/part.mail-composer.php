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
			<input type="text" name="to"
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
		</div>
		<div class="new-message-attachments">
		</div>
	</div>
</script>