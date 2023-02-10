<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Richard Steinmetz <richard@steinmetz.cloud>
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
			'name' => 'page#filteredThread',
			'url' => '/box/{filter}/{mailboxId}/thread/{id}',
			'verb' => 'GET'
		],
		[
			'name' => 'page#draft',
			'url' => '/box/{mailboxId}/thread/new/{draftId}',
			'verb' => 'GET'
		],
		[
			'name' => 'page#filteredDraft',
			'url' => '/box/{filter}/{mailboxId}/thread/new/{draftId}',
			'verb' => 'GET'
		],
		[
			'name' => 'page#outbox',
			'url' => '/outbox',
			'verb' => 'GET'
		],
		[
			'name' => 'page#outboxMessage',
			'url' => '/outbox/{messageId}',
			'verb' => 'GET'
		],
		[
			'name' => 'page#compose',
			'url' => '/compose',
			'verb' => 'GET'
		],
		[
			'name' => 'page#mailto',
			'url' => '/mailto',
			'verb' => 'GET'
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
			'name' => 'accounts#updateSmimeCertificate',
			'url' => '/api/accounts/{id}/smime-certificate',
			'verb' => 'PUT'
		],
		[
			'name' => 'accounts#getQuota',
			'url' => '/api/accounts/{id}/quota',
			'verb' => 'GET'
		],
		[
			'name' => 'autoConfig#queryIspdb',
			'url' => '/api/autoconfig/ispdb/{email}',
			'verb' => 'GET',
		],
		[
			'name' => 'autoConfig#queryMx',
			'url' => '/api/autoconfig/mx/{email}',
			'verb' => 'GET',
		],
		[
			'name' => 'autoConfig#testConnectivity',
			'url' => '/api/autoconfig/test',
			'verb' => 'GET',
		],
		[
			'name' => 'tags#create',
			'url' => '/api/tags',
			'verb' => 'POST'
		],
		[
			'name' => 'tags#update',
			'url' => '/api/tags/{id}',
			'verb' => 'PUT'
		],
		[
			'name' => 'aliases#updateSignature',
			'url' => '/api/accounts/{accountId}/aliases/{id}/signature',
			'verb' => 'PUT'
		],
		[
			'name' => 'contactIntegration#autoComplete',
			'url' => '/api/contactIntegration/autoComplete/{term}',
			'verb' => 'GET'
		],
		[
			'name' => 'contactIntegration#addMail',
			'url' => '/api/contactIntegration/add',
			'verb' => 'PUT'
		],
		[
			'name' => 'contactIntegration#newContact',
			'url' => '/api/contactIntegration/new',
			'verb' => 'PUT'
		],
		[
			'name' => 'contactIntegration#match',
			'url' => '/api/contactIntegration/match/{mail}',
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
			'name' => 'mailboxes#clearMailbox',
			'url' => '/api/mailboxes/{id}/clear',
			'verb' => 'POST'
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
			'name' => 'messages#downloadAttachments',
			'url' => '/api/messages/{id}/attachments',
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
			'name' => 'messages#getItineraries',
			'url' => '/api/messages/{id}/itineraries',
			'verb' => 'GET'
		],
		[
			'name' => 'messages#getSource',
			'url' => '/api/messages/{id}/source',
			'verb' => 'GET'
		],
		[
			'name' => 'messages#export',
			'url' => '/api/messages/{id}/export',
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
			'name' => 'messages#setTag',
			'url' => '/api/messages/{id}/tags/{imapLabel}',
			'verb' => 'PUT'
		],
		[
			'name' => 'messages#removeTag',
			'url' => '/api/messages/{id}/tags/{imapLabel}',
			'verb' => 'DELETE'
		],
		[
			'name' => 'messages#move',
			'url' => '/api/messages/{id}/move',
			'verb' => 'POST'
		],
		[
			'name' => 'messages#mdn',
			'url' => '/api/messages/{id}/mdn',
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
			'name' => 'settings#index',
			'url' => '/api/settings/provisioning',
			'verb' => 'GET'
		],
		[
			'name' => 'settings#createProvisioning',
			'url' => '/api/settings/provisioning',
			'verb' => 'POST'
		],
		[
			'name' => 'settings#updateProvisioning',
			'url' => '/api/settings/provisioning/{id}',
			'verb' => 'POST'
		],
		[
			'name' => 'settings#provision',
			'url' => '/api/settings/provisioning/all',
			'verb' => 'PUT'
		],
		[
			'name' => 'settings#deprovision',
			'url' => '/api/settings/provisioning/{id}',
			'verb' => 'DELETE'
		],
		[
			'name' => 'settings#setAntiSpamEmail',
			'url' => '/api/settings/antispam',
			'verb' => 'POST'
		],
		[
			'name' => 'settings#deleteAntiSpamEmail',
			'url' => '/api/settings/antispam',
			'verb' => 'DELETE'
		],
		[
			'name' => 'settings#setAllowNewMailAccounts',
			'url' => '/api/settings/allownewaccounts',
			'verb' => 'POST'
		],
		[
			'name' => 'trusted_senders#setTrusted',
			'url' => '/api/trustedsenders/{email}',
			'verb' => 'PUT'
		],
		[
			'name' => 'trusted_senders#removeTrust',
			'url' => '/api/trustedsenders/{email}',
			'verb' => 'DELETE'
		],
		[
			'name' => 'trusted_senders#list',
			'url' => '/api/trustedsenders',
			'verb' => 'GET'
		],
		[
			'name' => 'sieve#updateAccount',
			'url' => '/api/sieve/account/{id}',
			'verb' => 'PUT'
		],
		[
			'name' => 'sieve#getActiveScript',
			'url' => '/api/sieve/active/{id}',
			'verb' => 'GET'
		],
		[
			'name' => 'sieve#updateActiveScript',
			'url' => '/api/sieve/active/{id}',
			'verb' => 'PUT'
		],
		[
			'name' => 'thread#delete',
			'url' => '/api/thread/{id}',
			'verb' => 'DELETE'
		],
		[
			'name' => 'thread#move',
			'url' => '/api/thread/{id}',
			'verb' => 'POST'
		],
		[
			'name' => 'outbox#send',
			'url' => '/api/outbox/{id}',
			'verb' => 'POST'
		],
		[
			'name' => 'googleIntegration#configure',
			'url' => '/api/integration/google',
			'verb' => 'POST',
		],
		[
			'name' => 'googleIntegration#unlink',
			'url' => '/api/integration/google',
			'verb' => 'DELETE',
		],
		[
			'name' => 'googleIntegration#oauthRedirect',
			'url' => '/integration/google-auth',
			'verb' => 'GET',
		],
		[
			'name' => 'microsoftIntegration#configure',
			'url' => '/api/integration/microsoft',
			'verb' => 'POST',
		],
		[
			'name' => 'microsoftIntegration#unlink',
			'url' => '/api/integration/microsoft',
			'verb' => 'DELETE',
		],
		[
			'name' => 'microsoftIntegration#oauthRedirect',
			'url' => '/integration/microsoft-auth',
			'verb' => 'GET',
		],
	],
	'resources' => [
		'accounts' => ['url' => '/api/accounts'],
		'aliases' => ['url' => '/api/accounts/{accountId}/aliases'],
		'autoComplete' => ['url' => '/api/autoComplete'],
		'localAttachments' => ['url' => '/api/attachments'],
		'mailboxes' => ['url' => '/api/mailboxes'],
		'messages' => ['url' => '/api/messages'],
		'outbox' => ['url' => '/api/outbox'],
		'preferences' => ['url' => '/api/preferences'],
		'smimeCertificates' => ['url' => '/api/smime/certificates'],
	]
];
