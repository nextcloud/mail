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
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\AliasesService;
use OCA\Mail\Service\OutOfOfficeService;
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

	public function deleteAllAccounts(IUser $user, OutputInterface $output): void {
		$allAccounts = $this->accountService->getAllAcounts();

		foreach ($allAccounts as $account) {
			$this->accountService->deleteByAccountId($account->getId());
		}
	}

	public function exportAccounts(IUser $user, IExportDestination $exportDestination, OutputInterface $output): void {
		$accounts = [];
		$allAccounts = $this->accountService->findByUserId($user->getUID());

		foreach ($allAccounts as $account) {
			$mailAccount = $account->getMailAccount();

			$accountIsOwnedByAdmins = $mailAccount->getProvisioningId() !== null;
			if ($accountIsOwnedByAdmins) {
				$output->writeln("Skipping provisioned account {$account->getId()}");
				continue;
			}

			$accountData = $account->jsonSerialize();

			$authMethod = $mailAccount->getAuthMethod();

			if ($authMethod === 'password') {
				$this->getDecryptedPasswords($mailAccount, $accountData, $output);
			} elseif ($authMethod === 'xoauth2') {
				$this->getDecryptedOauthToken($mailAccount, $accountData, $output);
			}

			if ($mailAccount->isSieveEnabled()) {
				$this->getDecryptedSievePassword($mailAccount, $accountData, $output);
			}

			$this->getMailboxes($account, $accountData, $output);

			$this->getAliases($account, $accountData, $output);

			$accountFilePath = str_replace(MailAccountMigrator::FILENAME_PLACEHOLDER, (string)$account->getId(), self::ACCOUNT_FILES);
			$exportDestination->addFileContents($accountFilePath, json_encode($accountData));
			$accounts[$account->getId()] = $accountFilePath;
		}

		$exportDestination->addFileContents(str_replace(MailAccountMigrator::FILENAME_PLACEHOLDER, 'index', self::ACCOUNT_FILES), json_encode($accounts));

	}

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
			$newAccount->setSmimeCertificateId($certificatesMapping[$oldCertificateId]);
			$newAccount->setEditorMode($accountData['editorMode'] ?? 'plaintext');
			$newAccount->setTrashRetentionDays($accountData['trashRetentionDays']);
			$newAccount->setOooFollowsSystem($accountData['ooFollowsSystem']);
			$newAccount->setImipCreate($accountData['imipCreate']);
			$newAccount->setClassificationEnabled($accountData['classificationEnabled']);
			$newAccount->setSearchBody($accountData['searchBody'] ?? false);

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

	public function scheduleBackgroundJobs(IUser $user, OutputInterface $output): void {
		$accounts = $this->accountService->findByUserId($user->getUID());

		foreach ($accounts as $account) {
			$mailAccount = $account->getMailAccount();
			$this->accountService->scheduleBackgroundJobs($mailAccount->getId());
		}
	}

	private function setMailboxes(MailAccount &$mailAccount, array $accountData, OutputInterface $output): array {
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

	private function getAccounts(IImportSource $importSource, OutputInterface $output): array {
		try {
			$accountFilePaths = json_decode($importSource->getFileContents(str_replace(MailAccountMigrator::FILENAME_PLACEHOLDER, 'index', self::ACCOUNT_FILES)), true, flags: JSON_THROW_ON_ERROR);
		} catch (JsonException $e) {
			throw new UserMigrationException("Invalid index content: {$e->getMessage()}", $e->getCode(), $e);
		}

		return array_map(function (string $accountFilePath) use ($importSource, $output) {
			try {
				return json_decode($importSource->getFileContents($accountFilePath), true, flags: JSON_THROW_ON_ERROR);
			} catch (JsonException $e) {
				throw new UserMigrationException("Invalid account content: {$e->getMessage()}", $e->getCode(), $e);
			}
		}, $accountFilePaths);
	}

	private function setAliases(MailAccount &$mailAccount, array $accountData, array $certificatesMapping, OutputInterface $output): void {
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

	private function setPasswords(MailAccount &$mailAccount, array $accountData, OutputInterface $output): void {
		if ($mailAccount->getAuthMethod() === 'password') {
			$mailAccount->setInboundUser($accountData['imapUser']);
			$mailAccount->setInboundPassword($this->crypto->encrypt($accountData['inboundPassword']));

			$mailAccount->setOutboundUser($accountData['smtpUser']);
			$mailAccount->setOutboundPassword($this->crypto->encrypt($accountData['outboundPassword']));
		}
	}

	private function getDecryptedPasswords(MailAccount $mailAccount, array &$accountData, OutputInterface $output): void {
		$encryptedInboundPassword = $mailAccount->getInboundPassword();
		if ($encryptedInboundPassword !== null) {
			try {
				$accountData['inboundPassword'] = $this->crypto->decrypt($encryptedInboundPassword);
			} catch (Exception $e) {
				$output->writeln("Can not decrypt inbound password of account {$mailAccount->getId()}: " . $e->getMessage());
			}
		}

		$encryptedOutboundPassword = $mailAccount->getOutboundPassword();
		if ($encryptedOutboundPassword !== null) {
			try {
				$accountData['outboundPassword'] = $this->crypto->decrypt($encryptedOutboundPassword);
			} catch (Exception $e) {
				$output->writeln("Can not decrypt outbound password of account {$mailAccount->getId()}: " . $e->getMessage());
			}
		}
	}

	private function setOauthToken(MailAccount &$mailAccount, array $accountData, OutputInterface $output): void {
		if ($mailAccount->getAuthMethod() === 'xoauth2') {
			$mailAccount->setOauthRefreshToken($this->crypto->encrypt($accountData['oauthRefreshToken']));
			$mailAccount->setOauthAccessToken($this->crypto->encrypt($accountData['oauthAccessToken']));
			$mailAccount->setOauthTokenTtl($accountData['oauthTokenTtl']);
		}
	}

	private function getDecryptedOauthToken(MailAccount $mailAccount, array &$accountData, OutputInterface $output): void {
		$encryptedRefreshToken = $mailAccount->getOauthRefreshToken();
		$encryptedAccessToken = $mailAccount->getOauthAccessToken();
		if ($encryptedRefreshToken !== null) {
			try {
				$accountData['oauthRefreshToken'] = $this->crypto->decrypt($encryptedRefreshToken);
			} catch (Exception $e) {
				$output->writeln("Can not decrypt oauth refresh token of account {$mailAccount->getId()}: " . $e->getMessage());
			}
		}
		if ($encryptedAccessToken !== null) {
			try {
				$accountData['oauthAccessToken'] = $this->crypto->decrypt($encryptedAccessToken);
			} catch (Exception $e) {
				$output->writeln("Can not decrypt oauth access token of account {$mailAccount->getId()}: " . $e->getMessage());
			}
		}
		$accountData['oauthTokenTtl'] = $mailAccount->getOauthTokenTtl();
	}

	private function setSieveSettings(MailAccount &$mailAccount, array $accountData, OutputInterface $output): void {
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

	private function getDecryptedSievePassword(MailAccount $mailAccount, array &$accountData, OutputInterface $output): void {
		$encryptedSievePassword = $mailAccount->getSievePassword();

		if ($encryptedSievePassword !== null) {
			try {
				$accountData['sievePassword'] = $this->crypto->decrypt($encryptedSievePassword);
			} catch (Exception $e) {
				$output->writeln("Can not decrypt sieve password of account {$mailAccount->getId()}: " . $e->getMessage());
			}
		}
	}

	private function getMailboxes(Account $account, array &$accountData, OutputInterface $output): void {
		$mailboxes = $this->mailboxMapper->findAll($account);
		$accountData['mailboxes'] = array_map(function (Mailbox $mailbox) {
			return $mailbox->jsonSerialize();
		}, $mailboxes);
	}

	private function getAliases(Account $account, array &$accountData, OutputInterface $output): void {
		$aliases = $this->aliasesService->findAll(
			$account->getId(),
			$account->getUserId(), // perf: this adds overhead - add dedicated method to fetch by account id only
		);
		$accountData['aliases'] = array_map(function (Alias $alias) {
			return $alias->jsonSerialize();
		}, $aliases);
	}
}
