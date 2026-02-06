<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\UserMigration\Service;

use OCA\Mail\Account;
use OCA\Mail\Db\Alias;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\AliasesService;
use OCA\Mail\UserMigration\MailAccountMigrator;
use OCP\IUser;
use OCP\Security\ICrypto;
use OCP\UserMigration\IExportDestination;
use OCP\UserMigration\IImportSource;
use OCP\UserMigration\UserMigrationException;
use Symfony\Component\Console\Output\OutputInterface;

class AccountMigrationService {
	private const ACCOUNT_FILES = MailAccountMigrator::EXPORT_ROOT . '/accounts/' . MailAccountMigrator::FILENAME_PLACEHOLDER . '.json';

	public function __construct(
		private readonly AccountService $accountService,
		private readonly AliasesService $aliasesService,
		private readonly MailboxMapper $mailboxMapper,
		private readonly ICrypto $crypto,
	) {
	}

	/**
	 * Delete all mail accounts for the given user.
	 *
	 * @param IUser $user
	 * @param OutputInterface $output
	 * @return void
	 * @throws ClientException
	 */
	public function deleteAllAccounts(IUser $user, OutputInterface $output): void {
		$allAccounts = $this->accountService->findByUserId($user->getUID());

		foreach ($allAccounts as $account) {
			$this->accountService->deleteByAccountId($account->getId());
		}
	}

	/**
	 * Exports all mail accounts for the given user.
	 * This includes the mailboxes (without messages),
	 * aliases and Sieve settings.
	 *
	 * @param IUser $user
	 * @param IExportDestination $exportDestination
	 * @param OutputInterface $output
	 * @return void
	 * @throws UserMigrationException
	 * @throws \Exception
	 */
	public function exportAccounts(IUser $user, IExportDestination $exportDestination, OutputInterface $output): void {
		$accounts = $this->accountService->findByUserId($user->getUID());
		$exportedAccounts = [];

		foreach ($accounts as $account) {
			$mailAccount = $account->getMailAccount();

			$isProvisionedAccount = $mailAccount->getProvisioningId() !== null;
			if ($isProvisionedAccount) {
				$output->writeln("Skipping provisioned account {$account->getId()}");
				continue;
			}

			$accountData = $account->jsonSerialize();

			$this->getDecryptedPasswords($mailAccount, $accountData, $output);
			$this->getDecryptedOauthToken($mailAccount, $accountData, $output);
			$this->getDecryptedSievePassword($mailAccount, $accountData, $output);
			$this->getMailboxes($account, $accountData, $output);
			$this->getAliases($account, $accountData, $output);

			$accountFilePath = str_replace(MailAccountMigrator::FILENAME_PLACEHOLDER, (string)$account->getId(), self::ACCOUNT_FILES);
			$exportDestination->addFileContents($accountFilePath, json_encode($accountData));
			$exportedAccounts[$account->getId()] = $accountFilePath;
		}

		$exportDestination->addFileContents(str_replace(MailAccountMigrator::FILENAME_PLACEHOLDER, 'index', self::ACCOUNT_FILES), json_encode($exportedAccounts));
	}

