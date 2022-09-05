<?php
/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Jan-Christoph Borchardt <hey@jancborchardt.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
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
	<div class="update">
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
