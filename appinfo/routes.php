<?php

declare(strict_types=1);

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
return [
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
			'name' => 'accounts#patchAccount',
			'url' => '/api/accounts/{accountId}',
			'verb' => 'PATCH'
		],
		[
			'name' => 'accounts#updateSignature',
			'url' => '/api/accounts/{accountId}/signature',
			'verb' => 'PUT'
		],
		[
			'name' => 'folders#sync',
			'url' => '/api/accounts/{accountId}/folders/{folderId}/sync',
			'verb' => 'POST'
		],
		[
			'name' => 'folders#clearCache',
			'url' => '/api/accounts/{accountId}/folders/{folderId}/sync',
			'verb' => 'DELETE'
		],
		[
			'name' => 'folders#markAllAsRead',
			'url' => '/api/accounts/{accountId}/folders/{folderId}/read',
			'verb' => 'POST'
		],
		[
			'name' => 'folders#stats',
			'url' => '/api/accounts/{accountId}/folders/{folderId}/stats',
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
			'name' => 'messages#getBody',
			'url' => '/api/accounts/{accountId}/folders/{folderId}/messages/{messageId}/body',
			'verb' => 'GET'
		],
		[
			'name' => 'messages#getSource',
			'url' => '/api/accounts/{accountId}/folders/{folderId}/messages/{messageId}/source',
			'verb' => 'GET'
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
			'url' => '/api/avatars/url/{email}',
			'verb' => 'GET'
		],
		[
			'name' => 'avatars#image',
			'url' => '/api/avatars/image/{email}',
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
		[
			'name' => 'settings#provisioning',
			'url' => '/api/settings/provisioning',
			'verb' => 'POST'
		],
		[
			'name' => 'settings#deprovision',
			'url' => '/api/settings/provisioning',
			'verb' => 'DELETE'
		],
	],
	'resources' => [
		'accounts' => ['url' => '/api/accounts'],
		'aliases' => ['url' => '/api/accounts/{accountId}/aliases'],
		'autoComplete' => ['url' => '/api/autoComplete'],
		'folders' => ['url' => '/api/accounts/{accountId}/folders'],
		'localAttachments' => ['url' => '/api/attachments'],
		'messages' => ['url' => '/api/accounts/{accountId}/folders/{folderId}/messages'],
		'preferences' => ['url' => '/api/preferences'],
	]
];
