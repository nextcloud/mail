<script id="message-list-template" type="text/x-handlebars-template">
	<input type="button" id="load-new-mail-messages" value="<?php p($l->t('Check messages …')); ?>">
	<div id="emptycontent" style="display: none;">
		<div class="icon-mail"></div>
		<h2><?php p($l->t('No messages in this folder!')); ?></h2>
	</div>
	<div id="mail-message-list"></div>
	<input type="button" id="load-more-mail-messages" value="<?php p($l->t('Load more …')); ?>">
</script>