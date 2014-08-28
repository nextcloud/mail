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
			array('name' => 'page#compose', 'url' => '/compose', 'verb' => 'GET'),
			array('name' => 'settings#index', 'url' => '/settings', 'verb' => 'GET'),
			array('name' => 'accounts#send', 'url' => '/accounts/{accountId}/send', 'verb' => 'POST'),
			array('name' => 'accounts#autoComplete', 'url' => '/accounts/autoComplete', 'verb' => 'GET'),
			array(
				'name' => 'messages#downloadAttachment',
				'url' => '/accounts/{accountId}/folders/{folderId}/messages/{messageId}/attachment/{attachmentId}',
				'verb' => 'GET'),
			array(
				'name' => 'messages#saveAttachment',
				'url' => '/accounts/{accountId}/folders/{folderId}/messages/{messageId}/attachment/{attachmentId}',
				'verb' => 'POST'),
			array(
				'name' => 'messages#getHtmlBody',
				'url' => '/accounts/{accountId}/folders/{folderId}/messages/{messageId}/html',
				'verb' => 'GET'),
			array(
				'name' => 'messages#toggleStar',
				'url' => '/accounts/{accountId}/folders/{folderId}/messages/{messageId}/toggleStar',
				'verb' => 'POST'),
			array(
				'name' => 'proxy#redirect',
				'url' => '/redirect',
				'verb' => 'GET'),
			array(
				'name' => 'proxy#proxy',
				'url' => '/proxy',
				'verb' => 'GET'),
		),
		'resources' => array(
			'accounts' => array('url' => '/accounts'),
			'folders' => array('url' => '/accounts/{accountId}/folders'),
			'messages' => array('url' => '/accounts/{accountId}/folders/{folderId}/messages'),
		)
	));
