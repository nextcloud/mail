<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\UserMigration;

use Exception;
use JsonException;
use OCA\Mail\Db\Alias;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Events\MailboxesSynchronizedEvent;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\AliasesService;
use OCA\Mail\Service\Sync\SyncService;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IL10N;
use OCP\IUser;
use OCP\Security\ICrypto;
use OCP\UserMigration\IExportDestination;
use OCP\UserMigration\IImportSource;
use OCP\UserMigration\IMigrator;
use OCP\UserMigration\UserMigrationException;
use Symfony\Component\Console\Output\OutputInterface;
use function Amp\Iterator\concat;
use function array_map;
use function json_decode;
use function json_encode;

class MailAccountMigrator implements IMigrator {

	public function __construct(
		private AccountService $accountService,
		private AliasesService $aliasesService,
		private MailboxMapper $mailboxMapper,
		private SyncService $syncService,
		private IEventDispatcher $eventDispatcher,
		private IL10N $l10n,
		private ICrypto $crypto,
	) {
	}

	public function export(IUser $user,
		IExportDestination $exportDestination,
		OutputInterface $output,
	): void {
		$accounts = $this->accountService->findByUserId($user->getUID());
		$index = [];
		foreach ($accounts as $account) {
			if ($account->getMailAccount()->getProvisioningId() !== null) {
				// These configuration of these accounts is owned by the admins
				$output->writeln("Skipping provisioned account {$account->getId()}");
				continue;
			}

			$accountFilePath = "mail/accounts/{$account->getId()}.json";
			$accountData = $account->jsonSerialize();

			if ($account->getMailAccount()->getAuthMethod() === 'password') {
				$encryptedInboundPassword = $account->getMailAccount()->getInboundPassword();
				$encryptedOutboundPassword = $account->getMailAccount()->getOutboundPassword();
				if ($encryptedInboundPassword !== null) {
					try {
						$accountData['inboundPassword'] = $this->crypto->decrypt($encryptedInboundPassword);
					} catch (Exception $e) {
						$output->writeln("Can not decrypt inbound password of account {$account->getId()}: " . $e->getMessage());
					}
				}
				if ($encryptedOutboundPassword !== null) {
					try {
						$accountData['outboundPassword'] = $this->crypto->decrypt($encryptedOutboundPassword);
					} catch (Exception $e) {
						$output->writeln("Can not decrypt outbound password of account {$account->getId()}: " . $e->getMessage());
					}
				}
			} elseif ($account->getMailAccount()->getAuthMethod() === 'xoauth2') {
				$encryptedRefreshToken = $account->getMailAccount()->getOauthRefreshToken();
				$encryptedAccessToken = $account->getMailAccount()->getOauthAccessToken();
				if ($encryptedRefreshToken !== null) {
					try {
						$accountData['oauthRefreshToken'] = $this->crypto->decrypt($encryptedRefreshToken);
					} catch (Exception $e) {
						$output->writeln("Can not decrypt oauth refresh token of account {$account->getId()}: " . $e->getMessage());
					}
				}
				if ($encryptedAccessToken !== null) {
					try {
						$accountData['oauthAccessToken'] = $this->crypto->decrypt($encryptedAccessToken);
					} catch (Exception $e) {
						$output->writeln("Can not decrypt oauth access token of account {$account->getId()}: " . $e->getMessage());
					}
				}
				$accountData['oauthTokenTtl'] = $account->getMailAccount()->getOauthTokenTtl();
			}

			$mailboxes = $this->mailboxMapper->findAll($account);
			$accountData['mailboxes'] = array_map(function (Mailbox $mailbox) {
				return $mailbox->jsonSerialize();
			}, $mailboxes);

			$aliases = $this->aliasesService->findAll(
				$account->getId(),
				$account->getUserId(), // perf: this adds overhead - add dedicated method to fetch by account id only
			);
			$accountData['aliases'] = array_map(function (Alias $alias) {
				$data = $alias->jsonSerialize();
				return $data;
			}, $aliases);

			$exportDestination->addFileContents($accountFilePath, json_encode($accountData));
			$index[$account->getId()] = $accountFilePath;
		}

		$exportDestination->addFileContents('mail/accounts/index.json', json_encode($index));
	}

