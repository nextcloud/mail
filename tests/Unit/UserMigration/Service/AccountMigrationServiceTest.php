<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Unit\UserMigration\Service;

use ChristophWurst\Nextcloud\Testing\ServiceMockObject;
use ChristophWurst\Nextcloud\Testing\TestCase;
use Exception;
use OCA\Mail\Account;
use OCA\Mail\Db\Alias;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\UserMigration\Service\AccountMigrationService;
use OCP\IUser;
use OCP\UserMigration\IExportDestination;
use OCP\UserMigration\IImportSource;
use OCP\UserMigration\UserMigrationException;
use Symfony\Component\Console\Output\OutputInterface;

class AccountMigrationServiceTest extends TestCase {
	private const USER_ID = 'test_user';
	private const EXPORTED_ACCOUNT_ID = 19;
	private const IMPORTED_ACCOUNT_ID = 42;
	private const EXPORTED_INBOX_ID = 200;
	private const IMPORTED_INBOX_ID = 500;
	private const EXPORTED_SENT_ID = 201;
	private const IMPORTED_SENT_ID = 501;

	private OutputInterface $output;
	private IUser $user;
	private IExportDestination $exportDestination;
	private IImportSource $importSource;
	private ServiceMockObject $serviceMock;
	private AccountMigrationService $migrationService;
	private ?MailAccount $savedAccount = null;

	protected function setUp(): void {
		parent::setUp();

		$this->output = $this->createMock(OutputInterface::class);
		$this->exportDestination = $this->createMock(IExportDestination::class);
		$this->importSource = $this->createMock(IImportSource::class);

		$this->user = $this->createMock(IUser::class);
		$this->user->method('getUID')->willReturn(self::USER_ID);

		$this->serviceMock = $this->createServiceMock(AccountMigrationService::class);

		$this->serviceMock->getParameter('crypto')
			->method('encrypt')
			->willReturnCallback(function (string $value) {
				return $value . '_encrypted';
			});

		$this->serviceMock->getParameter('crypto')
			->method('decrypt')
			->willReturnCallback(function (string $encryptedValue) {
				if (!str_ends_with($encryptedValue, '_encrypted')) {
					throw new Exception('Invalid encrypted value');
				}
				return substr($encryptedValue, 0, strlen($encryptedValue) - strlen('_encrypted'));
			});

		$this->migrationService = $this->serviceMock->getService();
	}

	public function testExportsAccountWithDecryptedCredentials(): void {
		$account = new Account($this->getExportableMailAccount());
		$this->serviceMock->getParameter('accountService')
			->method('findByUserId')
			->with(self::USER_ID)
			->willReturn([$account]);
		$this->serviceMock->getParameter('mailboxMapper')
			->method('findAll')
			->with($account)
			->willReturn([$this->getInboxMailbox()]);
		$this->serviceMock->getParameter('aliasesService')
			->method('findAll')
			->with(self::EXPORTED_ACCOUNT_ID, self::USER_ID)
			->willReturn([$this->getAlias()]);

		$exportedFiles = [];
		$this->exportDestination->expects(self::once())
			->method('addFileContents')
			->willReturnCallback(function (string $path, string $contents) use (&$exportedFiles): void {
				$exportedFiles[$path] = $contents;
			});

		$this->migrationService->exportAccounts($this->user, $this->exportDestination, $this->output);

		$expectedPath = AccountMigrationService::ACCOUNT_FOLDER . self::EXPORTED_ACCOUNT_ID . '.json';
		self::assertArrayHasKey($expectedPath, $exportedFiles);
		$exported = json_decode($exportedFiles[$expectedPath], true, flags: JSON_THROW_ON_ERROR);
		self::assertSame('imap_secret', $exported['inboundPassword']);
		self::assertSame('smtp_secret', $exported['outboundPassword']);
		self::assertSame('sieve_secret', $exported['sievePassword']);
		self::assertSame(self::EXPORTED_ACCOUNT_ID, $exported['accountId']);
	}

	public function testExportSkipsProvisionedAccounts(): void {
		$mailAccount = $this->getExportableMailAccount();
		$mailAccount->setProvisioningId(7);
		$this->serviceMock->getParameter('accountService')
			->method('findByUserId')
			->with(self::USER_ID)
			->willReturn([new Account($mailAccount)]);

		$this->exportDestination->expects(self::never())
			->method('addFileContents');

		$this->migrationService->exportAccounts($this->user, $this->exportDestination, $this->output);
	}

