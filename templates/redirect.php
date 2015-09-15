<?php /** @var array $_ */?>
<?php if (isset($_['authorizedRedirect']) && ($_['authorizedRedirect'])): ?>
	<?php script('mail', 'autoredirect'); ?>
	<div class="error" style="text-align: center;">
		<img src="<?php p(\OCP\Util::imagePath('core', 'loading-dark.gif'));?>"
			style="margin: 0 auto;" />
		<p>
			<a href="<?php p($_['url']) ?>" rel="noreferrer" id="redirectLink"
				style="color: #fff !important;">
				<?php p($l->t('Forwarding you to %s - click here if you are not automatically redirected within the next few seconds.', array($_['urlHost'])));?>
			</a>
		</p>
	</div>
<?php else: ?>
	<div class="error">
		<legend><strong><?php p($l->t('Redirect Warning'));?></strong></legend>
		<p><?php p($l->t('The previous page is sending you to %s.', array($_['urlHost']))); ?></p>
		<p><?php print_unescaped($l->t('If you do not want to visit that page, you can return to <a href="%s">the mail app</a>.', array($_['mailURL']))); ?></p>

		<br/>
		<a href="<?php p($_['url']) ?>" class="button" rel="noreferrer" id="redirectLink"><?php p($l->t('Click here to visit the website.'));?></a>
	</div>
<?php endif; ?>