	/**
	 * Import all mail accounts for the given user existing
	 * on export. This includes the mailboxes (without messages),
	 * aliases and Sieve settings.
	 *
	 * @param IUser $user
	 * @param IImportSource $importSource
	 * @param array $certificatesMapping
	 * @param OutputInterface $output
	 * @return array
	 * @throws \JsonException
	 * @throws \OCP\AppFramework\Db\DoesNotExistException
	 * @throws \OCP\DB\Exception
	 * @throws \OCP\UserMigration\UserMigrationException
	 */
	public function importAccounts(IUser $user, IImportSource $importSource, array $certificatesMapping, OutputInterface $output): array {
		$accounts = $this->getAccounts($importSource, $output);
		$accountAndMailboxMappings = [];

		foreach ($accounts as $accountData) {
			$newAccount = new MailAccount();

			// Set general account information
			$newAccount->setUserId($user->getUID());
			$newAccount->setName($accountData['name']);
			$newAccount->setEmail($accountData['emailAddress']);

			// Set general settings
			$newAccount->setShowSubscribedOnly($accountData['showSubscribedOnly']);

			$oldCertificateId = $accountData['smimeCertificateId'];
			$newAccount->setSmimeCertificateId($certificatesMapping[$oldCertificateId] ?? null);
			$newAccount->setEditorMode($accountData['editorMode'] ?? 'plaintext');
			$newAccount->setTrashRetentionDays($accountData['trashRetentionDays']);
			$newAccount->setOooFollowsSystem($accountData['ooFollowsSystem']);
			$newAccount->setImipCreate($accountData['imipCreate']);
			$newAccount->setClassificationEnabled($accountData['classificationEnabled']);
			$newAccount->setSearchBody($accountData['searchBody']);

			// Set signature options
			$newAccount->setSignature($accountData['signature']);
			$newAccount->setSignatureAboveQuote($accountData['signatureAboveQuote']);

			// Set inbound connection
			$newAccount->setInboundHost($accountData['imapHost']);
			$newAccount->setInboundPort($accountData['imapPort']);
			$newAccount->setInboundSslMode($accountData['imapSslMode']);

			// Set outbound connection
			$newAccount->setOutboundHost($accountData['smtpHost']);
			$newAccount->setOutboundPort($accountData['smtpPort']);
			$newAccount->setOutboundSslMode($accountData['smtpSslMode']);

			// Set authentication settings for IMAP and SMTP
			$newAccount->setAuthMethod($accountData['authMethod']);
			$this->setPasswords($newAccount, $accountData, $output);
			$this->setOauthToken($newAccount, $accountData, $output);

			// Set sieve settings
			$this->setSieveSettings($newAccount, $accountData, $output);

			$mailAccount = $this->accountService->save(
				$newAccount, false
			);

			$oldAccountId = $accountData['accountId'];
			$accountAndMailboxMappings['accounts'][$oldAccountId] = $mailAccount->getId();

			$this->setAliases($mailAccount, $accountData, $certificatesMapping, $output);

			$mailboxesMapping = $this->setMailboxes($mailAccount, $accountData, $output);
			$accountAndMailboxMappings['mailboxes'] = $mailboxesMapping;
		}

		return $accountAndMailboxMappings;
	}

	/**
	 * Schedule background jobs for the added accounts.
	 * Necessary to do after all data is being imported as we
	 * could run into race conditions when doing directly after
	 * saving each mail account into database.
	 *
	 * @param IUser $user
	 * @param OutputInterface $output
	 * @return void
	 */
	public function scheduleBackgroundJobs(IUser $user, OutputInterface $output): void {
		$accounts = $this->accountService->findByUserId($user->getUID());

		foreach ($accounts as $account) {
			$mailAccount = $account->getMailAccount();
			$this->accountService->scheduleBackgroundJobs($mailAccount->getId());
		}
	}

	/**
	 * Gets the decrypted IMAP and SMTP passwords and
	 * stores them in `$accountData`. Only happens when
	 * the mail account is configured to use password
	 * authentication.
	 *
	 * @param MailAccount $mailAccount
	 * @param array $accountData
	 * @param OutputInterface $output
	 * @return void
	 * @throws \Exception
	 */
	private function getDecryptedPasswords(MailAccount $mailAccount, array &$accountData, OutputInterface $output): void {
		if ($mailAccount->getAuthMethod() === 'password') {
			$encryptedInboundPassword = $mailAccount->getInboundPassword();
			$accountData['inboundPassword'] = $this->crypto->decrypt($encryptedInboundPassword);

			$encryptedOutboundPassword = $mailAccount->getOutboundPassword();
			$accountData['outboundPassword'] = $this->crypto->decrypt($encryptedOutboundPassword);
		}
	}

	/**
	 * Gets the decrypted oauth2 access and refresh tokens and
	 * stores them in `$accountData` together with the TTL.
	 * Only happens when the mail account is configured to
	 * use oauth2 authentication.
	 *
	 * @param MailAccount $mailAccount
	 * @param array $accountData
	 * @param OutputInterface $output
	 * @return void
	 * @throws \Exception
	 */
	private function getDecryptedOauthToken(MailAccount $mailAccount, array &$accountData, OutputInterface $output): void {
		if ($mailAccount->getAuthMethod() === 'xoauth2') {
			$encryptedRefreshToken = $mailAccount->getOauthRefreshToken();
			$accountData['oauthRefreshToken'] = $this->crypto->decrypt($encryptedRefreshToken);

			$encryptedAccessToken = $mailAccount->getOauthAccessToken();
			$accountData['oauthAccessToken'] = $this->crypto->decrypt($encryptedAccessToken);

			$accountData['oauthTokenTtl'] = $mailAccount->getOauthTokenTtl();
		}
	}

