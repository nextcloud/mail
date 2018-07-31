<?php
/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author colm <mail@colm.be>
 * @author Damien <dcosset@hotmail.fr>
 * @author Jan-Christoph Borchardt <hey@jancborchardt.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Thomas Imbreckx <zinks@iozero.be>
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
style('mail', 'mail');
style('mail', 'mobile');
script('mail', 'searchproxy');
script('mail', 'build/build');
?>

<input type="hidden" id="debug-mode" value="<?php p($_['debug'] ? 'true' : 'false'); ?>">
<input type="hidden" id="config-installed-version" value="<?php p($_['app-version']); ?>">
<input type="hidden" id="serialized-accounts" value="<?php p($_['accounts']); ?>">
<input type="hidden" id="external-avatars" value="<?php p($_['external-avatars']); ?>">

<div id="app-navigation" class="icon-loading">
	<div id="mail-new-message-fixed"
		 class="app-navigation-new">
	</div>
	<ul id="usergrouplist"></ul>
	<div id="app-settings">
		<div id="app-settings-header">
			<button class="settings-button"
					data-apps-slide-toggle="#app-settings-content"><?php p($l->t('Settings'));?></button>
		</div>
		<div id="app-settings-content"></div>
	</div>
</div>
<div id="app-content">
	<div id="app-navigation-toggle" class="icon-menu"></div>
	<div id="app-content-wrapper"></div>
</div>
<div id="user-displayname"
     style="display: none"><?php p($_['prefill_displayName']); ?></div>
<div id="user-email"
     style="display: none"><?php p($_['prefill_email']); ?></div>