	public function testEstimatedExportSizeGrowsWithMailboxesAndAliases(): void {
		$account = new Account($this->getExportableMailAccount());
		$this->serviceMock->getParameter('accountService')
			->method('findByUserId')
			->with(self::USER_ID)
			->willReturn([$account]);
		$this->serviceMock->getParameter('mailboxMapper')
			->expects(self::once())
			->method('countByAccount')
			->with($account)
			->willReturn(2);
		$this->serviceMock->getParameter('aliasesService')
			->expects(self::once())
			->method('countByAccountId')
			->with(self::EXPORTED_ACCOUNT_ID, self::USER_ID)
			->willReturn(1);
		$this->serviceMock->getParameter('mailboxMapper')
			->expects(self::never())
			->method('findAll');

		$size = $this->migrationService->getEstimatedExportSize($this->user);

		self::assertSame(
			AccountMigrationService::ESTIMATED_ACCOUNT_SIZE
				+ 2 * AccountMigrationService::ESTIMATED_MAILBOX_SIZE
				+ AccountMigrationService::ESTIMATED_ALIAS_SIZE,
			$size,
		);
	}

	public function testEstimatedExportSizeIgnoresAccountsThatAreNotExported(): void {
		$provisioned = $this->getExportableMailAccount();
		$provisioned->setProvisioningId(7);
		$jmap = $this->getExportableMailAccount();
		$jmap->setProtocol(MailAccount::PROTOCOL_JMAP);

		$this->serviceMock->getParameter('accountService')
			->method('findByUserId')
			->with(self::USER_ID)
			->willReturn([new Account($provisioned), new Account($jmap)]);
		$this->serviceMock->getParameter('mailboxMapper')
			->expects(self::never())
			->method('countByAccount');

		$size = $this->migrationService->getEstimatedExportSize($this->user);

		self::assertSame(0, $size);
	}

	public function testExportSkipsAccountsWithUnsupportedProtocol(): void {
		$mailAccount = $this->getExportableMailAccount();
		$mailAccount->setProtocol(MailAccount::PROTOCOL_JMAP);
		$this->serviceMock->getParameter('accountService')
			->method('findByUserId')
			->with(self::USER_ID)
			->willReturn([new Account($mailAccount)]);

		$this->exportDestination->expects(self::never())
			->method('addFileContents');

		$this->migrationService->exportAccounts($this->user, $this->exportDestination, $this->output);
	}

	public function testImportSkipsAccountsWithUnsupportedProtocol(): void {
		$accountData = $this->getExportedAccountData();
		$accountData['protocol'] = MailAccount::PROTOCOL_JMAP;
		$this->expectAccountFileToBeImported($accountData);

		$this->serviceMock->getParameter('accountService')
			->expects(self::never())
			->method('save');
		$this->serviceMock->getParameter('connection')
			->expects(self::never())
			->method('beginTransaction');

		$mappings = $this->migrationService->importAccounts($this->user, $this->importSource, $this->output, []);

		self::assertSame(['accounts' => [], 'mailboxes' => []], $mappings);
	}

	public function testExportRetainsAllMailboxColumnsRestoredOnImport(): void {
		$account = new Account($this->getExportableMailAccount());
		$this->serviceMock->getParameter('accountService')
			->method('findByUserId')
			->willReturn([$account]);
		$this->serviceMock->getParameter('mailboxMapper')
			->method('findAll')
			->willReturn([$this->getInboxMailbox()]);
		$this->serviceMock->getParameter('aliasesService')
			->method('findAll')
			->willReturn([]);

		$exportedContents = null;
		$this->exportDestination->method('addFileContents')
			->willReturnCallback(function (string $path, string $contents) use (&$exportedContents): void {
				$exportedContents = $contents;
			});

		$this->migrationService->exportAccounts($this->user, $this->exportDestination, $this->output);

		$exported = json_decode($exportedContents, true, flags: JSON_THROW_ON_ERROR);
		self::assertEqualsCanonicalizing(
			['databaseId', 'name', 'attributes', 'delimiter', 'specialUse', 'selectable', 'syncInBackground', 'myAcls', 'shared'],
			array_keys($exported['mailboxes'][0]),
		);
	}

	/**
	 * `serialiseAccount()` is a hand-written list, so a column added to
	 * `MailAccount` would silently stop being migrated. Every persisted property
	 * has to be either exported or listed below with a reason.
	 */
	private const NOT_EXPORTED = [
		// belongs to the importing user, never to the archive
		'userId',
		// owned by the admins of the target instance
		'provisioningId',
		// sync and runtime state, recomputed after the first sync
		'lastMailboxSync',
		'quotaPercentage',
		// a per-account diagnostic toggle, not user data
		'debug',
		// only ever set on JMAP accounts, which export skips
		'path',
	];

	/** Archive keys that do not match their property name. */
	private const PROPERTY_TO_ARCHIVE_KEY = [
		'email' => 'emailAddress',
		'oooFollowsSystem' => 'outOfOfficeFollowsSystem',
		'inboundHost' => 'imapHost',
		'inboundPort' => 'imapPort',
		'inboundSslMode' => 'imapSslMode',
		'inboundUser' => 'imapUser',
		'outboundHost' => 'smtpHost',
		'outboundPort' => 'smtpPort',
		'outboundSslMode' => 'smtpSslMode',
		'outboundUser' => 'smtpUser',
	];

