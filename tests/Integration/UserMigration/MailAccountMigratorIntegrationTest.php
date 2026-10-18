<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Integration\UserMigration;

use ChristophWurst\Nextcloud\Testing\DatabaseTransaction;
use ChristophWurst\Nextcloud\Testing\TestCase;
use ChristophWurst\Nextcloud\Testing\TestUser;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\AliasesService;
use OCA\Mail\UserMigration\MailAccountMigrator;
use OCA\Mail\UserMigration\Service\AccountMigrationService;
use OCP\IUser;
use OCP\Security\ICrypto;
use OCP\Server;
use OCP\UserMigration\IExportDestination;
use OCP\UserMigration\IImportSource;
use OCP\UserMigration\UserMigrationException;
use Symfony\Component\Console\Output\OutputInterface;
use function array_key_exists;

class MailAccountMigratorIntegrationTest extends TestCase {

	use DatabaseTransaction;
	use TestUser;

	private AccountService $accountService;
	private AliasesService $aliasesService;
	private MailboxMapper $mailboxMapper;
	private MailAccountMigrator $migrator;
	private OutputInterface $output;

	/** @var array<string, string> */
	private array $archive = [];

	protected function setUp(): void {
		parent::setUp();

		$this->accountService = Server::get(AccountService::class);
		$this->aliasesService = Server::get(AliasesService::class);
		$this->mailboxMapper = Server::get(MailboxMapper::class);
		$this->migrator = Server::get(MailAccountMigrator::class);
		$this->output = $this->createStub(OutputInterface::class);
	}

	public function testMigrateAccountWithMailboxesAndAliases(): void {
		$sourceUser = $this->createTestUser();
		$destinationUser = $this->createTestUser();

		$sourceAccount = $this->createAccount($sourceUser);
		$this->createMailbox($sourceAccount, 'INBOX');
		$this->createMailbox($sourceAccount, 'Sent');
		$this->aliasesService->create(
			$sourceUser->getUID(),
			$sourceAccount->getId(),
			'alias@domain.tld',
			'Alias',
			'Kind regards',
		);

		$this->migrator->export($sourceUser, $this->exportDestination(), $this->output);
		$this->migrator->import($destinationUser, $this->importSource(), $this->output);

		$importedAccounts = $this->accountService->findByUserId($destinationUser->getUID());
		self::assertCount(1, $importedAccounts);

		$importedAccount = $importedAccounts[0];
		self::assertSame($sourceAccount->getEmail(), $importedAccount->getEmail());
		self::assertSame($destinationUser->getUID(), $importedAccount->getUserId());
		self::assertNotSame($sourceAccount->getId(), $importedAccount->getId());

		$importedMailboxes = $this->mailboxMapper->findAll($importedAccount);
		self::assertCount(2, $importedMailboxes);
		self::assertEqualsCanonicalizing(
			['INBOX', 'Sent'],
			array_map(static fn (Mailbox $mailbox): string => $mailbox->getName(), $importedMailboxes),
		);

		$importedAliases = $this->aliasesService->findAll($importedAccount->getId(), $destinationUser->getUID());
		self::assertCount(1, $importedAliases);
		self::assertSame('alias@domain.tld', $importedAliases[0]->getAlias());
		self::assertSame('Kind regards', $importedAliases[0]->getSignature());
	}

	public function testMigrateUserWithoutAccounts(): void {
		$sourceUser = $this->createTestUser();
		$destinationUser = $this->createTestUser();

		$this->migrator->export($sourceUser, $this->exportDestination(), $this->output);

		// The archive has to be written even with nothing to migrate, otherwise
		// the assertion below would hold before import() has done anything.
		self::assertNotEmpty($this->archive);
		self::assertSame(
			[],
			array_filter(array_keys($this->archive), static fn (string $path): bool
				=> str_starts_with($path, AccountMigrationService::ACCOUNT_FOLDER)),
		);

		$this->migrator->import($destinationUser, $this->importSource(), $this->output);

		self::assertCount(0, $this->accountService->findByUserId($destinationUser->getUID()));
	}

