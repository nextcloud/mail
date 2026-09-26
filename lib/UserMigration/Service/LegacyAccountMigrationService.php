<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\UserMigration\Service;

use JsonException;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\AliasesService;
use OCA\Mail\UserMigration\MailAccountMigrator;
use OCP\AppFramework\Db\TTransactional;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IUser;
use OCP\Security\ICrypto;
use OCP\UserMigration\IImportSource;
use OCP\UserMigration\UserMigrationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Reads the archive format of migrator version 1, which carried only accounts
 * and their aliases: mailboxes, tags, certificates, text blocks, trusted
 * senders, internal addresses and quick actions had no representation in it.
 *
 * Version 1 kept an index of account files rather than listing the folder, and
 * wrote whatever `MailAccount::toJson()` produced at the time, so every field is
 * read defensively.
 */
class LegacyAccountMigrationService {
	use TTransactional;

	public const ACCOUNT_INDEX_FILE = MailAccountMigrator::EXPORT_ROOT . '/accounts/index.json';

	public function __construct(
		private readonly AccountService $accountService,
		private readonly AliasesService $aliasesService,
		private readonly ICrypto $crypto,
		private readonly IL10N $l10n,
		private readonly IDBConnection $connection,
		private readonly LoggerInterface $logger,
	) {
	}

	/**
	 * @return array<int, int> Ids of the exported accounts mapped to the imported ones
	 */
	public function importAccounts(IUser $user, IImportSource $importSource, OutputInterface $output): array {
		$output->writeln(
			$this->l10n->t(
				'Importing mail accounts of user %s from a version 1 export',
				[$user->getUID()]
			), OutputInterface::VERBOSITY_VERBOSE
		);

		$accountsMapping = [];
		$orderOffset = $this->getNextAccountOrder($user);

		foreach ($this->getAccountFilePaths($importSource, $output) as $accountFilePath) {
			$accountData = $this->getAccount($importSource, $accountFilePath, $output);
			if ($accountData === null) {
				continue;
			}

			$protocol = $accountData['protocol'] ?? MailAccount::PROTOCOL_IMAP;
			if ($protocol !== MailAccount::PROTOCOL_IMAP) {
				$output->writeln(
					$this->l10n->t(
						'Skipping account %s because migrating %s accounts is not supported yet',
						[$accountFilePath, $protocol]
					), OutputInterface::VERBOSITY_VERBOSE
				);

				continue;
			}

			$newAccount = $this->buildAccount($user, $accountData, $orderOffset);

			try {
				$mailAccount = $this->atomic(function () use ($newAccount, $accountData): MailAccount {
					$saved = $this->accountService->save($newAccount, false);
					$this->setAliases($saved, $accountData);

					return $saved;
				}, $this->connection);
			} catch (Throwable $exception) {
				$this->logger->error('Failed to import a version 1 mail account of user ' . $user->getUID(), [
					'exception' => $exception,
				]);
				$output->writeln(
					$this->l10n->t(
						'Failed to import a mail account of user %s, it will be skipped. See the log for details.',
						[$user->getUID()]
					)
				);

				continue;
			}

			$accountsMapping[(int)($accountData['accountId'] ?? $mailAccount->getId())] = $mailAccount->getId();
		}

		return $accountsMapping;
	}

	/**
	 * Version 1 wrote a map of account id to archive path instead of relying on
	 * the folder listing, and the paths it stored are already absolute.
	 *
	 * @return list<string>
	 */
	private function getAccountFilePaths(IImportSource $importSource, OutputInterface $output): array {
		try {
			$index = json_decode(
				$importSource->getFileContents(self::ACCOUNT_INDEX_FILE),
				true,
				flags: JSON_THROW_ON_ERROR
			);
		} catch (JsonException|UserMigrationException) {
			$output->writeln(
				$this->l10n->t('No readable account index in the version 1 export. Continue...'),
				OutputInterface::VERBOSITY_VERBOSE
			);

			return [];
		}

		if (!is_array($index)) {
			return [];
		}

		return array_values(array_filter($index, static fn (mixed $path): bool => is_string($path)));
	}

