<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Unit;

use ChristophWurst\Nextcloud\Testing\ServiceMockObject;
use ChristophWurst\Nextcloud\Testing\TestCase;
use Exception;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Service\AccountService;
use OCA\Mail\UserMigration\Service\AccountMigrationService;
use OCP\IUser;
use OCP\UserMigration\IExportDestination;
use OCP\UserMigration\IImportSource;
use OCP\UserMigration\UserMigrationException;
use Symfony\Component\Console\Output\OutputInterface;

class AccountMigrationServiceTest extends TestCase {
	private AccountMigrationService $migrator;
	/** @var ServiceMockObject<AccountMigrationService> */
	private ServiceMockObject $serviceMock;
	private OutputInterface|MockObject $output;

	protected function setUp(): void {
		parent::setUp();

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
		$this->migrator = $this->serviceMock->getService();

		$this->output = $this->createMock(OutputInterface::class);
	}

	public function testExportBasicAccountInfo(): void {
		$user = $this->createStub(IUser::class);
		$user->method('getUID')->willReturn('user_export');
		$mailAccount1 = new MailAccount([]);
		$account1 = $this->createStub(Account::class);
		$account1->method('getId')->willReturn(101);
		$account1->method('getUserId')->willReturn('user_export');
		$account1->method('getMailAccount')->willReturn($mailAccount1);
		// $account1->method('jsonSerialize')->willReturnSelf();
		$mailAccount1->setAuthMethod('password');
		$mailAccount1->setInboundPassword('imap_pass_encrypted');
		$mailAccount1->setOutboundPassword('smtp_pass_encrypted');
		$account1->method('jsonSerialize')->willReturn([
			'id' => 101,
			'emailAddress' => 'jane@doe.org',
		]);
		$mailAccount2 = new MailAccount();
		$mailAccount2->setUserId('user_export');
		$mailAccount2->setId(102);
		$mailAccount2->setAuthMethod('password');
		$mailAccount2->setInboundPassword('imap_pass_encrypted');
		$mailAccount2->setOutboundPassword('smtp_pass_encrypted');
		$account2 = new Account($mailAccount2);
		//		$account2->method('jsonSerialize')->willReturn([
		//			'id' => 102,
		//			'emailAddress' => 'jane@doe.com',
		//		]);
		/** @var AccountService|MockObject $accountService */
		$accountService = $this->serviceMock->getParameter('accountService');
		$accountService->expects(self::once())
			->method('findByUserId')
			->with('user_export')
			->willReturn([
				$account1,
				$account2,
			]);
		$exportDestination = $this->createMock(IExportDestination::class);
		$exportDestination->method('addFileContents')
			->willReturnCallback(function (string $path, string $content) {
				if ($path === 'mail/accounts/index.json') {
					self::assertSame(
						[
							101 => 'mail/accounts/101.json',
							102 => 'mail/accounts/102.json',
						],
						json_decode($content, true)
					);
				} elseif ($path === 'mail/accounts/101.json') {
					$accountData = json_decode($content, true);
					self::assertArrayHasKey('id', $accountData);
					self::assertSame(101, $accountData['id']);
					self::assertArrayHasKey('inboundPassword', $accountData);
					self::assertSame('imap_423pass', $accountData['inboundPassword']);
				} elseif ($path === 'mail/accounts/102.json') {
					$accountData = json_decode($content, true);
					self::assertArrayHasKey('id', $accountData);
					self::assertSame(102, $accountData['id']);
					self::assertArrayHasKey('inboundPassword', $accountData);
					self::assertSame('imap_pass', $accountData['inboundPassword']);
				} else {
					$this->fail('Invalid file content path ' . $path);
				}
			});

		$this->migrator->exportAccounts(
			$user,
			$exportDestination,
			$this->output,
		);
	}

	public function testImportInvalidIndex(): void {
		$this->expectException(UserMigrationException::class);
		$user = $this->createMock(IUser::class);

		$importSource = $this->createMock(IImportSource::class);
		$importSource->method('getFileContents')
			->with('mail/accounts/index.json')
			->willReturn('fail');

		$this->migrator->importAccounts(
			$user,
			$importSource,
			[],
			$this->output,
		);
	}

	public function testImportBasicAccountInfo(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user_import');
		$accountData = [
			'id' => 101,
			'userId' => 'user_export',
			'name' => 'Jane Doe',
			'emailAddress' => 'jane@doe.org',
			'authMethod' => 'password',
			'showSubscribedOnly' => null,
			'smimeCertificateId' => null,
			'aliases' => [],
		];
		$importSource = $this->createMock(IImportSource::class);
		$importSource->method('getFileContents')
			->willReturnMap([
				['mail/accounts/index.json', json_encode([101 => 'mail/accounts/101.json'])],
				['mail/accounts/101.json', json_encode($accountData)],
			]);
		$newAccount = new MailAccount([]);
		$newAccount->setUserId('user_import');
		$newAccount->setName('Jane Doe');
		$newAccount->setAuthMethod('password');
		$newAccount->setEditorMode('plain');
		$newAccount->setClassificationEnabled(false);
		/** @var AccountService|MockObject $accountService */
		$accountService = $this->serviceMock->getParameter('accountService');
		$accountService->expects(self::once())
			->method('save')
			->with(self::equalTo($newAccount))
			->willReturnArgument(0);

		$this->migrator->importAccounts(
			$user,
			$importSource,
			[],
			$this->output,
		);
	}

}
