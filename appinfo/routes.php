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
		[
			'name' => 'page#index',
			'url' => '/',
			'verb' => 'GET'
		],
		[
			'name' => 'page#compose',
			'url' => '/compose',
			'verb' => 'GET'
		],
		[
			'name' => 'accounts#send',
			'url' => '/api/accounts/{accountId}/send',
			'verb' => 'POST'
		],
		[
			'name' => 'accounts#draft',
			'url' => '/api/accounts/{accountId}/draft',
			'verb' => 'POST'
		],
		[
			'name' => 'folders#sync',
			'url' => '/api/accounts/{accountId}/folders/{folderId}/sync',
			'verb' => 'GET'
		],
		[
			'name' => 'messages#downloadAttachment',
			'url' => '/api/accounts/{accountId}/folders/{folderId}/messages/{messageId}/attachment/{attachmentId}',
			'verb' => 'GET'
		],
		[
			'name' => 'messages#saveAttachment',
			'url' => '/api/accounts/{accountId}/folders/{folderId}/messages/{messageId}/attachment/{attachmentId}',
			'verb' => 'POST'
		],
		[
			'name' => 'messages#getHtmlBody',
			'url' => '/api/accounts/{accountId}/folders/{folderId}/messages/{messageId}/html',
			'verb' => 'GET'
		],
		[
			'name' => 'messages#setFlags',
			'url' => '/api/accounts/{accountId}/folders/{folderId}/messages/{messageId}/flags',
			'verb' => 'PUT'
		],
		[
			'name' => 'messages#move',
			'url' => '/api/accounts/{accountId}/folders/{folderId}/messages/{id}/move',
			'verb' => 'POST'
		],
		[
			'name' => 'avatars#url',
			'url' => '/avatars/url',
			'verb' => 'GET'
		],
		[
			'name' => 'avatars#file',
			'url' => '/avatars/file',
			'verb' => 'GET'
		],
		[
			'name' => 'proxy#redirect',
			'url' => '/redirect',
			'verb' => 'GET'
		],
		[
			'name' => 'proxy#proxy',
			'url' => '/proxy',
			'verb' => 'GET'
		],
	],
	'resources' => [
		'autoComplete' => ['url' => '/api/autoComplete'],
		'localAttachments' => ['url' => '/api/attachments'],
		'accounts' => ['url' => '/api/accounts'],
		'folders' => ['url' => '/api/accounts/{accountId}/folders'],
		'messages' => ['url' => '/api/accounts/{accountId}/folders/{folderId}/messages'],
		'aliases' => ['url' => '/api/accounts/{accountId}/aliases'],
	]
]);