	public function testSerialiserCoversEveryPersistedAccountField(): void {
		$account = new Account($this->getExportableMailAccount());
		$this->serviceMock->getParameter('accountService')
			->method('findByUserId')
			->willReturn([$account]);
		$this->serviceMock->getParameter('mailboxMapper')
			->method('findAll')
			->willReturn([]);
		$this->serviceMock->getParameter('aliasesService')
			->method('findAll')
			->willReturn([]);

		$exported = null;
		$this->exportDestination->method('addFileContents')
			->willReturnCallback(function (string $path, string $contents) use (&$exported): void {
				$exported = $contents;
			});

		$this->migrationService->exportAccounts($this->user, $this->exportDestination, $this->output);

		$archive = json_decode($exported, true, flags: JSON_THROW_ON_ERROR);
		$reflection = new \ReflectionClass(MailAccount::class);

		foreach ($reflection->getProperties(\ReflectionProperty::IS_PROTECTED) as $property) {
			if ($property->getDeclaringClass()->getName() !== MailAccount::class) {
				continue;
			}

			$name = $property->getName();
			if (in_array($name, self::NOT_EXPORTED, true)) {
				continue;
			}

			self::assertArrayHasKey(
				self::PROPERTY_TO_ARCHIVE_KEY[$name] ?? $name,
				$archive,
				"MailAccount::\$$name is not in the archive. Add it to serialiseAccount(), "
					. 'or to NOT_EXPORTED with the reason it must not be migrated.',
			);
		}
	}

	public function testImportsAccountWithMailboxesAndAliases(): void {
		$this->expectAccountFileToBeImported($this->getExportedAccountData());
		$this->expectAccountToBeSaved();

		$insertedMailboxes = [];
		$this->serviceMock->getParameter('mailboxMapper')
			->expects(self::exactly(2))
			->method('insert')
			->willReturnCallback(function (Mailbox $mailbox) use (&$insertedMailboxes): Mailbox {
				$mailbox->setId($mailbox->getName() === 'INBOX' ? self::IMPORTED_INBOX_ID : self::IMPORTED_SENT_ID);
				$insertedMailboxes[$mailbox->getName()] = $mailbox;
				return $mailbox;
			});

		$createdAliases = [];
		$this->serviceMock->getParameter('aliasesService')
			->expects(self::once())
			->method('create')
			->willReturnCallback(function (string $userId,
				int $accountId,
				string $alias,
				?string $name,
				?string $signature = null,
				?int $smimeCertificateId = null) use (&$createdAliases): Alias {
				$createdAliases[] = [
					'userId' => $userId,
					'accountId' => $accountId,
					'alias' => $alias,
					'name' => $name,
					'signature' => $signature,
					'smimeCertificateId' => $smimeCertificateId,
				];
				return new Alias();
			});

		$mappings = $this->migrationService->importAccounts(
			$this->user,
			$this->importSource,
			$this->output,
			[11 => 22],
		);

		self::assertSame(
			['accounts' => [self::EXPORTED_ACCOUNT_ID => self::IMPORTED_ACCOUNT_ID],
				'mailboxes' => [self::EXPORTED_INBOX_ID => self::IMPORTED_INBOX_ID, self::EXPORTED_SENT_ID => self::IMPORTED_SENT_ID]],
			$mappings,
		);
		self::assertSame(self::USER_ID, $this->savedAccount->getUserId());
		self::assertSame('jane@doe.com', $this->savedAccount->getEmail());
		self::assertSame(22, $this->savedAccount->getSmimeCertificateId());
		self::assertSame('imap_secret_encrypted', $this->savedAccount->getInboundPassword());
		self::assertSame('smtp_secret_encrypted', $this->savedAccount->getOutboundPassword());
		self::assertSame('sieve_secret_encrypted', $this->savedAccount->getSievePassword());
		self::assertSame([
			['userId' => self::USER_ID,
				'accountId' => self::IMPORTED_ACCOUNT_ID,
				'alias' => 'alias@doe.com',
				'name' => 'Jane Alias',
				'signature' => 'Kind regards',
				'smimeCertificateId' => 22],
		], $createdAliases);
	}

