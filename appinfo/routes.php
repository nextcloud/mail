<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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

$app = new \OCA\Mail\AppInfo\Application();
$app->registerRoutes($this,
	[
		'routes' => [
			['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
			['name' => 'page#compose', 'url' => '/compose', 'verb' => 'GET'],
			['name' => 'accounts#edit', 'url' => '/accounts/{accountId}', 'verb' => 'PUT'],
			['name' => 'accounts#send', 'url' => '/accounts/{accountId}/send', 'verb' => 'POST'],
			['name' => 'accounts#draft', 'url' => '/accounts/{accountId}/draft', 'verb' => 'POST'],
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
			'autoComplete' => ['url' => '/autoComplete'],
			'accounts' => ['url' => '/accounts'],
			'folders' => ['url' => '/accounts/{accountId}/folders'],
			'messages' => ['url' => '/accounts/{accountId}/folders/{folderId}/messages'],
			'aliases' => ['url' => '/accounts/{accountId}/aliases'],
		]
	]);
