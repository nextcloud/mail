<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\AuditLog;

/**
 * Operations Mail writes to the admin audit log, in the form `app.entity.action`
 *
 * The values are matched by log consumers (filters, alert rules, SIEM imports),
 * so changing one is a breaking change for them. Case names may be renamed.
 */
enum Operation: string {
	// Accounts
	case AccountCreated = 'mail.account.created';
	case AccountUpdated = 'mail.account.updated';
	case AccountDeleted = 'mail.account.deleted';
	case DelegationCreated = 'mail.delegation.created';
	case DelegationDeleted = 'mail.delegation.deleted';

	// Sieve
	case SieveAccountUpdated = 'mail.sieve_account.updated';
	case SieveScriptUpdated = 'mail.sieve_script.updated';

	// S/MIME
	case SmimeCertificateCreated = 'mail.smime_certificate.created';
	case SmimeCertificateDeleted = 'mail.smime_certificate.deleted';

	// Text blocks
	case TextBlockShareCreated = 'mail.text_block_share.created';
	case TextBlockShareDeleted = 'mail.text_block_share.deleted';

	// Provisioning
	case ProvisioningConfigCreated = 'mail.provisioning_config.created';
	case ProvisioningConfigUpdated = 'mail.provisioning_config.updated';
	case ProvisioningConfigDeleted = 'mail.provisioning_config.deleted';
	case ProvisioningApplied = 'mail.provisioning.applied';

	// Admin settings
	case GoogleOauthConfigured = 'mail.google_oauth.configured';
	case GoogleOauthDeleted = 'mail.google_oauth.deleted';
	case MicrosoftOauthConfigured = 'mail.microsoft_oauth.configured';
	case MicrosoftOauthDeleted = 'mail.microsoft_oauth.deleted';
	case AntispamConfigUpdated = 'mail.antispam_config.updated';
	case AntispamConfigDeleted = 'mail.antispam_config.deleted';
	case LlmProcessingUpdated = 'mail.llm_processing.updated';
	case AllowNewAccountsUpdated = 'mail.allow_new_accounts.updated';

	// Mailboxes
	case MailboxDeleted = 'mail.mailbox.deleted';
	case MailboxCleared = 'mail.mailbox.cleared';

