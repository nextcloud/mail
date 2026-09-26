<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\UserMigration\Service;

use Generator;
use JsonException;
use OCA\Mail\Account;
use OCA\Mail\Db\Alias;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\AliasesService;
use OCA\Mail\UserMigration\MailAccountMigrator;
use OCP\AppFramework\Db\TTransactional;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IUser;
use OCP\Security\ICrypto;
use OCP\UserMigration\IExportDestination;
use OCP\UserMigration\IImportSource;
use OCP\UserMigration\UserMigrationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * @psalm-type MailAccountExport = array{
 *     accountId: int, name: string, emailAddress: string, order: int,
 *     showSubscribedOnly: bool, smimeCertificateId: int|null, editorMode: string|null,
 *     trashRetentionDays: int|null, outOfOfficeFollowsSystem: bool, imipCreate: bool|null,
 *     classificationEnabled: bool, searchBody: bool, personalNamespace: string|null,
 *     signature: string|null, signatureAboveQuote: bool, signatureMode: int,
 *     protocol: string, authMethod: string,
 *     imapHost: string|null, imapPort: int|null, imapSslMode: string|null,
 *     imapUser: string|null, inboundPassword: string|null,
 *     smtpHost: string|null, smtpPort: int|null, smtpSslMode: string|null,
 *     smtpUser: string|null, outboundPassword: string|null,
 *     oauthRefreshToken: string|null, oauthAccessToken: string|null, oauthTokenTtl: int|null,
 *     sieveEnabled: bool, sieveHost: string|null, sievePort: int|null,
 *     sieveSslMode: string|null, sieveUser: string|null, sievePassword: string|null,
 *     draftsMailboxId: int|null, sentMailboxId: int|null, trashMailboxId: int|null,
 *     archiveMailboxId: int|null, junkMailboxId: int|null, snoozeMailboxId: int|null,
 *     mailboxes: list<array>, aliases: list<array>
 * }
 */
class AccountMigrationService {
	use TTransactional;

	public const ACCOUNT_FOLDER = MailAccountMigrator::EXPORT_ROOT . '/accounts/';
	public const ACCOUNT_FILES = self::ACCOUNT_FOLDER . MailAccountMigrator::FILENAME_PLACEHOLDER . '.json';

	/**
	 * Measured on real export payloads and rounded up: an underestimate makes
	 * the caller reserve too little space, which is worse than overshooting.
	 */
	public const ESTIMATED_ACCOUNT_SIZE = 1500;
	public const ESTIMATED_MAILBOX_SIZE = 300;
	public const ESTIMATED_ALIAS_SIZE = 250;

	/** Every key the export writes; an archive missing one is not ours. */
	private const REQUIRED_ACCOUNT_KEYS = [
		'accountId', 'name', 'emailAddress', 'order',
		'showSubscribedOnly', 'smimeCertificateId', 'editorMode', 'trashRetentionDays',
		'outOfOfficeFollowsSystem', 'imipCreate', 'classificationEnabled', 'searchBody',
		'personalNamespace', 'signature', 'signatureAboveQuote', 'signatureMode',
		'protocol', 'authMethod',
		'imapHost', 'imapPort', 'imapSslMode', 'imapUser', 'inboundPassword',
		'smtpHost', 'smtpPort', 'smtpSslMode', 'smtpUser', 'outboundPassword',
		'oauthRefreshToken', 'oauthAccessToken', 'oauthTokenTtl',
		'sieveEnabled', 'sieveHost', 'sievePort', 'sieveSslMode', 'sieveUser', 'sievePassword',
		'draftsMailboxId', 'sentMailboxId', 'trashMailboxId',
		'archiveMailboxId', 'junkMailboxId', 'snoozeMailboxId',
		'mailboxes', 'aliases',
	];

	private const REQUIRED_MAILBOX_KEYS = [
		'databaseId', 'name', 'attributes', 'delimiter', 'specialUse',
		'selectable', 'syncInBackground', 'myAcls', 'shared',
	];

	private const REQUIRED_ALIAS_KEYS = ['alias', 'name', 'signature', 'smimeCertificateId'];

