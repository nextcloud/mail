<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2014-2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
style('mail', 'redirect');
?>
<?php /** @var array $_ */ ?>
<?php if (isset($_['authorizedRedirect']) && ($_['authorizedRedirect'])): ?>
	<?php script('mail', 'autoredirect'); ?>
	<div class="error">
		<div class="icon-loading-dark"
		     style="height: 60px;"></div>
		<p>
			<a href="<?php p($_['url']) ?>" rel="noreferrer" id="redirectLink"
			   style="font-weight: 300 !important">
				<h2><?php p($l->t('Forwarding to %s', [$_['urlHost']])); ?></h2>
				<?php p($l->t('Click here if you are not automatically redirected within the next few seconds.')); ?>
			</a>
		</p>
	</div>
<?php else: ?>
	<div class="update guest-box">
		<h2><?php p($l->t('Redirect')); ?></h2>
		<p><?php p($l->t('The link leads to %s', [$_['urlHost']])); ?></p>
		<p class="infogroup"><?php print_unescaped($l->t('If you do not want to visit that page, you can return to <a href="%s">Mail</a>.',
			[$_['mailURL']]));
	?></p>

		<p>
			<a href="<?php p($_['url']) ?>" class="button primary" rel="noreferrer" id="redirectLink"><?php p($l->t('Continue to %s', [$_['urlHost']])); ?></a>
		</p>
	</div>
<?php endif; ?>