	public function testImportRestoresMailboxColumnsAndSpecialMailboxes(): void {
		$this->expectAccountFileToBeImported($this->getExportedAccountData());
		$this->expectAccountToBeSaved();
		$this->serviceMock->getParameter('aliasesService')
			->method('create')
			->willReturn(new Alias());

		$insertedMailboxes = [];
		$this->serviceMock->getParameter('mailboxMapper')
			->method('insert')
			->willReturnCallback(function (Mailbox $mailbox) use (&$insertedMailboxes): Mailbox {
				$mailbox->setId($mailbox->getName() === 'INBOX' ? self::IMPORTED_INBOX_ID : self::IMPORTED_SENT_ID);
				$insertedMailboxes[$mailbox->getName()] = $mailbox;
				return $mailbox;
			});

		$this->migrationService->importAccounts($this->user, $this->importSource, $this->output, []);

		$inbox = $insertedMailboxes['INBOX'];
		self::assertSame(self::IMPORTED_ACCOUNT_ID, $inbox->getAccountId());
		self::assertSame('["\\\\subscribed"]', $inbox->getAttributes());
		self::assertSame('["\\\\Inbox"]', $inbox->getSpecialUse());
		self::assertSame('.', $inbox->getDelimiter());
		self::assertTrue($inbox->getSelectable());
		self::assertTrue($inbox->getSyncInBackground());
		self::assertSame('lrswipkxte', $inbox->getMyAcls());
		self::assertFalse($inbox->isShared());
		self::assertSame(0, $inbox->getMessages());
		self::assertSame(0, $inbox->getUnseen());
		self::assertSame(md5('INBOX'), $inbox->getNameHash());
		self::assertSame(self::IMPORTED_SENT_ID, $this->savedAccount->getSentMailboxId());
	}

	public function testImportReadsAccountFilesFromInsideTheAccountFolder(): void {
		$this->importSource->method('getFolderListing')
			->with(AccountMigrationService::ACCOUNT_FOLDER)
			->willReturn(['19.json', 'not-an-account.txt']);

		$requestedPaths = [];
		$this->importSource->method('getFileContents')
			->willReturnCallback(function (string $path) use (&$requestedPaths): string {
				$requestedPaths[] = $path;
				return json_encode($this->getExportedAccountData(), JSON_THROW_ON_ERROR);
			});

		$this->expectAccountToBeSaved();
		$this->expectMailboxesAndAliasesToBeImported();

		$this->migrationService->importAccounts($this->user, $this->importSource, $this->output, []);

		self::assertSame([AccountMigrationService::ACCOUNT_FOLDER . '19.json'], $requestedPaths);
	}

	public function testImportSkipsAnUnreadableAccountFileAndKeepsGoing(): void {
		$this->importSource->method('getFolderListing')
			->with(AccountMigrationService::ACCOUNT_FOLDER)
			->willReturn(['broken.json', '19.json']);
		$this->importSource->method('getFileContents')
			->willReturnCallback(function (string $path): string {
				if (str_ends_with($path, 'broken.json')) {
					throw new UserMigrationException('not readable');
				}
				return json_encode($this->getExportedAccountData(), JSON_THROW_ON_ERROR);
			});
		$this->expectAccountToBeSaved();
		$this->expectMailboxesAndAliasesToBeImported();

		$mappings = $this->migrationService->importAccounts($this->user, $this->importSource, $this->output, []);

		self::assertSame([self::EXPORTED_ACCOUNT_ID => self::IMPORTED_ACCOUNT_ID], $mappings['accounts']);
	}

	public function testImportWithoutStoredPasswordsDoesNotEncryptNull(): void {
		$accountData = $this->getExportedAccountData();
		$accountData['inboundPassword'] = null;
		$accountData['outboundPassword'] = null;
		$this->expectAccountFileToBeImported($accountData);
		$this->expectAccountToBeSaved();
		$this->expectMailboxesAndAliasesToBeImported();

		$this->migrationService->importAccounts($this->user, $this->importSource, $this->output, []);

		self::assertNull($this->savedAccount->getInboundPassword());
		self::assertNull($this->savedAccount->getOutboundPassword());
	}

	public function testImportOauthAccountWithoutStoredTokens(): void {
		$accountData = $this->getExportedAccountData();
		$accountData['authMethod'] = 'xoauth2';
		$accountData['inboundPassword'] = null;
		$accountData['outboundPassword'] = null;
		$this->expectAccountFileToBeImported($accountData);
		$this->expectAccountToBeSaved();
		$this->expectMailboxesAndAliasesToBeImported();

		$this->migrationService->importAccounts($this->user, $this->importSource, $this->output, []);

		self::assertNull($this->savedAccount->getOauthRefreshToken());
		self::assertNull($this->savedAccount->getOauthAccessToken());
		self::assertNull($this->savedAccount->getOauthTokenTtl());
	}

	public function testImportKeepsUserNamesForOauthAccounts(): void {
		$accountData = $this->getExportedAccountData();
		$accountData['authMethod'] = 'xoauth2';
		$accountData['oauthRefreshToken'] = 'refresh';
		$accountData['oauthAccessToken'] = 'access';
		$accountData['oauthTokenTtl'] = 3600;
		$this->expectAccountFileToBeImported($accountData);
		$this->expectAccountToBeSaved();
		$this->expectMailboxesAndAliasesToBeImported();

		$this->migrationService->importAccounts($this->user, $this->importSource, $this->output, []);

		self::assertSame('jane', $this->savedAccount->getInboundUser());
		self::assertSame('jane', $this->savedAccount->getOutboundUser());
		self::assertSame('refresh_encrypted', $this->savedAccount->getOauthRefreshToken());
		self::assertSame('access_encrypted', $this->savedAccount->getOauthAccessToken());
		self::assertSame(3600, $this->savedAccount->getOauthTokenTtl());
	}

