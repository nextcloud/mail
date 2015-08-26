<?php
/**
 * Copyright (c) 2013 Thomas Müller
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

$app = new \OCA\Mail\AppInfo\Application();
$app->registerRoutes($this,
	[
		'routes' => [
			['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
			['name' => 'page#compose', 'url' => '/compose', 'verb' => 'GET'],
			['name' => 'accounts#send', 'url' => '/accounts/{accountId}/send', 'verb' => 'POST'],
			['name' => 'accounts#draft', 'url' => '/accounts/{accountId}/draft', 'verb' => 'POST'],
			['name' => 'accounts#autoComplete', 'url' => '/accounts/autoComplete', 'verb' => 'GET'],
			[
				'name' => 'messages#downloadAttachment',
				'url' => '/accounts/{accountId}/folders/{folderId}/messages/{messageId}/attachment/{attachmentId}',
				'verb' => 'GET'],
			[
				'name' => 'messages#saveAttachment',
				'url' => '/accounts/{accountId}/folders/{folderId}/messages/{messageId}/attachment/{attachmentId}',
				'verb' => 'POST'],
			[
				'name' => 'messages#getHtmlBody',
				'url' => '/accounts/{accountId}/folders/{folderId}/messages/{messageId}/html',
				'verb' => 'GET'],
			[
				'name' => 'messages#setFlags',
				'url' => '/accounts/{accountId}/folders/{folderId}/messages/{messageId}/flags',
				'verb' => 'PUT'],
			[
				'name' => 'proxy#redirect',
				'url' => '/redirect',
				'verb' => 'GET'],
			[
				'name' => 'proxy#proxy',
				'url' => '/proxy',
				'verb' => 'GET'],
			[
				'name' => 'folders#detectChanges',
				'url' => '/accounts/{accountId}/folders/detectChanges',
				'verb' => 'POST'],
		],
		'resources' => [
			'accounts' => ['url' => '/accounts'],
			'folders' => ['url' => '/accounts/{accountId}/folders'],
			'messages' => ['url' => '/accounts/{accountId}/folders/{folderId}/messages'],
		]
	]);
