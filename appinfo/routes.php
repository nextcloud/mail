<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2013-2016 ownCloud Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
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
			'name' => 'page#mailboxStarred',
			'url' => '/box/starred/{id}',
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
			'name' => 'accounts#testAccountConnection',
			'url' => '/api/accounts/{id}/test',
			'verb' => 'GET'
		],
		[
			'name' => 'autoConfig#queryIspdb',
			'url' => '/api/autoconfig/ispdb/{host}/{email}',
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
			'name' => 'tags#delete',
			'url' => '/api/tags/{accountId}/delete/{id}',
			'verb' => 'DELETE'
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
			'name' => 'mailboxes#repair',
			'url' => '/api/mailboxes/{id}/repair',
			'verb' => 'POST'
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
			'name' => 'messages#getDkim',
			'url' => '/api/messages/{id}/dkim',
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
			'name' => 'messages#snooze',
			'url' => '/api/messages/{id}/snooze',
			'verb' => 'POST'
		],
		[
			'name' => 'messages#unSnooze',
			'url' => '/api/messages/{id}/unsnooze',
			'verb' => 'POST'
		],
		[
			'name' => 'messages#mdn',
			'url' => '/api/messages/{id}/mdn',
			'verb' => 'POST'
		],
		[
			'name' => 'messages#smartReply',
			'url' => '/api/messages/{messageId}/smartreply',
			'verb' => 'GET'
		],
		[
			'name' => 'messages#needsTranslation',
			'url' => '/api/messages/{messageId}/needsTranslation',
			'verb' => 'GET'
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
			'name' => 'settings#setEnabledLlmProcessing',
			'url' => '/api/settings/llm',
			'verb' => 'PUT'
		],
		[
			'name' => 'settings#setImportanceClassificationEnabledByDefault',
			'url' => '/api/settings/importance-classification-default',
			'verb' => 'PUT'
		],
		[
			'name' => 'settings#setLayoutMessageView',
			'url' => '/api/settings/layout-message-view',
			'verb' => 'PUT'
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
			'name' => 'internal_address#setAddress',
			'url' => '/api/internalAddress/{address}',
			'verb' => 'PUT'
		],
		[
			'name' => 'internal_address#removeAddress',
			'url' => '/api/internalAddress/{address}',
			'verb' => 'DELETE'
		],
		[
			'name' => 'internal_address#list',
			'url' => '/api/internalAddress',
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
			'name' => 'thread#snooze',
			'url' => '/api/thread/{id}/snooze',
			'verb' => 'POST'
		],
		[
			'name' => 'thread#unSnooze',
			'url' => '/api/thread/{id}/unsnooze',
			'verb' => 'POST'
		],
		[
			'name' => 'thread#summarize',
			'url' => '/api/thread/{id}/summary',
			'verb' => 'GET'
		],
		[
			'name' => 'thread#generateEventData',
			'url' => '/api/thread/{id}/eventdata',
			'verb' => 'GET'
		],
		[
			'name' => 'outbox#send',
			'url' => '/api/outbox/{id}',
			'verb' => 'POST'
		],
		[
			'name' => 'outbox#createFromDraft',
			'url' => '/api/outbox/from-draft/{id}',
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
		[
			'name' => 'list#unsubscribe',
			'url' => '/api/list/unsubscribe/{id}',
			'verb' => 'POST',
		],
		[
			'name' => 'drafts#move',
			'url' => '/api/drafts/move/{id}',
			'verb' => 'POST',
		],
		[
			'name' => 'outOfOffice#getState',
			'url' => '/api/out-of-office/{accountId}',
			'verb' => 'GET',
		],
		[
			'name' => 'outOfOffice#update',
			'url' => '/api/out-of-office/{accountId}',
			'verb' => 'POST',
		],
		[
			'name' => 'outOfOffice#followSystem',
			'url' => '/api/out-of-office/{accountId}/follow-system',
			'verb' => 'POST',
		],
		[
			'name' => 'followUp#checkMessageIds',
			'url' => '/api/follow-up/check-message-ids',
			'verb' => 'POST',
		],
		[
			'name' => 'textBlockShares#getTextBlockShares',
			'url' => '/api/textBlocks/{id}/shares',
			'verb' => 'GET',
		],
		[
			'name' => 'actionStep#findAllStepsForAction',
			'url' => '/api/action-step/{actionId}/steps',
			'verb' => 'GET'
		],
	],
	'resources' => [
		'accounts' => ['url' => '/api/accounts'],
		'aliases' => ['url' => '/api/accounts/{accountId}/aliases'],
		'autoComplete' => ['url' => '/api/autoComplete'],
		'drafts' => ['url' => '/api/drafts'],
		'localAttachments' => ['url' => '/api/attachments'],
		'mailboxes' => ['url' => '/api/mailboxes'],
		'messages' => ['url' => '/api/messages'],
		'outbox' => ['url' => '/api/outbox'],
		'preferences' => ['url' => '/api/preferences'],
		'smimeCertificates' => ['url' => '/api/smime/certificates'],
		'textBlock' => ['url' => '/api/textBlocks'],
		'textBlockShares' => ['url' => '/api/textBlockshares'],
		'quickActions' => ['url' => '/api/quick-actions'],
		'actionStep' => ['url' => '/api/action-step'],
	],
	'ocs' => [
		[
			'name' => 'messageApi#get',
			'url' => '/message/{id}',
			'verb' => 'GET',
		],
		[
			'name' => 'messageApi#getRaw',
			'url' => '/message/{id}/raw',
			'verb' => 'GET',
		],
		[
			'name' => 'messageApi#getAttachment',
			'url' => '/message/{id}/attachment/{attachmentId}',
			'verb' => 'GET',
		],
	],
];
