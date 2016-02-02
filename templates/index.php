<?php

// TODO: remove DEBUG constant check once minimum oc
// core version >= 8.2, see https://github.com/owncloud/core/pull/18510
$debug = (defined('DEBUG') && DEBUG)
	|| \OC::$server->getConfig()->getSystemValue('debug', false);

style('mail','mail');
style('mail','mobile');
script('mail','vendor/autosize/jquery.autosize');
script('mail', 'vendor/jQuery-Storage-API/jquery.storageapi');
script('mail', 'vendor/jquery-visibility/jquery-visibility');
script('mail', 'vendor/requirejs/require');
script('mail','searchproxy');
if ($debug) {
	// Load JS dependencies asynchronously as specified in require_config.js
	script('mail', 'require_config');
} else {
	// Load optimzed requirejs dependencies in one single file
	script('mail', 'mail.min');
}
?>

<div id="user-displayname"
     style="display: none"><?php p(\OCP\User::getDisplayName(\OCP\User::getUser())); ?></div>
<div id="user-email"
     style="display: none"><?php p(\OCP\Config::getUserValue(\OCP\User::getUser(), 'settings', 'email', '')); ?></div>
<div id="app">
	<div id="app-navigation" class="icon-loading">
		<ul>
			<li id="mail-new-message-fixed">
				<input type="button" id="mail_new_message" class="icon-add"
				       value="<?php p($l->t('New message')); ?>" style="display: none">
			</li>
			<li id="app-navigation-accounts"></li>
		</ul>
		<div id="app-settings">
			<div id="app-settings-header">
				<button class="settings-button"
					data-apps-slide-toggle="#app-settings-content"></button>
			</div>
			<div id="app-settings-content"></div>
		</div>
	</div>
	<div id="app-content">
		<div id="mail-messages"></div>
		<div id="setup" class="hidden" ></div>
		<div id="mail-message" class="icon-loading hidden-mobile"></div>
	</div>
</div>