	private function getAccount(IImportSource $importSource, string $path, OutputInterface $output): ?array {
		try {
			$accountData = json_decode($importSource->getFileContents($path), true, flags: JSON_THROW_ON_ERROR);
		} catch (JsonException|UserMigrationException) {
			$output->writeln(
				$this->l10n->t('Mail account %s could not be read and will be skipped. Continue...', [$path]),
				OutputInterface::VERBOSITY_VERBOSE
			);

			return null;
		}

		$hasInbound = isset(
			$accountData['emailAddress'],
			$accountData['imapHost'],
			$accountData['imapPort'],
			$accountData['imapSslMode'],
		);
		if (!is_array($accountData) || !$hasInbound) {
			$output->writeln(
				$this->l10n->t('Mail account %s is invalid and will be skipped. Continue...', [$path]),
				OutputInterface::VERBOSITY_VERBOSE
			);

			return null;
		}

		return $accountData;
	}

	private function buildAccount(IUser $user, array $accountData, int $orderOffset): MailAccount {
		$newAccount = new MailAccount();

		$newAccount->setUserId($user->getUID());
		$newAccount->setName($accountData['name'] ?? $accountData['emailAddress']);
		$newAccount->setEmail($accountData['emailAddress']);
		$newAccount->setOrder($orderOffset + max(0, (int)($accountData['order'] ?? 0)));
		$newAccount->setShowSubscribedOnly((bool)($accountData['showSubscribedOnly'] ?? false));
		$newAccount->setEditorMode($accountData['editorMode'] ?? 'plaintext');
		$newAccount->setTrashRetentionDays($accountData['trashRetentionDays'] ?? null);
		$newAccount->setOooFollowsSystem((bool)($accountData['outOfOfficeFollowsSystem'] ?? false));
		$newAccount->setImipCreate((bool)($accountData['imipCreate'] ?? false));
		$newAccount->setClassificationEnabled((bool)($accountData['classificationEnabled'] ?? false));
		$newAccount->setSearchBody((bool)($accountData['searchBody'] ?? false));
		$newAccount->setPersonalNamespace($accountData['personalNamespace'] ?? null);
		$newAccount->setSignature($accountData['signature'] ?? null);
		$newAccount->setSignatureAboveQuote((bool)($accountData['signatureAboveQuote'] ?? false));
		$newAccount->setSignatureMode((int)($accountData['signatureMode'] ?? MailAccount::SIGNATURE_MODE_PLAIN));

		// Version 1 never exported certificates, so any id it carries points at
		// a certificate that does not exist on the importing instance.
		$newAccount->setSmimeCertificateId(null);

		$newAccount->setInboundHost($accountData['imapHost']);
		$newAccount->setInboundPort($accountData['imapPort']);
		$newAccount->setInboundSslMode($accountData['imapSslMode']);
		if (isset($accountData['imapUser'])) {
			$newAccount->setInboundUser($accountData['imapUser']);
		}

		if (isset($accountData['smtpHost'], $accountData['smtpPort'], $accountData['smtpSslMode'])) {
			$newAccount->setOutboundHost($accountData['smtpHost']);
			$newAccount->setOutboundPort($accountData['smtpPort']);
			$newAccount->setOutboundSslMode($accountData['smtpSslMode']);
		}
		if (isset($accountData['smtpUser'])) {
			$newAccount->setOutboundUser($accountData['smtpUser']);
		}

		$newAccount->setAuthMethod($accountData['authMethod'] ?? 'password');
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

		$sieveEnabled = (bool)($accountData['sieveEnabled'] ?? false);
		$newAccount->setSieveEnabled($sieveEnabled);
		if ($sieveEnabled) {
			$newAccount->setSieveHost($accountData['sieveHost'] ?? null);
			$newAccount->setSievePort($accountData['sievePort'] ?? null);
			$newAccount->setSieveSslMode($accountData['sieveSslMode'] ?? null);
			$newAccount->setSieveUser($accountData['sieveUser'] ?? null);
		}

		return $newAccount;
	}

	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException
	 * @throws \OCP\DB\Exception
	 */
	private function setAliases(MailAccount $mailAccount, array $accountData): void {
		$userId = $mailAccount->getUserId();

		foreach ($accountData['aliases'] ?? [] as $alias) {
			if (!is_array($alias) || !isset($alias['alias'])) {
				continue;
			}

			$this->aliasesService->create(
				$userId,
				$mailAccount->getId(),
				$alias['alias'],
				$alias['name'] ?? null,
				$alias['signature'] ?? null,
			);
		}
	}

	private function getNextAccountOrder(IUser $user): int {
		$orders = array_map(
			static fn (Account $account): int => (int)$account->getMailAccount()->getOrder(),
			$this->accountService->findByUserId($user->getUID()),
		);

		return $orders === [] ? 0 : max($orders) + 1;
	}
}