	public function import(IUser $user, IImportSource $importSource, OutputInterface $output): void {
		foreach($this->accountService->getAllAcounts() as $account) {
			$this->accountService->deleteByAccountId($account->getId());
		}

		try {
			$index = json_decode($importSource->getFileContents('mail/accounts/index.json'), true, flags: JSON_THROW_ON_ERROR);
		} catch (JsonException $e) {
			throw new UserMigrationException("Invalid index content: {$e->getMessage()}", $e->getCode(), $e);
		}
		foreach ($index as $accountFilePath) {
			try {
				$accountData = json_decode($importSource->getFileContents($accountFilePath), true, flags: JSON_THROW_ON_ERROR);
			} catch (JsonException $e) {
				throw new UserMigrationException("Invalid account content: {$e->getMessage()}", $e->getCode(), $e);
			}

			// Wipe the old ID(s) to prevent overwrites
			unset(
				$accountData['id'],
				$accountData['accountId'],
			);

			$newAccount = new MailAccount($accountData);
			print_r("Before: " . $newAccount->getId());

			// Change UID to new owner
			$newAccount->setUserId($user->getUID());
			// Map the rest of the properties that are not mapped via the constructor
			$newAccount->setName($accountData['name']);
			$newAccount->setAuthMethod($accountData['authMethod']);
			$newAccount->setEditorMode($accountData['editorMode'] ?? 'plain');
			$newAccount->setSearchBody($accountData['searchBody'] ?? false);
			$newAccount->setClassificationEnabled($accountData['classificationEnabled'] ?? false);
			$newAccount->setSignatureAboveQuote($accountData['signatureAboveQuote'] ?? false);
			$newAccount->setPersonalNamespace($accountData['personalNamespace'] ?? null);
			if (isset($accountData['inboundPassword'])) {
				$newAccount->setInboundPassword($this->crypto->encrypt($accountData['inboundPassword']));
			}
			if (isset($accountData['outboundPassword'])) {
				$newAccount->setOutboundPassword($this->crypto->encrypt($accountData['outboundPassword']));
			}
			if (isset($accountData['oauthRefreshToken'])) {
				$newAccount->setOauthRefreshToken($this->crypto->encrypt($accountData['oauthRefreshToken']));
			}
			if (isset($accountData['oauthAccessToken'])) {
				$newAccount->setOauthAccessToken($this->crypto->encrypt($accountData['oauthAccessToken']));
			}
			$newAccount->setOauthTokenTtl($accountData['oauthTokenTtl'] ?? null);

			$mailAccount = $this->accountService->save(
				$newAccount
			);

			foreach ($accountData['mailboxes'] as $oldMailbox) {
				$newMailbox = new Mailbox();

				$newMailbox->setName($oldMailbox['name']);
				$newMailbox->setNameHash(md5($oldMailbox['name']));
				$newMailbox->setAccountId($mailAccount->getId());
				$newMailbox->setAttributes($oldMailbox['attributes']);
				$newMailbox->setDelimiter($oldMailbox['delimiter']);
				$newMailbox->setMessages(0);
				$newMailbox->setUnseen(0);
				$newMailbox->setSelectable($oldMailbox['selectable'] ?? true);
				$newMailbox->setSyncInBackground($oldMailbox['syncInBackground'] ?? false);
				$newMailbox->setMyAcls($oldMailbox['myAcls'] ?? null);
				$newMailbox->setShared($oldMailbox['shared'] ?? false);

				/** @var Mailbox $mailbox */
				$mailbox = $this->mailboxMapper->insert($newMailbox);

				switch ($oldMailbox['databaseId']) {
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

			// Import aliases
			foreach ($accountData['aliases'] as $alias) {
				$this->aliasesService->create(
					$user->getUID(),
					$mailAccount->getId(),
					$alias['alias'],
					$alias['name'],
				);
			}
		}
	}

	public function getId(): string {
		return 'mail_account';
	}

	public function getDisplayName(): string {
		return $this->l10n->t('Mail');
	}

	public function getDescription(): string {
		return $this->l10n->t('Mail account parameters, aliases and preferences');
	}

	public function getVersion(): int {
		return 01_00_00;
	}

	public function canImport(IImportSource $importSource): bool {
		try {
			return $importSource->getMigratorVersion($this->getId()) <= $this->getVersion();
		} catch (UserMigrationException) {
			return false;
		}
	}

}