	public function __construct(
		private readonly AccountService $accountService,
		private readonly AliasesService $aliasesService,
		private readonly MailboxMapper $mailboxMapper,
		private readonly ICrypto $crypto,
		private readonly IL10N $l10n,
		private readonly IDBConnection $connection,
		private readonly LoggerInterface $logger,
	) {
	}

	/**
	 * Mailboxes dominate the size of an account file, so they are counted in the
	 * database instead of being estimated per account.
	 *
	 * @throws \OCP\DB\Exception
	 */
	public function getEstimatedExportSize(IUser $user): int {
		$bytes = 0;

		foreach ($this->accountService->findByUserId($user->getUID()) as $account) {
			if ($this->getExportSkipReason($account->getMailAccount()) !== null) {
				continue;
			}

			$mailboxCount = $this->mailboxMapper->countByAccount($account);
			$aliasCount = $this->aliasesService->countByAccountId($account->getId(), $account->getUserId());

			$bytes += self::ESTIMATED_ACCOUNT_SIZE
				+ $mailboxCount * self::ESTIMATED_MAILBOX_SIZE
				+ $aliasCount * self::ESTIMATED_ALIAS_SIZE;
		}

		return $bytes;
	}

	/**
	 * Exports all mail accounts for the given user.
	 * This includes the mailboxes (without messages),
	 * aliases and Sieve settings.
	 *
	 * @throws UserMigrationException
	 */
	public function exportAccounts(IUser $user, IExportDestination $exportDestination, OutputInterface $output): void {
		$output->writeln(
			$this->l10n->t(
				'Exporting mail accounts for user %s',
				[$user->getUID()]
			), OutputInterface::VERBOSITY_VERBOSE
		);

		$accounts = $this->accountService->findByUserId($user->getUID());

		foreach ($accounts as $account) {
			$mailAccount = $account->getMailAccount();

			$skipReason = $this->getExportSkipReason($mailAccount);
			if ($skipReason !== null) {
				$output->writeln($skipReason, OutputInterface::VERBOSITY_VERBOSE);

				continue;
			}

			try {
				$accountData = $this->serialiseAccount($account);
			} catch (\Exception $exception) {
				throw new UserMigrationException(
					"Failed to decrypt passwords for user {$user->getUID()}",
					previous: $exception
				);
			}

			$this->getMailboxes($account, $accountData);
			$this->getAliases($account, $accountData);

			$accountFilePath = str_replace(MailAccountMigrator::FILENAME_PLACEHOLDER, (string)$account->getId(),
				self::ACCOUNT_FILES);

			try {
				$exportDestination->addFileContents($accountFilePath, json_encode($accountData, JSON_THROW_ON_ERROR));
			} catch (JsonException|UserMigrationException $exception) {
				throw new UserMigrationException(
					"Failed to export mail accounts for user {$user->getUID()}",
					previous: $exception
				);
			}
		}
	}