	public function testImportKeepsTheExportedOrderForAUserWithoutAccounts(): void {
		$accountData = $this->getExportedAccountData();
		$this->expectAccountFileToBeImported($accountData);
		$this->serviceMock->getParameter('accountService')
			->method('findByUserId')
			->with(self::USER_ID)
			->willReturn([]);
		$this->expectAccountToBeSaved();
		$this->expectMailboxesAndAliasesToBeImported();

		$this->migrationService->importAccounts($this->user, $this->importSource, $this->output, []);

		self::assertSame($accountData['order'], $this->savedAccount->getOrder());
		self::assertSame($accountData['signatureMode'], $this->savedAccount->getSignatureMode());
	}

	public function testImportAppendsAccountsAfterTheOnesTheUserAlreadyHas(): void {
		$existing = $this->getExportableMailAccount();
		$existing->setOrder(4);
		$this->serviceMock->getParameter('accountService')
			->method('findByUserId')
			->with(self::USER_ID)
			->willReturn([new Account($existing)]);

		$accountData = $this->getExportedAccountData();
		$accountData['order'] = 2;
		$this->expectAccountFileToBeImported($accountData);
		$this->expectAccountToBeSaved();
		$this->expectMailboxesAndAliasesToBeImported();

		$this->migrationService->importAccounts($this->user, $this->importSource, $this->output, []);

		self::assertSame(7, $this->savedAccount->getOrder());
	}

	public function testExportWritesEveryKeyTheImportRequires(): void {
		$account = new Account($this->getExportableMailAccount());
		$this->serviceMock->getParameter('accountService')
			->method('findByUserId')
			->willReturn([$account]);
		$this->serviceMock->getParameter('mailboxMapper')
			->method('findAll')
			->willReturn([]);
		$this->serviceMock->getParameter('aliasesService')
			->method('findAll')
			->willReturn([]);

		$exported = null;
		$this->exportDestination->method('addFileContents')
			->willReturnCallback(function (string $path, string $contents) use (&$exported): void {
				$exported = $contents;
			});

		$this->migrationService->exportAccounts($this->user, $this->exportDestination, $this->output);

		self::assertEqualsCanonicalizing(
			array_keys($this->getExportedAccountData()),
			array_keys(json_decode($exported, true, flags: JSON_THROW_ON_ERROR)),
		);
	}

	public function testImportRestoresPersonalNamespace(): void {
		$accountData = $this->getExportedAccountData();
		$accountData['personalNamespace'] = 'INBOX.';
		$this->expectAccountFileToBeImported($accountData);
		$this->expectAccountToBeSaved();
		$this->expectMailboxesAndAliasesToBeImported();

		$this->migrationService->importAccounts($this->user, $this->importSource, $this->output, []);

		self::assertSame('INBOX.', $this->savedAccount->getPersonalNamespace());
	}

	public function testImportAliasWithoutName(): void {
		$accountData = $this->getExportedAccountData();
		$accountData['aliases'][0]['name'] = null;
		$this->expectAccountFileToBeImported($accountData);
		$this->expectAccountToBeSaved();

		$names = [];
		$this->serviceMock->getParameter('mailboxMapper')
			->method('insert')
			->willReturnCallback(static function (Mailbox $mailbox): Mailbox {
				$mailbox->setId(self::IMPORTED_INBOX_ID);
				return $mailbox;
			});
		$this->serviceMock->getParameter('aliasesService')
			->method('create')
			->willReturnCallback(function (string $userId,
				int $accountId,
				string $alias,
				?string $name = null) use (&$names): Alias {
				$names[] = $name;
				return new Alias();
			});

		$this->migrationService->importAccounts($this->user, $this->importSource, $this->output, []);

		self::assertSame([null], $names);
	}

	public static function provideInvalidAccountData(): array {
		return [
			'missing account id' => ['accountId'],
			'missing email address' => ['emailAddress'],
			'missing sieve flag' => ['sieveEnabled'],
			'missing special mailbox id' => ['sentMailboxId'],
			'missing mailboxes' => ['mailboxes'],
			'missing aliases' => ['aliases'],
		];
	}

	/**
	 * @dataProvider provideInvalidAccountData
	 */
	public function testImportSkipsAccountsMissingARequiredKey(string $key): void {
		$accountData = $this->getExportedAccountData();
		unset($accountData[$key]);

		$this->expectAccountFileToBeImported($accountData);
		$this->serviceMock->getParameter('accountService')
			->expects(self::never())
			->method('save');

		$mappings = $this->migrationService->importAccounts($this->user, $this->importSource, $this->output, []);

		self::assertSame(['accounts' => [], 'mailboxes' => []], $mappings);
	}

