<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Unit\UserMigration\Service;

use ChristophWurst\Nextcloud\Testing\ServiceMockObject;
use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Db\Alias;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\UserMigration\Service\LegacyAccountMigrationService;
use OCP\IUser;
use OCP\UserMigration\IImportSource;
use OCP\UserMigration\UserMigrationException;
use Symfony\Component\Console\Output\OutputInterface;

class LegacyAccountMigrationServiceTest extends TestCase {
	private const USER_ID = 'test_user';
	private const EXPORTED_ACCOUNT_ID = 19;
	private const IMPORTED_ACCOUNT_ID = 42;
	private const ACCOUNT_PATH = 'mail/accounts/19.json';

	private OutputInterface $output;
	private IUser $user;
	private IImportSource $importSource;
	private ServiceMockObject $serviceMock;
	private LegacyAccountMigrationService $migrationService;
	private ?MailAccount $savedAccount = null;

	protected function setUp(): void {
		parent::setUp();

		$this->output = $this->createMock(OutputInterface::class);
		$this->importSource = $this->createMock(IImportSource::class);

		$this->user = $this->createMock(IUser::class);
		$this->user->method('getUID')->willReturn(self::USER_ID);

		$this->serviceMock = $this->createServiceMock(LegacyAccountMigrationService::class);
		$this->serviceMock->getParameter('crypto')
			->method('encrypt')
			->willReturnCallback(static fn (string $value): string => $value . '_encrypted');

		$this->migrationService = $this->serviceMock->getService();
	}

	public function testImportsAccountsAndAliases(): void {
		$this->expectArchive([self::EXPORTED_ACCOUNT_ID => self::ACCOUNT_PATH], $this->getV1AccountData());
		$this->expectAccountToBeSaved();

		$createdAliases = [];
		$this->serviceMock->getParameter('aliasesService')
			->method('create')
			->willReturnCallback(function (string $userId,
				int $accountId,
				string $alias,
				?string $name = null,
				?string $signature = null) use (&$createdAliases): Alias {
				$createdAliases[] = ['alias' => $alias, 'name' => $name, 'signature' => $signature];
				return new Alias();
			});

		$mapping = $this->migrationService->importAccounts($this->user, $this->importSource, $this->output);

		self::assertSame([self::EXPORTED_ACCOUNT_ID => self::IMPORTED_ACCOUNT_ID], $mapping);
		self::assertSame('jane@doe.com', $this->savedAccount->getEmail());
		self::assertSame('imap.doe.com', $this->savedAccount->getInboundHost());
		self::assertSame('imap_secret_encrypted', $this->savedAccount->getInboundPassword());
		self::assertSame('smtp_secret_encrypted', $this->savedAccount->getOutboundPassword());
		self::assertSame([
			['alias' => 'alias@doe.com', 'name' => 'Jane Alias', 'signature' => 'Kind regards'],
		], $createdAliases);
	}

	public function testImportDropsCertificateIdsThatVersionOneNeverExported(): void {
		$accountData = $this->getV1AccountData();
		$accountData['smimeCertificateId'] = 11;
		$this->expectArchive([self::EXPORTED_ACCOUNT_ID => self::ACCOUNT_PATH], $accountData);
		$this->expectAccountToBeSaved();
		$this->serviceMock->getParameter('aliasesService')->method('create')->willReturn(new Alias());

		$this->migrationService->importAccounts($this->user, $this->importSource, $this->output);

		self::assertNull($this->savedAccount->getSmimeCertificateId());
	}

	public function testImportLeavesSpecialMailboxesUnsetBecauseVersionOneHadNone(): void {
		$this->expectArchive([self::EXPORTED_ACCOUNT_ID => self::ACCOUNT_PATH], $this->getV1AccountData());
		$this->expectAccountToBeSaved();
		$this->serviceMock->getParameter('aliasesService')->method('create')->willReturn(new Alias());

		$this->migrationService->importAccounts($this->user, $this->importSource, $this->output);

		self::assertNull($this->savedAccount->getSentMailboxId());
		self::assertNull($this->savedAccount->getDraftsMailboxId());
	}

