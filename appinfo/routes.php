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
			'name' => 'page#setup',
			'url' => '/setup',
			'verb' => 'GET'
		],
		[
			'name' => 'page#mailbox',
			'url' => '/box/{id}',
			'verb' => 'GET'
		],
		[
			'name' => 'page#thread',
			'url' => '/box/{mailboxId}/thread/{id}',
			'verb' => 'GET'
		],
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
			'url' => '/api/accounts/{id}/send',
			'verb' => 'POST'
		],
		[
			'name' => 'accounts#draft',
			'url' => '/api/accounts/{id}/draft',
			'verb' => 'POST'
		],
		[
			'name' => 'accounts#patchAccount',
			'url' => '/api/accounts/{id}',
			'verb' => 'PATCH'
		],
		[
			'name' => 'accounts#updateSignature',
			'url' => '/api/accounts/{id}/signature',
			'verb' => 'PUT'
		],
		[
			'name' => 'accounts#getQuota',
			'url' => '/api/accounts/{id}/quota',
			'verb' => 'GET'
		],
		[
			'name' => 'mailboxes#patch',
			'url' => '/api/mailboxes/{id}',
			'verb' => 'PATCH'
		],
		[
			'name' => 'mailboxes#sync',
			'url' => '/api/mailboxes/{id}/sync',
			'verb' => 'POST'
		],
		[
			'name' => 'mailboxes#clearCache',
			'url' => '/api/mailboxes/{id}/sync',
			'verb' => 'DELETE'
		],
		[
			'name' => 'mailboxes#markAllAsRead',
			'url' => '/api/mailboxes/{id}/read',
			'verb' => 'POST'
		],
		[
			'name' => 'mailboxes#stats',
			'url' => '/api/mailboxes/{id}/stats',
			'verb' => 'GET'
		],
		[
			'name' => 'messages#downloadAttachment',
			'url' => '/api/messages/{id}/attachment/{attachmentId}',
			'verb' => 'GET'
		],
		[
			'name' => 'messages#saveAttachment',
			'url' => '/api/messages/{id}/attachment/{attachmentId}',
			'verb' => 'POST'
		],
		[
			'name' => 'messages#getBody',
			'url' => '/api/messages/{id}/body',
			'verb' => 'GET'
		],
		[
			'name' => 'messages#getSource',
			'url' => '/api/messages/{id}/source',
			'verb' => 'GET'
		],
		[
			'name' => 'messages#getHtmlBody',
			'url' => '/api/messages/{id}/html',
			'verb' => 'GET'
		],
		[
			'name' => 'messages#getThread',
			'url' => '/api/messages/{id}/thread',
			'verb' => 'GET'
		],
		[
			'name' => 'messages#setFlags',
			'url' => '/api/messages/{id}/flags',
			'verb' => 'PUT'
		],
		[
			'name' => 'messages#move',
			'url' => '/api/messages/{id}/move',
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
		'localAttachments' => ['url' => '/api/attachments'],
		'mailboxes' => ['url' => '/api/mailboxes'],
		'messages' => ['url' => '/api/messages'],
		'preferences' => ['url' => '/api/preferences'],
	]
];