	public static function provideMalformedAccountData(): array {
		return [
			'not an object' => ['not-an-account'],
			'account id is not an int' => [['accountId' => 'nineteen']],
			'mailboxes are not a list' => [['mailboxes' => ['unexpected' => 'object']]],
			'aliases are not a list' => [['aliases' => ['unexpected' => 'object']]],
			'mailbox is not an object' => [['mailboxes' => ['not-a-mailbox']]],
			'alias is not an object' => [['aliases' => ['not-an-alias']]],
		];
	}

	/**
	 * @dataProvider provideMalformedAccountData
	 */
	public function testImportSkipsMalformedAccounts(mixed $payload): void {
		$accountData = is_array($payload) ? array_merge($this->getExportedAccountData(), $payload) : $payload;

		$this->expectAccountFileToBeImported($accountData);
		$this->serviceMock->getParameter('accountService')
			->expects(self::never())
			->method('save');

		$mappings = $this->migrationService->importAccounts($this->user, $this->importSource, $this->output, []);

		self::assertSame(['accounts' => [], 'mailboxes' => []], $mappings);
	}

	public function testImportSkipsMailboxesWithMissingColumns(): void {
		$accountData = $this->getExportedAccountData();
		unset($accountData['mailboxes'][0]['selectable']);
		$this->expectAccountFileToBeImported($accountData);

		$this->serviceMock->getParameter('accountService')
			->expects(self::never())
			->method('save');

		$mappings = $this->migrationService->importAccounts($this->user, $this->importSource, $this->output, []);

		self::assertSame(['accounts' => [], 'mailboxes' => []], $mappings);
	}

	public function testExportWithoutAccounts(): void {
		$this->serviceMock->getParameter('accountService')
			->method('findByUserId')
			->with(self::USER_ID)
			->willReturn([]);

		$this->exportDestination->expects(self::never())
			->method('addFileContents');

		$this->migrationService->exportAccounts($this->user, $this->exportDestination, $this->output);
	}

	public function testImportWithoutAccountFolderImportsNothing(): void {
		$this->importSource->method('getFolderListing')
			->with(AccountMigrationService::ACCOUNT_FOLDER)
			->willReturn([]);
		$this->serviceMock->getParameter('accountService')
			->expects(self::never())
			->method('save');

		$mappings = $this->migrationService->importAccounts($this->user, $this->importSource, $this->output, []);

		self::assertSame(['accounts' => [], 'mailboxes' => []], $mappings);
	}

	public function testImportKeepsAliasCertificateEmptyWhenCertificateWasNotImported(): void {
		$this->expectAccountFileToBeImported($this->getExportedAccountData());
		$this->expectAccountToBeSaved();
		$this->serviceMock->getParameter('mailboxMapper')
			->method('insert')
			->willReturnCallback(static function (Mailbox $mailbox): Mailbox {
				$mailbox->setId(self::IMPORTED_INBOX_ID);
				return $mailbox;
			});

		$certificateIds = [];
		$this->serviceMock->getParameter('aliasesService')
			->method('create')
			->willReturnCallback(function (string $userId,
				int $accountId,
				string $alias,
				?string $name,
				?string $signature = null,
				?int $smimeCertificateId = null) use (&$certificateIds): Alias {
				$certificateIds[] = $smimeCertificateId;
				return new Alias();
			});

		$this->migrationService->importAccounts($this->user, $this->importSource, $this->output, []);

		self::assertSame([null], $certificateIds);
	}

	public function testImportSkipsTheFailingAccountAndKeepsGoing(): void {
		$this->expectAccountFileToBeImported($this->getExportedAccountData());
		$this->expectAccountToBeSaved();
		$this->serviceMock->getParameter('aliasesService')
			->method('create')
			->willThrowException(new Exception('alias insert failed'));

		$this->serviceMock->getParameter('connection')
			->expects(self::once())
			->method('beginTransaction');
		$this->serviceMock->getParameter('connection')
			->expects(self::once())
			->method('rollBack');
		$this->serviceMock->getParameter('connection')
			->expects(self::never())
			->method('commit');

		$this->serviceMock->getParameter('logger')
			->expects(self::once())
			->method('error')
			->with(self::stringContains(self::USER_ID), self::arrayHasKey('exception'));

		$mappings = $this->migrationService->importAccounts($this->user, $this->importSource, $this->output, []);

		self::assertSame(['accounts' => [], 'mailboxes' => []], $mappings);
	}