	public function testImportsAVersionOneArchive(): void {
		$destinationUser = $this->createTestUser();

		// exactly what migrator version 1 wrote: an index of full paths, and per
		// account `MailAccount::toJson()` without any mailbox ids
		$this->archive = [
			'mail/accounts/index.json' => json_encode([19 => 'mail/accounts/19.json']),
			'mail/accounts/19.json' => json_encode([
				'id' => 19,
				'accountId' => 19,
				'name' => 'Legacy Account',
				'emailAddress' => 'legacy@domain.tld',
				'authMethod' => 'password',
				'imapHost' => 'imap.domain.tld',
				'imapPort' => 993,
				'imapUser' => 'legacy@domain.tld',
				'imapSslMode' => 'ssl',
				'inboundPassword' => 'legacy-imap-secret',
				'smtpHost' => 'smtp.domain.tld',
				'smtpPort' => 465,
				'smtpUser' => 'legacy@domain.tld',
				'smtpSslMode' => 'ssl',
				'outboundPassword' => 'legacy-smtp-secret',
				'signature' => 'Sent from a v1 export',
				'sieveEnabled' => false,
				'smimeCertificateId' => 77,
				'aliases' => [
					['id' => 5, 'name' => 'Legacy Alias', 'alias' => 'legacy-alias@domain.tld',
						'signature' => 'Alias signature', 'provisioned' => false,
						'signatureMode' => 0, 'smimeCertificateId' => null],
				],
			]),
		];

		$this->migrator->import($destinationUser, $this->legacyImportSource(), $this->output);

		$accounts = $this->accountService->findByUserId($destinationUser->getUID());
		self::assertCount(1, $accounts);

		$imported = $accounts[0]->getMailAccount();
		self::assertSame('legacy@domain.tld', $imported->getEmail());
		self::assertSame('imap.domain.tld', $imported->getInboundHost());
		self::assertSame('Sent from a v1 export', $imported->getSignature());
		self::assertSame(
			'legacy-imap-secret',
			Server::get(ICrypto::class)->decrypt($imported->getInboundPassword()),
		);

		// version 1 never exported certificates, so the id it carries is dangling
		self::assertNull($imported->getSmimeCertificateId());
		self::assertNull($imported->getSentMailboxId());
		self::assertCount(0, $this->mailboxMapper->findAll($accounts[0]));

		$aliases = $this->aliasesService->findAll($imported->getId(), $destinationUser->getUID());
		self::assertCount(1, $aliases);
		self::assertSame('legacy-alias@domain.tld', $aliases[0]->getAlias());
		self::assertSame('Alias signature', $aliases[0]->getSignature());
	}

	private function legacyImportSource(): IImportSource {
		$importSource = $this->importSource();
		$importSource->method('getMigratorVersion')->willReturn(MailAccountMigrator::LEGACY_VERSION);

		return $importSource;
	}

	public function testEstimatedExportSizeGrowsWithTheAccount(): void {
		$user = $this->createTestUser();
		$emptySize = $this->migrator->getEstimatedExportSize($user);

		$account = $this->createAccount($user);
		$this->createMailbox($account, 'INBOX');

		self::assertGreaterThan($emptySize, $this->migrator->getEstimatedExportSize($user));
	}

	private function createAccount(IUser $user): MailAccount {
		$account = new MailAccount();
		$account->setUserId($user->getUID());
		$account->setName('Test Account');
		$account->setEmail('test@domain.tld');
		$account->setAuthMethod('password');
		$account->setInboundHost('imap.domain.tld');
		$account->setInboundPort(993);
		$account->setInboundSslMode('ssl');
		$account->setInboundUser('test@domain.tld');
		$account->setOutboundHost('smtp.domain.tld');
		$account->setOutboundPort(465);
		$account->setOutboundSslMode('ssl');
		$account->setOutboundUser('test@domain.tld');

		return $this->accountService->save($account, false);
	}

	private function createMailbox(MailAccount $account, string $name): void {
		$mailbox = new Mailbox();
		$mailbox->setAccountId($account->getId());
		$mailbox->setName($name);
		$mailbox->setNameHash(md5($name));
		$mailbox->setAttributes('[]');
		$mailbox->setSpecialUse('[]');
		$mailbox->setDelimiter('.');
		$mailbox->setMessages(0);
		$mailbox->setUnseen(0);
		$mailbox->setSelectable(true);
		$mailbox->setSyncInBackground(true);
		$mailbox->setShared(false);

		$this->mailboxMapper->insert($mailbox);
	}

	private function exportDestination(): IExportDestination {
		$exportDestination = $this->createMock(IExportDestination::class);
		$exportDestination->method('addFileContents')
			->willReturnCallback(function (string $path, string $contents): void {
				$this->archive[$path] = $contents;
			});

		return $exportDestination;
	}

	/**
	 * Mirrors the archive semantics the real import source has: folder listings
	 * yield names relative to the folder, and only files exist as entries.
	 */
	private function importSource(): IImportSource {
		$importSource = $this->createMock(IImportSource::class);
		$importSource->method('getFileContents')
			->willReturnCallback(function (string $path): string {
				if (!array_key_exists($path, $this->archive)) {
					$available = implode(', ', array_keys($this->archive));
					throw new UserMigrationException("File contents for $path not found. Available: $available");
				}
				return $this->archive[$path];
			});
		// ZIP::fileExists() locates an exact entry, and the export writes no
		// directory entries, so a folder path never exists in a real archive.
		$importSource->method('pathExists')
			->willReturnCallback(fn (string $path): bool => array_key_exists($path, $this->archive));
		$importSource->method('getFolderListing')
			->willReturnCallback(function (string $path): array {
				$names = [];
				foreach (array_keys($this->archive) as $archivedPath) {
					if (str_starts_with($archivedPath, $path)) {
						$names[] = substr($archivedPath, strlen($path));
					}
				}
				return $names;
			});

		return $importSource;
	}

}