	// Only logged when a delegate acts on behalf of the account owner
	case DelegatedAccountSettingsUpdated = 'mail.delegated_account_settings.updated';
	case DelegatedAccountSignatureUpdated = 'mail.delegated_account_signature.updated';
	case DelegatedAccountSmimeCertificateUpdated = 'mail.delegated_account_smime_certificate.updated';
	case DelegatedAliasCreated = 'mail.delegated_alias.created';
	case DelegatedAliasUpdated = 'mail.delegated_alias.updated';
	case DelegatedAliasDeleted = 'mail.delegated_alias.deleted';
	case DelegatedAliasSignatureUpdated = 'mail.delegated_alias_signature.updated';
	case DelegatedFiltersUpdated = 'mail.delegated_filters.updated';
	case DelegatedMailboxCreated = 'mail.delegated_mailbox.created';
	case DelegatedMailboxRenamed = 'mail.delegated_mailbox.renamed';
	case DelegatedMailboxSubscribed = 'mail.delegated_mailbox.subscribed';
	case DelegatedMailboxUnsubscribed = 'mail.delegated_mailbox.unsubscribed';
	case DelegatedMailboxSyncEnabled = 'mail.delegated_mailbox_sync.enabled';
	case DelegatedMailboxSyncDisabled = 'mail.delegated_mailbox_sync.disabled';
	case DelegatedMailboxMarkedRead = 'mail.delegated_mailbox.marked_read';
	case DelegatedMailboxRepaired = 'mail.delegated_mailbox.repaired';
	case DelegatedMessageSent = 'mail.delegated_message.sent';
	case DelegatedMessageSendFailed = 'mail.delegated_message.send_failed';
	case DelegatedMessageMoved = 'mail.delegated_message.moved';
	case DelegatedMessageSnoozed = 'mail.delegated_message.snoozed';
	case DelegatedMessageUnsnoozed = 'mail.delegated_message.unsnoozed';
	case DelegatedMessageFlagsUpdated = 'mail.delegated_message_flags.updated';
	case DelegatedMessageTagAdded = 'mail.delegated_message_tag.added';
	case DelegatedMessageTagRemoved = 'mail.delegated_message_tag.removed';
	case DelegatedMessageDeleted = 'mail.delegated_message.deleted';
	case DelegatedThreadMoved = 'mail.delegated_thread.moved';
	case DelegatedThreadSnoozed = 'mail.delegated_thread.snoozed';
	case DelegatedThreadUnsnoozed = 'mail.delegated_thread.unsnoozed';
	case DelegatedThreadDeleted = 'mail.delegated_thread.deleted';
	case DelegatedDraftCreated = 'mail.delegated_draft.created';
	case DelegatedDraftMoved = 'mail.delegated_draft.moved';
	case DelegatedDraftDeleted = 'mail.delegated_draft.deleted';
	case DelegatedOutboxMessageCreated = 'mail.delegated_outbox_message.created';
	case DelegatedOutboxMessageUpdated = 'mail.delegated_outbox_message.updated';
	case DelegatedOutboxMessageDeleted = 'mail.delegated_outbox_message.deleted';
	case DelegatedOutboxMessageSent = 'mail.delegated_outbox_message.sent';
	case DelegatedOutboxMessageSendFailed = 'mail.delegated_outbox_message.send_failed';
	case DelegatedMailingListUnsubscribed = 'mail.delegated_mailing_list.unsubscribed';