	/**
	 * Decrypts the password to connect to the sieve
	 * server and stores it in `$accountData`. Only
	 * happens when the mail account has a sieve
	 * connection configured and a password set.
	 *
	 * @param MailAccount $mailAccount
	 * @param array $accountData
	 * @param OutputInterface $output
	 * @return void
	 * @throws \Exception
	 */
	private function getDecryptedSievePassword(MailAccount $mailAccount, array &$accountData, OutputInterface $output): void {
		if ($mailAccount->isSieveEnabled()) {
			$encryptedSievePassword = $mailAccount->getSievePassword();

			if ($encryptedSievePassword !== null) {
				$accountData['sievePassword'] = $this->crypto->decrypt($encryptedSievePassword);
			}
		}
	}

	/**
	 * Gets all mailboxes for the given account and
	 * saves it to `$accountData`.
	 *
	 * @param Account $account
	 * @param array $accountData
	 * @param OutputInterface $output
	 * @return void
	 */
	private function getMailboxes(Account $account, array &$accountData, OutputInterface $output): void {
		$mailboxes = $this->mailboxMapper->findAll($account);
		$accountData['mailboxes'] = array_map(function (Mailbox $mailbox) {
			return $mailbox->jsonSerialize();
		}, $mailboxes);
	}

	/**
	 * Gets all aliases for the given account and
	 * saves it to `$accountData`.
	 *
	 * @param Account $account
	 * @param array $accountData
	 * @param OutputInterface $output
	 * @return void
	 */
	private function getAliases(Account $account, array &$accountData, OutputInterface $output): void {
		$aliases = $this->aliasesService->findAll(
			$account->getId(),
			$account->getUserId(), // perf: this adds overhead - add dedicated method to fetch by account id only
		);
		$accountData['aliases'] = array_map(function (Alias $alias) {
			return $alias->jsonSerialize();
		}, $aliases);
	}


	/**
	 * Gets all existing mail accounts on export.
	 *
	 * @param IImportSource $importSource
	 * @param OutputInterface $output
	 * @return array
	 * @throws UserMigrationException
	 * @throws \JsonException
	 */
	private function getAccounts(IImportSource $importSource, OutputInterface $output): array {
		$accountFilePaths = json_decode($importSource->getFileContents(str_replace(MailAccountMigrator::FILENAME_PLACEHOLDER, 'index', self::ACCOUNT_FILES)), true, flags: JSON_THROW_ON_ERROR);

		return array_map(function (string $accountFilePath) use ($importSource, $output) {
			return json_decode($importSource->getFileContents($accountFilePath), true, flags: JSON_THROW_ON_ERROR);
		}, $accountFilePaths);
	}

	/**
	 * Encrypts the IMAP and SMTP password and saves
	 * them to the mail account. Only happens when the mail
	 * account is configured to use password authentication.
	 *
	 * @param MailAccount $mailAccount
	 * @param array $accountData
	 * @param OutputInterface $output
	 * @return void
	 */
	private function setPasswords(MailAccount $mailAccount, array $accountData, OutputInterface $output): void {
		if ($mailAccount->getAuthMethod() === 'password') {
			$mailAccount->setInboundUser($accountData['imapUser']);
			$mailAccount->setInboundPassword($this->crypto->encrypt($accountData['inboundPassword']));

			$mailAccount->setOutboundUser($accountData['smtpUser']);
			$mailAccount->setOutboundPassword($this->crypto->encrypt($accountData['outboundPassword']));
		}
	}

	/**
	 * Encrypts the Oauth2 access and refresh tokens and
	 * saves them to the mail account together with the TTL.
	 * This only happens when the mail account is configured
	 * to use oauth2 authentication.
	 *
	 * @param MailAccount $mailAccount
	 * @param array $accountData
	 * @param OutputInterface $output
	 * @return void
	 */
	private function setOauthToken(MailAccount $mailAccount, array $accountData, OutputInterface $output): void {
		if ($mailAccount->getAuthMethod() === 'xoauth2') {
			$mailAccount->setOauthRefreshToken($this->crypto->encrypt($accountData['oauthRefreshToken']));
			$mailAccount->setOauthAccessToken($this->crypto->encrypt($accountData['oauthAccessToken']));
			$mailAccount->setOauthTokenTtl($accountData['oauthTokenTtl']);
		}
	}

