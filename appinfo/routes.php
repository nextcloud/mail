<?php
/**
 * Copyright (c) 2013 Thomas Müller
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

$app = new \OCA\Mail\AppInfo\Application();
$app->registerRoutes($this,
	array(
		'routes' => array(
			array('name' => 'page#index', 'url' => '/', 'verb' => 'GET'),
		),
		'resources' => array(
			'accounts' => array('url' => '/accounts'),
			'folders' => array('url' => '/accounts/{accountId}/folders'),
			'messages' => array('url' => '/accounts/{accountId}/folders/{folderId}/messages'),
		)
	));

// oC JS config
$this->create('mail_editor', 'js/mail_editor.js')
	->actionInclude('mail/js/mail_editor.php');