	/**
	 * Short description of when the operation is logged, used for the documentation
	 */
	public function description(): string {
		return match ($this) {
			self::AccountCreated => 'A mail account was set up with its IMAP and SMTP credentials',
			self::AccountUpdated => 'The servers or credentials of a mail account were changed',
			self::AccountDeleted => 'A mail account was deleted with its aliases and credentials',
			self::DelegationCreated => 'Another user was allowed to act on behalf of the owner of a mail account',
			self::DelegationDeleted => 'A user was no longer allowed to act on behalf of the owner of a mail account',
			self::SieveAccountUpdated => 'The Sieve server or credentials of a mail account were changed, or Sieve was turned on or off',
			self::SieveScriptUpdated => 'The active Sieve script of a mail account was replaced, including through mail filters and out-of-office replies',
			self::SmimeCertificateCreated => 'An S/MIME certificate was uploaded, optionally with its private key',
			self::SmimeCertificateDeleted => 'An S/MIME certificate was deleted with its private key',
			self::TextBlockShareCreated => 'A text block was shared with a user or a group',
			self::TextBlockShareDeleted => 'A text block was no longer shared with a user or a group',
			self::ProvisioningConfigCreated => 'An admin created a provisioning configuration that sets up mail accounts for matching users',
			self::ProvisioningConfigUpdated => 'An admin changed the servers or credentials of a provisioning configuration',
			self::ProvisioningConfigDeleted => 'An admin deleted a provisioning configuration together with all mail accounts provisioned from it',
			self::ProvisioningApplied => 'Mail accounts were created or updated for all users from the provisioning configurations, from the admin settings, occ mail:account:provision or an app update',
			self::GoogleOauthConfigured => 'An admin set the OAuth client used to connect Google accounts',
			self::GoogleOauthDeleted => 'An admin removed the OAuth client used to connect Google accounts',
			self::MicrosoftOauthConfigured => 'An admin set the OAuth client used to connect Microsoft accounts',
			self::MicrosoftOauthDeleted => 'An admin removed the OAuth client used to connect Microsoft accounts',
			self::AntispamConfigUpdated => 'An admin set the addresses that messages reported as spam or not spam are forwarded to',
			self::AntispamConfigDeleted => 'An admin removed the addresses that reported messages are forwarded to',
			self::LlmProcessingUpdated => 'An admin allowed or disallowed processing mail content with large language models',
			self::AllowNewAccountsUpdated => 'An admin allowed or disallowed users adding their own mail accounts',
			self::MailboxDeleted => 'A mailbox (folder) was deleted with all its messages',
			self::MailboxCleared => 'All messages of a mailbox were moved to the trash, or permanently deleted when it is the trash',
			self::DelegatedAccountSettingsUpdated => 'A delegate changed settings of the mail account, such as the special mailboxes',
			self::DelegatedAccountSignatureUpdated => 'A delegate changed the signature of the mail account',
			self::DelegatedAccountSmimeCertificateUpdated => 'A delegate changed the S/MIME certificate used by the mail account',
			self::DelegatedAliasCreated => 'A delegate created an alias of the mail account',
			self::DelegatedAliasUpdated => 'A delegate changed an alias of the mail account',
			self::DelegatedAliasDeleted => 'A delegate deleted an alias of the mail account',
			self::DelegatedAliasSignatureUpdated => 'A delegate changed the signature of an alias',
			self::DelegatedFiltersUpdated => 'A delegate changed the mail filters of the mail account',
			self::DelegatedMailboxCreated => 'A delegate created a mailbox',
			self::DelegatedMailboxRenamed => 'A delegate renamed a mailbox',
			self::DelegatedMailboxSubscribed => 'A delegate subscribed to a mailbox',
			self::DelegatedMailboxUnsubscribed => 'A delegate unsubscribed from a mailbox',
			self::DelegatedMailboxSyncEnabled => 'A delegate turned on background sync of a mailbox',
			self::DelegatedMailboxSyncDisabled => 'A delegate turned off background sync of a mailbox',
			self::DelegatedMailboxMarkedRead => 'A delegate marked all messages of a mailbox as read',
			self::DelegatedMailboxRepaired => 'A delegate repaired the synchronization of a mailbox',
			self::DelegatedMessageSent => 'A delegate sent a message',
			self::DelegatedMessageSendFailed => 'A delegate tried to send a message, but sending failed',
			self::DelegatedMessageMoved => 'A delegate moved a message to another mailbox',
			self::DelegatedMessageSnoozed => 'A delegate snoozed a message',
			self::DelegatedMessageUnsnoozed => 'A delegate unsnoozed a message',
			self::DelegatedMessageFlagsUpdated => 'A delegate changed flags of a message, such as read or important',
			self::DelegatedMessageTagAdded => 'A delegate added a tag to a message',
			self::DelegatedMessageTagRemoved => 'A delegate removed a tag from a message',
			self::DelegatedMessageDeleted => 'A delegate deleted a message',
			self::DelegatedThreadMoved => 'A delegate moved a thread to another mailbox',
			self::DelegatedThreadSnoozed => 'A delegate snoozed a thread',
			self::DelegatedThreadUnsnoozed => 'A delegate unsnoozed a thread',
			self::DelegatedThreadDeleted => 'A delegate deleted a thread',
			self::DelegatedDraftCreated => 'A delegate created a draft',
			self::DelegatedDraftMoved => 'A delegate moved a draft to the IMAP server',
			self::DelegatedDraftDeleted => 'A delegate deleted a draft',
			self::DelegatedOutboxMessageCreated => 'A delegate created a message in the outbox',
			self::DelegatedOutboxMessageUpdated => 'A delegate changed a message in the outbox',
			self::DelegatedOutboxMessageDeleted => 'A delegate deleted a message from the outbox',
			self::DelegatedOutboxMessageSent => 'A delegate sent a message from the outbox',
			self::DelegatedOutboxMessageSendFailed => 'A delegate tried to send a message from the outbox, but sending failed',
			self::DelegatedMailingListUnsubscribed => 'A delegate unsubscribed the mail account from a mailing list',
		};
	}
}