	public function testImportCommitsOneTransactionPerAccount(): void {
		$secondAccountData = $this->getExportedAccountData();
		$secondAccountData['accountId'] = 20;
		$secondAccountData['mailboxes'] = [];
		$secondAccountData['aliases'] = [];
		$this->expectAccountFileToBeImported($this->getExportedAccountData(), $secondAccountData);

		$savedIds = [self::IMPORTED_ACCOUNT_ID, 43];
		$saveCount = 0;
		$this->serviceMock->getParameter('accountService')
			->method('save')
			->willReturnCallback(function (MailAccount $account, bool $scheduleBackgroundJobs = true) use ($savedIds, &$saveCount): MailAccount {
				$account->setId($savedIds[$saveCount++]);
				return $account;
			});
		$this->serviceMock->getParameter('mailboxMapper')
			->method('insert')
			->willReturnCallback(function (Mailbox $mailbox): Mailbox {
				$mailbox->setId($mailbox->getName() === 'INBOX' ? self::IMPORTED_INBOX_ID : self::IMPORTED_SENT_ID);
				return $mailbox;
			});
		$this->serviceMock->getParameter('aliasesService')
			->method('create')
			->willReturn(new Alias());

		$this->serviceMock->getParameter('connection')
			->expects(self::exactly(2))
			->method('beginTransaction');
		$this->serviceMock->getParameter('connection')
			->expects(self::exactly(2))
			->method('commit');

		$mappings = $this->migrationService->importAccounts($this->user, $this->importSource, $this->output, []);

		self::assertSame(
			[self::EXPORTED_ACCOUNT_ID => self::IMPORTED_ACCOUNT_ID, 20 => 43],
			$mappings['accounts'],
		);
		self::assertSame(
			[self::EXPORTED_INBOX_ID => self::IMPORTED_INBOX_ID, self::EXPORTED_SENT_ID => self::IMPORTED_SENT_ID],
			$mappings['mailboxes'],
		);
	}

	public function testScheduleBackgroundJobs(): void {
		$scheduledIds = [];
		$this->serviceMock->getParameter('accountService')
			->expects(self::exactly(2))
			->method('scheduleBackgroundJobs')
			->willReturnCallback(function (int $accountId) use (&$scheduledIds) {
				$scheduledIds[] = $accountId;
			});

		$this->migrationService->scheduleBackgroundJobs(
			[19 => 101, 20 => 102],
			$this->output,
		);

		self::assertSame([101, 102], $scheduledIds);
	}

	public function testScheduleBackgroundJobsWithoutImportedAccounts(): void {
		$this->serviceMock->getParameter('accountService')
			->expects(self::never())
			->method('scheduleBackgroundJobs');

		$this->migrationService->scheduleBackgroundJobs([], $this->output);
	}

	/**
	 * `IImportSource::getFolderListing()` returns names relative to the folder,
	 * so the stub must not hand out full paths.
	 */
	private function expectAccountFileToBeImported(mixed ...$accountData): void {
		$fileNames = [];
		$contentsByPath = [];
		foreach ($accountData as $index => $data) {
			$fileName = (is_array($data) ? $data['accountId'] ?? $index : $index) . '.json';
			$fileNames[] = $fileName;
			$contentsByPath[AccountMigrationService::ACCOUNT_FOLDER . $fileName] = json_encode($data, JSON_THROW_ON_ERROR);
		}

		$this->importSource->method('getFolderListing')
			->with(AccountMigrationService::ACCOUNT_FOLDER)
			->willReturn($fileNames);
		$this->importSource->method('getFileContents')
			->willReturnCallback(static function (string $path) use ($contentsByPath): string {
				self::assertArrayHasKey($path, $contentsByPath);
				return $contentsByPath[$path];
			});
	}

	private function expectMailboxesAndAliasesToBeImported(): void {
		$this->serviceMock->getParameter('mailboxMapper')
			->method('insert')
			->willReturnCallback(static function (Mailbox $mailbox): Mailbox {
				$mailbox->setId($mailbox->getName() === 'INBOX' ? self::IMPORTED_INBOX_ID : self::IMPORTED_SENT_ID);
				return $mailbox;
			});
		$this->serviceMock->getParameter('aliasesService')
			->method('create')
			->willReturn(new Alias());
	}

	private function expectAccountToBeSaved(): void {
		$this->serviceMock->getParameter('accountService')
			->method('save')
			->willReturnCallback(function (MailAccount $account, bool $scheduleBackgroundJobs = true): MailAccount {
				self::assertFalse($scheduleBackgroundJobs);
				$account->setId(self::IMPORTED_ACCOUNT_ID);
				$this->savedAccount = $account;
				return $account;
			});
	}

