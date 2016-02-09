<?php
/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Jan-Christoph Borchardt <hey@jancborchardt.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * ownCloud - Mail
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
		<h2><?php p($l->t('Redirect warning'));?></h2>
		<p><?php p($l->t('The previous page is sending you to %s', array($_['urlHost']))); ?></p>
		<p><?php print_unescaped($l->t('If you do not want to visit that page, you can return to <a href="%s">the mail app</a>.', array($_['mailURL']))); ?></p>

		<br/>
		<a href="<?php p($_['url']) ?>" class="button" rel="noreferrer" id="redirectLink"><?php p($l->t('Continue to %s', array($_['urlHost']))); ?></a>
	</div>
<?php endif; ?>