	/**
	 * S
	 *
	 * @param MailAccount $mailAccount
	 * @param array $accountData
	 * @param OutputInterface $output
	 * @return void
	 */
	private function setSieveSettings(MailAccount $mailAccount, array $accountData, OutputInterface $output): void {
		$sieveEnabled = (bool)$accountData['sieveEnabled'];
		$mailAccount->setSieveEnabled($sieveEnabled);

		if ($sieveEnabled) {
			$mailAccount->setSieveHost($accountData['sieveHost']);
			$mailAccount->setSievePort($accountData['sievePort']);
			$mailAccount->setSieveSslMode($accountData['sieveSslMode']);

			// Sieve can use the IMAP credentials, which
			// is indicated by empty username and password.
			$useCustomCredentials = isset($accountData['sieveUser']) && isset($accountData['sievePassword']);
			if ($useCustomCredentials) {
				$mailAccount->setSieveUser($accountData['sieveUser']);
				$mailAccount->setSievePassword($this->crypto->encrypt($accountData['sievePassword']));
			}
		}
	}

	/**
	 * Imports all aliases for the given mail account
	 * on export.
	 *
	 * @param MailAccount $mailAccount
	 * @param array $accountData
	 * @param array $certificatesMapping
	 * @param OutputInterface $output
	 * @return void
	 * @throws \OCP\AppFramework\Db\DoesNotExistException
	 * @throws \OCP\DB\Exception
	 */
	private function setAliases(MailAccount $mailAccount, array $accountData, array $certificatesMapping, OutputInterface $output): void {
		foreach ($accountData['aliases'] as $alias) {
			$userId = $mailAccount->getUserId();

			$newAlias = $this->aliasesService->create(
				$userId,
				$mailAccount->getId(),
				$alias['alias'],
				$alias['name'],
			);

			$this->aliasesService->updateSignature($userId, $newAlias->getId(), (string)$alias['signature']);

			$oldCertificateId = (int)$alias['smimeCertificateId'];
			$this->aliasesService->updateSmimeCertificateId($userId, $newAlias->getId(), $certificatesMapping[$oldCertificateId]);
		}
	}

	/**
	 * Imports all mailboxes for the given mail account.
	 *
	 * @param MailAccount $mailAccount
	 * @param array $accountData
	 * @param OutputInterface $output
	 * @return array Contains the old mailbox id as key and the
	 *               new mailbox id as value. Example: `'2' => '4'`
	 * @throws \OCP\DB\Exception
	 */
	private function setMailboxes(MailAccount $mailAccount, array $accountData, OutputInterface $output): array {
		$mailboxMapping = [];

		foreach ($accountData['mailboxes'] as $oldMailbox) {
			$newMailbox = new Mailbox();

			$newMailbox->setName($oldMailbox['name']);
			$newMailbox->setNameHash(md5($oldMailbox['name']));
			$newMailbox->setAccountId($mailAccount->getId());
			$newMailbox->setAttributes($oldMailbox['attributes']);
			$newMailbox->setDelimiter($oldMailbox['delimiter']);
			$newMailbox->setMessages(0);
			$newMailbox->setUnseen(0);
			$newMailbox->setSelectable($oldMailbox['selectable']);
			$newMailbox->setSyncInBackground($oldMailbox['syncInBackground']);
			$newMailbox->setMyAcls($oldMailbox['myAcls']);
			$newMailbox->setShared($oldMailbox['shared']);

			/** @var Mailbox $mailbox */
			$mailbox = $this->mailboxMapper->insert($newMailbox);

			$oldMailboxId = $oldMailbox['databaseId'];
			$mailboxMapping[$oldMailboxId] = $mailbox->getId();

			// Check if the current mailbox was used as
			// special mailbox and modify the mail
			// account if so.
			switch ($oldMailboxId) {
				case $accountData['draftsMailboxId']:
					$mailAccount->setDraftsMailboxId($mailbox->getId());
					break;
				case $accountData['sentMailboxId']:
					$mailAccount->setSentMailboxId($mailbox->getId());
					break;
				case $accountData['trashMailboxId']:
					$mailAccount->setTrashMailboxId($mailbox->getId());
					break;
				case $accountData['archiveMailboxId']:
					$mailAccount->setArchiveMailboxId($mailbox->getId());
					break;
				case $accountData['junkMailboxId']:
					$mailAccount->setJunkMailboxId($mailbox->getId());
					break;
				case $accountData['snoozeMailboxId']:
					$mailAccount->setSnoozeMailboxId($mailbox->getId());
					break;
			}
		}

		$this->accountService->update($mailAccount);

		return $mailboxMapping;
	}
}
