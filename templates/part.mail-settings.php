<script id="mail-settings-template" type="text/x-handlebars-template">
	<div id="mailsettings">
		<ul id="settings-accounts" class="mailaccount-list">
		</ul>
		<input id="new_mail_account" type="submit" value="<?php p($l->t('Add mail account')); ?>" class="new-button">

		<p class="app-settings-hint">
			<?php print_unescaped($l->t('Looking to encrypt your emails? Install the <a href="https://www.mailvelope.com/" target="_blank">Mailvelope browser extension</a>!')); ?>
		</p>
	</div>
</script>