	private function getExportableMailAccount(): MailAccount {
		$mailAccount = new MailAccount();

		$mailAccount->setId(self::EXPORTED_ACCOUNT_ID);
		$mailAccount->setUserId(self::USER_ID);
		$mailAccount->setName('Jane Doe');
		$mailAccount->setEmail('jane@doe.com');
		$mailAccount->setProtocol(MailAccount::PROTOCOL_IMAP);
		$mailAccount->setAuthMethod('password');
		$mailAccount->setInboundHost('imap.doe.com');
		$mailAccount->setInboundPort(993);
		$mailAccount->setInboundUser('jane');
		$mailAccount->setInboundSslMode('ssl');
		$mailAccount->setInboundPassword('imap_secret_encrypted');
		$mailAccount->setOutboundHost('smtp.doe.com');
		$mailAccount->setOutboundPort(465);
		$mailAccount->setOutboundUser('jane');
		$mailAccount->setOutboundSslMode('ssl');
		$mailAccount->setOutboundPassword('smtp_secret_encrypted');
		$mailAccount->setSieveEnabled(true);
		$mailAccount->setSieveHost('sieve.doe.com');
		$mailAccount->setSievePort(4190);
		$mailAccount->setSieveUser('jane');
		$mailAccount->setSieveSslMode('tls');
		$mailAccount->setSievePassword('sieve_secret_encrypted');

		return $mailAccount;
	}

	private function getInboxMailbox(): Mailbox {
		$mailbox = new Mailbox();

		$mailbox->setId(self::EXPORTED_INBOX_ID);
		$mailbox->setAccountId(self::EXPORTED_ACCOUNT_ID);
		$mailbox->setName('INBOX');
		$mailbox->setNameHash(md5('INBOX'));
		$mailbox->setAttributes('["\\\\subscribed"]');
		$mailbox->setSpecialUse('["\\\\Inbox"]');
		$mailbox->setDelimiter('.');
		$mailbox->setSelectable(true);
		$mailbox->setSyncInBackground(true);
		$mailbox->setMyAcls('lrswipkxte');
		$mailbox->setShared(false);
		$mailbox->setMessages(12);
		$mailbox->setUnseen(3);

		return $mailbox;
	}

	private function getAlias(): Alias {
		$alias = new Alias();

		$alias->setId(5);
		$alias->setAccountId(self::EXPORTED_ACCOUNT_ID);
		$alias->setAlias('alias@doe.com');
		$alias->setName('Jane Alias');
		$alias->setSignature('Kind regards');
		$alias->setSmimeCertificateId(11);

		return $alias;
	}

	private function getExportedAccountData(): array {
		return [
			'accountId' => self::EXPORTED_ACCOUNT_ID,
			'name' => 'Jane Doe',
			'emailAddress' => 'jane@doe.com',
			'order' => 3,
			'showSubscribedOnly' => true,
			'smimeCertificateId' => 11,
			'editorMode' => 'richtext',
			'trashRetentionDays' => 60,
			'outOfOfficeFollowsSystem' => true,
			'imipCreate' => false,
			'classificationEnabled' => true,
			'searchBody' => false,
			'personalNamespace' => 'INBOX.',
			'signature' => 'Jane',
			'signatureAboveQuote' => true,
			'signatureMode' => 1,
			'protocol' => MailAccount::PROTOCOL_IMAP,
			'authMethod' => 'password',
			'imapHost' => 'imap.doe.com',
			'imapPort' => 993,
			'imapSslMode' => 'ssl',
			'imapUser' => 'jane',
			'inboundPassword' => 'imap_secret',
			'smtpHost' => 'smtp.doe.com',
			'smtpPort' => 465,
			'smtpSslMode' => 'ssl',
			'smtpUser' => 'jane',
			'outboundPassword' => 'smtp_secret',
			'oauthRefreshToken' => null,
			'oauthAccessToken' => null,
			'oauthTokenTtl' => null,
			'sieveEnabled' => true,
			'sieveHost' => 'sieve.doe.com',
			'sievePort' => 4190,
			'sieveSslMode' => 'tls',
			'sieveUser' => 'jane',
			'sievePassword' => 'sieve_secret',
			'draftsMailboxId' => null,
			'sentMailboxId' => self::EXPORTED_SENT_ID,
			'trashMailboxId' => null,
			'archiveMailboxId' => null,
			'junkMailboxId' => null,
			'snoozeMailboxId' => null,
			'mailboxes' => [
				['databaseId' => self::EXPORTED_INBOX_ID,
					'name' => 'INBOX',
					'attributes' => '["\\\\subscribed"]',
					'delimiter' => '.',
					'specialUse' => '["\\\\Inbox"]',
					'selectable' => true,
					'syncInBackground' => true,
					'myAcls' => 'lrswipkxte',
					'shared' => false],
				['databaseId' => self::EXPORTED_SENT_ID,
					'name' => 'Sent',
					'attributes' => '[]',
					'delimiter' => '.',
					'specialUse' => '["\\\\Sent"]',
					'selectable' => true,
					'syncInBackground' => false,
					'myAcls' => null,
					'shared' => false],
			],
			'aliases' => [
				['alias' => 'alias@doe.com',
					'name' => 'Jane Alias',
					'signature' => 'Kind regards',
					'smimeCertificateId' => 11],
			],
		];
	}
}
