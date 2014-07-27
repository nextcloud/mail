<?php /** @var array $_ */?>
<div id="app">
	<div id="app-content">
		<div class="error">
			<legend><strong><?php p($l->t('Redirect Warning'));?></strong></legend>
			<p><?php p($l->t('The previous page is sending you to %s.', array($_['url']))); ?></p>
			<p><?php print_unescaped($l->t('If you do not want to visit that page, you can return to <a href="%s">the mail app</a>.', array($_['mailURL']))); ?></p>

			<br/>
			<a href="<?php p($_['url']) ?>" class="button" target="_blank" rel="noreferrer"><?php p($l->t('Click here to visit the website.'));?></a>
		</div>
	</div>
</div>