	/**
	 * Import all mail accounts for the given user existing
	 * on export. This includes the mailboxes (without messages),
	 * aliases and Sieve settings.
	 *
	 * @param array<int, int> $certificatesMapping
	 * @return array{accounts: array<int, int>, mailboxes: array<int, int>} Ids of the exported accounts and
	 *                                                                      mailboxes mapped to the imported ones
	 * @throws \JsonException
	 * @throws \OCP\UserMigration\UserMigrationException
	 */
	public function importAccounts(IUser $user,
		IImportSource $importSource,
		OutputInterface $output,
		array $certificatesMapping): array {
		$output->writeln(
			$this->l10n->t(
				'Importing mail accounts for user %s',
				[$user->getUID()]
			), OutputInterface::VERBOSITY_VERBOSE
		);

		$accounts = $this->getAccounts($importSource, $output);
		$accountAndMailboxMappings = ['accounts' => [], 'mailboxes' => []];
		$orderOffset = $this->getNextAccountOrder($user);

		foreach ($accounts as $accountData) {
			try {
				$this->validateAccount($accountData);
			} catch (UserMigrationException $exception) {
				$this->logger->warning('Skipping an invalid mail account while importing user ' . $user->getUID(), [
					'exception' => $exception,
				]);
				$output->writeln(
					$this->l10n->t(
						'A mail account of user %s is invalid and will be skipped. Continue...',
						[$user->getUID()]
					), OutputInterface::VERBOSITY_VERBOSE
				);

				continue;
			}

			$protocol = $accountData['protocol'];
			if ($protocol !== MailAccount::PROTOCOL_IMAP) {
				$output->writeln(
					$this->l10n->t(
						'Skipping account with ID %s because migrating %s accounts is not supported yet',
						[$accountData['accountId'], $protocol]
					), OutputInterface::VERBOSITY_VERBOSE
				);

				continue;
			}

			$newAccount = $this->buildAccount($user, $accountData, $certificatesMapping, $orderOffset);

			try {
				$imported = $this->atomic(function () use ($newAccount, $accountData, $certificatesMapping): array {
					$mailAccount = $this->accountService->save($newAccount, false);
					$this->setAliases($mailAccount, $accountData, $certificatesMapping);

					return [
						'accountId' => $mailAccount->getId(),
						'mailboxes' => $this->setMailboxes($mailAccount, $accountData),
					];
				}, $this->connection);
			} catch (Throwable $exception) {
				// Each account commits on its own, so a failure leaves nothing
				// behind and the remaining accounts can still be imported.
				$this->logger->error('Failed to import a mail account of user ' . $user->getUID(), [
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

			$accountAndMailboxMappings['accounts'][$accountData['accountId']] = $imported['accountId'];
			$accountAndMailboxMappings['mailboxes'] += $imported['mailboxes'];
		}

		return $accountAndMailboxMappings;
	}

	/**
	 * Imported accounts are appended after the user's existing ones: restoring
	 * the exported order verbatim would interleave them into an arrangement the
	 * user already has. Zero when there is nothing to append to, so a migration
	 * into a fresh user keeps the exported order exactly.
	 */
	private function getNextAccountOrder(IUser $user): int {
		$orders = array_map(
			static fn (Account $account): int => (int)$account->getMailAccount()->getOrder(),
			$this->accountService->findByUserId($user->getUID()),
		);

		return $orders === [] ? 0 : max($orders) + 1;
	}

	/**
	 * Necessary to do after all data is being imported as we
	 * could run into race conditions when doing directly after
	 * saving each mail account into database.
	 *
	 * @param array<int, int> $accountsMapping Ids of the exported accounts mapped to the imported ones
	 */
	public function scheduleBackgroundJobs(array $accountsMapping, OutputInterface $output): void {
		foreach ($accountsMapping as $accountId) {
			$output->writeln(
				$this->l10n->t(
					'Scheduling background jobs for mail account %s',
					[$accountId]
				), OutputInterface::VERBOSITY_VERBOSE
			);

			$this->accountService->scheduleBackgroundJobs($accountId);
		}
	}

	/**
	 * @param array<int, int> $certificatesMapping
	 * @param int $orderOffset Added to the exported order so imported accounts
	 *                         sort after the ones the user already has
	 */
	private function buildAccount(IUser $user, array $accountData, array $certificatesMapping, int $orderOffset): MailAccount {
		$newAccount = new MailAccount();

		$newAccount->setUserId($user->getUID());
		$newAccount->setName($accountData['name']);
		$newAccount->setEmail($accountData['emailAddress']);

		$newAccount->setShowSubscribedOnly($accountData['showSubscribedOnly']);

		$oldCertificateId = $accountData['smimeCertificateId'];
		$newAccount->setSmimeCertificateId($certificatesMapping[$oldCertificateId] ?? null);
		$newAccount->setEditorMode($accountData['editorMode'] ?? 'plaintext');
		$newAccount->setTrashRetentionDays($accountData['trashRetentionDays']);
		$newAccount->setOooFollowsSystem($accountData['outOfOfficeFollowsSystem']);
		$newAccount->setImipCreate($accountData['imipCreate']);
		$newAccount->setClassificationEnabled($accountData['classificationEnabled']);
		$newAccount->setSearchBody($accountData['searchBody']);

		$newAccount->setOrder($orderOffset + max(0, $accountData['order']));
		$newAccount->setSignature($accountData['signature']);
		$newAccount->setSignatureAboveQuote($accountData['signatureAboveQuote']);
		$newAccount->setSignatureMode($accountData['signatureMode']);

		$newAccount->setPersonalNamespace($accountData['personalNamespace']);

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

		$newAccount->setAuthMethod($accountData['authMethod']);
		$this->setPasswords($newAccount, $accountData);
		$this->setOauthToken($newAccount, $accountData);

		$this->setSieveSettings($newAccount, $accountData);

		return $newAccount;
	}

	/**
	 * @throws UserMigrationException
	 */
	private function validateAccount(mixed $accountData): void {
		if (!is_array($accountData)) {
			throw new UserMigrationException('Invalid account export structure');
		}

		$this->assertRequiredKeys($accountData, self::REQUIRED_ACCOUNT_KEYS, 'account');

		if (!is_int($accountData['accountId'])) {
			throw new UserMigrationException('Invalid account id in account export');
		}

		$this->assertListOfRequiredKeys($accountData['mailboxes'], self::REQUIRED_MAILBOX_KEYS, 'mailbox');
		$this->assertListOfRequiredKeys($accountData['aliases'], self::REQUIRED_ALIAS_KEYS, 'alias');
	}

	/**
	 * @param list<string> $requiredKeys
	 * @throws UserMigrationException
	 */
	private function assertRequiredKeys(array $entry, array $requiredKeys, string $label): void {
		foreach ($requiredKeys as $requiredKey) {
			if (!array_key_exists($requiredKey, $entry)) {
				throw new UserMigrationException("Exported $label is missing $requiredKey");
			}
		}
	}

	/**
	 * @param list<string> $requiredKeys
	 * @throws UserMigrationException
	 */
	private function assertListOfRequiredKeys(mixed $entries, array $requiredKeys, string $label): void {
		if (!is_array($entries) || !array_is_list($entries)) {
			throw new UserMigrationException("Invalid {$label}es in account export");
		}

		foreach ($entries as $entry) {
			if (!is_array($entry)) {
				throw new UserMigrationException("Invalid $label in account export");
			}

			$this->assertRequiredKeys($entry, $requiredKeys, $label);
		}
	}

	/**
	 * Provisioned accounts are managed by the target instance and non-IMAP
	 * accounts are not migratable yet, so neither is exported.
	 *
	 * @return string|null The reason to skip the account, or null to export it
	 */
	private function getExportSkipReason(MailAccount $mailAccount): ?string {
		if ($mailAccount->getProvisioningId() !== null) {
			return $this->l10n->t(
				'Skipping provisioned account with ID %s',
				[$mailAccount->getId()]
			);
		}

		if ($mailAccount->getProtocol() !== MailAccount::PROTOCOL_IMAP) {
			return $this->l10n->t(
				'Skipping account with ID %s because migrating %s accounts is not supported yet',
				[$mailAccount->getId(), $mailAccount->getProtocol()]
			);
		}

		return null;
	}

	/**
	 * The archive format. Deliberately not `MailAccount::toJson()`: that shape
	 * serves the API, so a field added for the UI would silently change what
	 * every export contains and what the import has to accept.
	 *
	 * @return MailAccountExport
	 * @throws \Exception when a stored secret cannot be decrypted
	 */
	private function serialiseAccount(Account $account): array {
		$mailAccount = $account->getMailAccount();
		$authMethod = $mailAccount->getAuthMethod() ?? 'password';
		$usesPassword = $authMethod === 'password';
		$usesOauth = $authMethod === 'xoauth2';

		return [
			'accountId' => $account->getId(),
			'name' => $mailAccount->getName(),
			'emailAddress' => $mailAccount->getEmail(),
			'order' => $mailAccount->getOrder(),
			'showSubscribedOnly' => $mailAccount->getShowSubscribedOnly() === true,
			'smimeCertificateId' => $mailAccount->getSmimeCertificateId(),
			'editorMode' => $mailAccount->getEditorMode(),
			'trashRetentionDays' => $mailAccount->getTrashRetentionDays(),
			'outOfOfficeFollowsSystem' => $mailAccount->getOutOfOfficeFollowsSystem(),
			'imipCreate' => $mailAccount->getImipCreate(),
			'classificationEnabled' => $mailAccount->getClassificationEnabled(),
			'searchBody' => $mailAccount->getSearchBody(),
			'personalNamespace' => $mailAccount->getPersonalNamespace(),
			'signature' => $mailAccount->getSignature(),
			'signatureAboveQuote' => $mailAccount->isSignatureAboveQuote() === true,
			'signatureMode' => $mailAccount->getSignatureMode(),
			'protocol' => $mailAccount->getProtocol(),

			'authMethod' => $authMethod,
			'imapHost' => $mailAccount->getInboundHost(),
			'imapPort' => $mailAccount->getInboundPort(),
			'imapSslMode' => $mailAccount->getInboundSslMode(),
			'imapUser' => $mailAccount->getInboundUser(),
			'inboundPassword' => $usesPassword ? $this->decrypt($mailAccount->getInboundPassword()) : null,

			'smtpHost' => $mailAccount->getOutboundHost(),
			'smtpPort' => $mailAccount->getOutboundPort(),
			'smtpSslMode' => $mailAccount->getOutboundSslMode(),
			'smtpUser' => $mailAccount->getOutboundUser(),
			'outboundPassword' => $usesPassword ? $this->decrypt($mailAccount->getOutboundPassword()) : null,

			'oauthRefreshToken' => $usesOauth ? $this->decrypt($mailAccount->getOauthRefreshToken()) : null,
			'oauthAccessToken' => $usesOauth ? $this->decrypt($mailAccount->getOauthAccessToken()) : null,
			'oauthTokenTtl' => $usesOauth ? $mailAccount->getOauthTokenTtl() : null,

			'sieveEnabled' => $mailAccount->isSieveEnabled() === true,
			'sieveHost' => $mailAccount->getSieveHost(),
			'sievePort' => $mailAccount->getSievePort(),
			'sieveSslMode' => $mailAccount->getSieveSslMode(),
			'sieveUser' => $mailAccount->getSieveUser(),
			'sievePassword' => $mailAccount->isSieveEnabled() ? $this->decrypt($mailAccount->getSievePassword()) : null,

			'draftsMailboxId' => $mailAccount->getDraftsMailboxId(),
			'sentMailboxId' => $mailAccount->getSentMailboxId(),
			'trashMailboxId' => $mailAccount->getTrashMailboxId(),
			'archiveMailboxId' => $mailAccount->getArchiveMailboxId(),
			'junkMailboxId' => $mailAccount->getJunkMailboxId(),
			'snoozeMailboxId' => $mailAccount->getSnoozeMailboxId(),

			'mailboxes' => [],
			'aliases' => [],
		];
	}

	/**
	 * @throws \Exception
	 */
	private function decrypt(?string $encrypted): ?string {
		return $encrypted === null ? null : $this->crypto->decrypt($encrypted);
	}

	/**
	 * Message counts and sync state are deliberately not exported.
	 */
	private function getMailboxes(Account $account, array &$accountData): void {
		$mailboxes = $this->mailboxMapper->findAll($account);
		$accountData['mailboxes'] = array_map(static function (Mailbox $mailbox) {
			return [
				'databaseId' => $mailbox->getId(),
				'name' => $mailbox->getName(),
				'attributes' => $mailbox->getAttributes(),
				'delimiter' => $mailbox->getDelimiter(),
				'specialUse' => $mailbox->getSpecialUse(),
				'selectable' => $mailbox->getSelectable(),
				'syncInBackground' => $mailbox->getSyncInBackground(),
				'myAcls' => $mailbox->getMyAcls(),
				'shared' => $mailbox->isShared(),
			];
		}, $mailboxes);
	}

	/**
	 * Gets all aliases for the given account and
	 * saves it to `$accountData`.
	 *
	 * @param Account $account
	 * @param array $accountData
	 * @return void
	 */
	private function getAliases(Account $account, array &$accountData): void {
		$aliases = $this->aliasesService->findAll(
			$account->getId(),
			$account->getUserId(), // perf: this adds overhead - add dedicated method to fetch by account id only
		);
		$accountData['aliases'] = array_map(static function (Alias $alias) {
			return [
				'alias' => $alias->getAlias(),
				'name' => $alias->getName(),
				'signature' => $alias->getSignature(),
				'smimeCertificateId' => $alias->getSmimeCertificateId(),
			];
		}, $aliases);
	}

	/**
	 * `getFolderListing()` returns names relative to the folder, so they have to
	 * be prefixed again. It is also the only way to test for a folder: the export
	 * writes no directory entries, so `pathExists()` on a folder is always false.
	 *
	 * @return Generator<array>
	 * @throws UserMigrationException
	 */
	private function getAccounts(IImportSource $importSource, OutputInterface $output): Generator {
		foreach ($importSource->getFolderListing(self::ACCOUNT_FOLDER) as $accountFileName) {
			if (!str_ends_with($accountFileName, '.json')) {
				continue;
			}

			try {
				yield json_decode(
					$importSource->getFileContents(self::ACCOUNT_FOLDER . $accountFileName),
					true,
					flags: JSON_THROW_ON_ERROR
				);
			} catch (JsonException|UserMigrationException $exception) {
				$this->logger->warning('Skipping an unreadable mail account file: ' . $accountFileName, [
					'exception' => $exception,
				]);
				$output->writeln(
					$this->l10n->t(
						'Mail account %s could not be read and will be skipped. Continue...',
						[$accountFileName]
					), OutputInterface::VERBOSITY_VERBOSE
				);
			}
		}
	}

	private function setPasswords(MailAccount $mailAccount, array $accountData): void {
		if ($mailAccount->getAuthMethod() !== 'password') {
			return;
		}

		if (isset($accountData['inboundPassword'])) {
			$mailAccount->setInboundPassword($this->crypto->encrypt($accountData['inboundPassword']));
		}
		if (isset($accountData['outboundPassword'])) {
			$mailAccount->setOutboundPassword($this->crypto->encrypt($accountData['outboundPassword']));
		}
	}

	private function setOauthToken(MailAccount $mailAccount, array $accountData): void {
		if ($mailAccount->getAuthMethod() !== 'xoauth2') {
			return;
		}

		if (isset($accountData['oauthRefreshToken'])) {
			$mailAccount->setOauthRefreshToken($this->crypto->encrypt($accountData['oauthRefreshToken']));
		}
		if (isset($accountData['oauthAccessToken'])) {
			$mailAccount->setOauthAccessToken($this->crypto->encrypt($accountData['oauthAccessToken']));
		}
		$mailAccount->setOauthTokenTtl($accountData['oauthTokenTtl']);
	}

	private function setSieveSettings(MailAccount $mailAccount, array $accountData): void {
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
	 * @param array<int, int> $certificatesMapping
	 * @throws \OCP\AppFramework\Db\DoesNotExistException
	 * @throws \OCP\DB\Exception
	 */
	private function setAliases(MailAccount $mailAccount, array $accountData, array $certificatesMapping): void {
		$userId = $mailAccount->getUserId();

		foreach ($accountData['aliases'] as $alias) {
			$oldCertificateId = $alias['smimeCertificateId'];

			$this->aliasesService->create(
				$userId,
				$mailAccount->getId(),
				$alias['alias'],
				$alias['name'],
				$alias['signature'],
				$certificatesMapping[$oldCertificateId] ?? null,
			);
		}
	}

	/**
	 * Imports all mailboxes for the given mail account.
	 *
	 * @return array Contains the old mailbox id as key and the
	 *               new mailbox id as value. Example: `'2' => '4'`
	 * @throws \OCP\DB\Exception
	 */
	private function setMailboxes(MailAccount $mailAccount, array $accountData): array {
		$mailboxMapping = [];

		foreach ($accountData['mailboxes'] as $oldMailbox) {
			$newMailbox = new Mailbox();

			$newMailbox->setName($oldMailbox['name']);
			$newMailbox->setNameHash(md5($oldMailbox['name']));
			$newMailbox->setAccountId($mailAccount->getId());
			$newMailbox->setAttributes($oldMailbox['attributes']);
			$newMailbox->setDelimiter($oldMailbox['delimiter']);
			$newMailbox->setSpecialUse($oldMailbox['specialUse']);
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
