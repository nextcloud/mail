<?php /** @var array $_ */?>
<div id="app">
	<div id="app-content">
		<?php if (isset($_['authorizedRedirect']) && ($_['authorizedRedirect'])): ?>
			<legend>
				<p id="message" style="padding-top:25px;">
					<img class="float-spinner" src="<?php p(\OCP\Util::imagePath('core', 'loading-dark.gif'));?>"/>
					<a href="<?php p($_['url']) ?>" rel="noreferrer" id="redirectLink">
						<span id="messageText"><?php p($l->t('Forwarding you to %s', array($_['urlHost'])));?></span>
					</a>
				</p>
			</legend>
		<?php else: ?>
			<div class="error">
				<legend><strong><?php p($l->t('Redirect Warning'));?></strong></legend>
				<p><?php p($l->t('The previous page is sending you to %s.', array($_['urlHost']))); ?></p>
				<p><?php print_unescaped($l->t('If you do not want to visit that page, you can return to <a href="%s">the mail app</a>.', array($_['mailURL']))); ?></p>

				<br/>
				<a href="<?php p($_['url']) ?>" class="button" rel="noreferrer" id="redirectLink"><?php p($l->t('Click here to visit the website.'));?></a>
			</div>
		<?php endif; ?>
	</div>
</div>
