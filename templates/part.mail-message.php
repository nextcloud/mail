<script id="mail-message-template" type="text/x-handlebars-template">
	<div id="mail-message-close" class="icon-close"></div>
	<div id="mail-message-header" class="section">
		<h2 title="{{subject}}">{{subject}}</h2>
		<p class="transparency">
			{{printAddressList fromList}}
			{{#if toList}}
			<?php p($l->t('to')); ?>
			{{printAddressList toList}}
			{{/if}}
			{{#if ccList}}
			(<?php p($l->t('cc')); ?> {{printAddressList ccList}})
			{{/if}}
		</p>
	</div>
	<div class="mail-message-body">
		<div id="mail-content">
			{{#if hasHtmlBody}}
			<div class="icon-loading">
				<iframe src="{{htmlBodyUrl}}" seamless>
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
					<button class="icon-folder attachment-save-to-cloud"><?php p($l->t('Save to Files')); ?></button>
				</li>
			</ul>
			{{/if}}
			{{#if attachments}}
			<ul>
				{{#each attachments}}
				<li class="mail-message-attachment" data-message-id="{{messageId}}" data-attachment-id="{{id}}" data-attachment-mime="{{mime}}">
					<a class="button icon-download attachment-download" href="{{downloadUrl}}" title="<?php p($l->t('Download attachment')); ?>"></a>
					<button class="icon-folder attachment-save-to-cloud" title="<?php p($l->t('Save to Files')); ?>"></button>
					<img class="attachment-icon" src="{{mimeUrl}}" />
					{{fileName}} <span class="attachment-size">({{humanFileSize size}})</span>
				</li>
				{{/each}}
			</ul>
			<p>
				<button data-message-id="{{id}}" class="icon-folder attachments-save-to-cloud"><?php p($l->t('Save all to Files')); ?></button>
			</p>
			{{/if}}
		</div>
		<div id="reply-composer"></div>
		<input type="button" id="forward-button" value="<?php p($l->t('Forward')); ?>">
	</div>
</script>