	public function testImportAppendsAfterExistingAccounts(): void {
		$existing = new MailAccount();
		$existing->setOrder(4);
		$this->serviceMock->getParameter('accountService')
			->method('findByUserId')
			->willReturn([new Account($existing)]);

		$accountData = $this->getV1AccountData();
		$accountData['order'] = 2;
		$this->expectArchive([self::EXPORTED_ACCOUNT_ID => self::ACCOUNT_PATH], $accountData);
		$this->expectAccountToBeSaved();
		$this->serviceMock->getParameter('aliasesService')->method('create')->willReturn(new Alias());

		$this->migrationService->importAccounts($this->user, $this->importSource, $this->output);

		self::assertSame(7, $this->savedAccount->getOrder());
	}

	public function testImportWithoutIndexImportsNothing(): void {
		$this->importSource->method('getFileContents')
			->with(LegacyAccountMigrationService::ACCOUNT_INDEX_FILE)
			->willThrowException(new UserMigrationException());
		$this->serviceMock->getParameter('accountService')->expects(self::never())->method('save');

		$mapping = $this->migrationService->importAccounts($this->user, $this->importSource, $this->output);

		self::assertSame([], $mapping);
	}

	public static function provideUnusableAccountFiles(): array {
		return [
			'not valid JSON' => ['this is not json {{{'],
			'not an object' => ['"just a string"'],
			'missing email address' => ['{"imapHost":"imap.doe.com"}'],
			'missing imap host' => ['{"emailAddress":"jane@doe.com"}'],
			'incomplete imap settings' => ['{"emailAddress":"jane@doe.com","imapHost":"imap.doe.com"}'],
		];
	}

	/**
	 * @dataProvider provideUnusableAccountFiles
	 */
	public function testImportSkipsUnusableAccountFiles(string $contents): void {
		$this->importSource->method('getFileContents')
			->willReturnCallback(static function (string $path) use ($contents): string {
				return $path === LegacyAccountMigrationService::ACCOUNT_INDEX_FILE
					? json_encode([self::EXPORTED_ACCOUNT_ID => self::ACCOUNT_PATH])
					: $contents;
			});
		$this->serviceMock->getParameter('accountService')->expects(self::never())->method('save');

		$mapping = $this->migrationService->importAccounts($this->user, $this->importSource, $this->output);

		self::assertSame([], $mapping);
	}

	public function testImportSkipsNonImapAccounts(): void {
		$accountData = $this->getV1AccountData();
		$accountData['protocol'] = MailAccount::PROTOCOL_JMAP;
		$this->expectArchive([self::EXPORTED_ACCOUNT_ID => self::ACCOUNT_PATH], $accountData);
		$this->serviceMock->getParameter('accountService')->expects(self::never())->method('save');

		$mapping = $this->migrationService->importAccounts($this->user, $this->importSource, $this->output);

		self::assertSame([], $mapping);
	}

	private function expectArchive(array $index, array $accountData): void {
		$this->importSource->method('getFileContents')
			->willReturnCallback(static function (string $path) use ($index, $accountData): string {
				return $path === LegacyAccountMigrationService::ACCOUNT_INDEX_FILE
					? json_encode($index, JSON_THROW_ON_ERROR)
					: json_encode($accountData, JSON_THROW_ON_ERROR);
			});
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

	/**
	 * The shape version 1 wrote: `MailAccount::toJson()` of the time, with the
	 * special mailbox ids removed and decrypted secrets added.
	 */
	private function getV1AccountData(): array {
		return [
			'id' => self::EXPORTED_ACCOUNT_ID,
			'accountId' => self::EXPORTED_ACCOUNT_ID,
			'name' => 'Jane Doe',
			'emailAddress' => 'jane@doe.com',
			'order' => 0,
			'authMethod' => 'password',
			'imapHost' => 'imap.doe.com',
			'imapPort' => 993,
			'imapUser' => 'jane',
			'imapSslMode' => 'ssl',
			'inboundPassword' => 'imap_secret',
			'smtpHost' => 'smtp.doe.com',
			'smtpPort' => 465,
			'smtpUser' => 'jane',
			'smtpSslMode' => 'ssl',
			'outboundPassword' => 'smtp_secret',
			'signature' => 'Jane',
			'signatureAboveQuote' => true,
			'editorMode' => 'richtext',
			'showSubscribedOnly' => true,
			'personalNamespace' => 'INBOX.',
			'provisioningId' => null,
			'sieveEnabled' => false,
			'aliases' => [
				['id' => 5,
					'name' => 'Jane Alias',
					'alias' => 'alias@doe.com',
					'signature' => 'Kind regards',
					'provisioned' => false,
					'signatureMode' => 0,
					'smimeCertificateId' => null],
			],
		];
	}
}
