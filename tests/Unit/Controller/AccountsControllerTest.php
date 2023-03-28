<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Mail\Tests\Unit\Controller;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Contracts\IMailTransmission;
use OCA\Mail\Controller\AccountsController;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\AliasesService;
use OCA\Mail\Service\SetupService;
use OCA\Mail\Service\Sync\SyncService;
use OCA\Mail\IMAP\MailboxSync;
use OCP\Security\IRemoteHostValidator;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class AccountsControllerTest extends TestCase {
	/** @var string */
	private $appName;

	/** @var IRequest|MockObject */
	private $request;

	/** @var AccountService|MockObject */
	private $accountService;

	/** @var string */
	private $userId;

	/** @var LoggerInterface|MockObject */
	private $logger;

	/** @var IL10N|MockObject */
	private $l10n;

	/** @var AccountsController */
	private $controller;

	/** @var int */
	private $accountId;

	/** @var Account|MockObject */
	private $account;

	/** @var AliasesService|MockObject */
	private $aliasesService;

	/** @var IMailTransmission|MockObject */
	private $transmission;

	/** @var SetupService|MockObject */
	private $setupService;

	/** @var IMailManager|MockObject */
	private $mailManager;

	/** @var SyncService|MockObject */
	private $syncService;

	/** @var MailboxSync|MockObject */
	private $mailboxSync;

	/** @var IRemoteHostValidator|MockObject */
	private $hostValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->appName = 'mail';
		$this->request = $this->createMock(IRequest::class);
		$this->accountService = $this->createMock(AccountService::class);
		$this->userId = 'manfred';
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->aliasesService = $this->createMock(AliasesService::class);
		$this->transmission = $this->createMock(IMailTransmission::class);
		$this->setupService = $this->createMock(SetupService::class);
		$this->mailManager = $this->createMock(IMailManager::class);
		$this->syncService = $this->createMock(SyncService::class);
		$this->mailboxSync = $this->createMock(mailboxSync::class);
		$this->config = $this->createMock(IConfig::class);
		$this->hostValidator = $this->createMock(IRemoteHostValidator::class);
		$this->hostValidator->method('isValid')->willReturn(true);

		$this->controller = new AccountsController(
			$this->appName,
			$this->request,
			$this->accountService,
			$this->userId,
			$this->logger,
			$this->l10n,
			$this->aliasesService,
			$this->transmission,
			$this->setupService,
			$this->mailManager,
			$this->syncService,
			$this->config,
			$this->hostValidator,
			$this->mailboxSync,
		);
		$this->account = $this->createMock(Account::class);
		$this->accountId = 123;
	}

	public function testIndex(): void {
		$this->account->expects(self::once())
			->method('jsonSerialize')
			->will(self::returnValue([
				'accountId' => 123,
			]));
		$this->accountService->expects(self::once())
			->method('findByUserId')
			->with(self::equalTo($this->userId))
			->will(self::returnValue([$this->account]));
		$this->aliasesService->expects(self::any())
			->method('findAll')
			->with(self::equalTo($this->accountId), self::equalTo($this->userId))
			->will(self::returnValue(['a1', 'a2']));

		$response = $this->controller->index();

		$expectedResponse = new JSONResponse([
			[
				'accountId' => 123,
				'aliases' => [
					'a1',
					'a2',
				],
			]
		]);
		self::assertEquals($expectedResponse, $response);
	}

	public function testShow(): void {
		$this->accountService->expects(self::once())
			->method('find')
			->with(self::equalTo($this->userId), self::equalTo($this->accountId))
			->will(self::returnValue($this->account));

		$response = $this->controller->show($this->accountId);

		$expectedResponse = new JSONResponse($this->account);
		self::assertEquals($expectedResponse, $response);
	}

	public function testShowDoesNotExist(): void {
		$this->accountService->expects(self::once())
			->method('find')
			->with(self::equalTo($this->userId), self::equalTo($this->accountId))
			->will(self::throwException(new DoesNotExistException('test123')));
		$this->expectException(DoesNotExistException::class);

		$this->controller->show($this->accountId);
	}

	public function testUpdateSignature(): void {
		$this->accountService->expects(self::once())
			->method('updateSignature')
			->with(self::equalTo($this->accountId), self::equalTo($this->userId), 'sig');

		$response = $this->controller->updateSignature($this->accountId, 'sig');

		$expectedResponse = new JSONResponse();
		self::assertEquals($expectedResponse, $response);
	}

	public function testDeleteSignature(): void {
		$this->accountService->expects(self::once())
			->method('updateSignature')
			->with(self::equalTo($this->accountId), self::equalTo($this->userId), null);

		$response = $this->controller->updateSignature($this->accountId, null);

		$expectedResponse = new JSONResponse();
		self::assertEquals($expectedResponse, $response);
	}

	public function testDestroy(): void {
		$this->accountService->expects(self::once())
			->method('delete')
			->with(self::equalTo($this->userId), self::equalTo($this->accountId));

		$response = $this->controller->destroy($this->accountId);

		$expectedResponse = new JSONResponse();
		self::assertEquals($expectedResponse, $response);
	}

	public function testDestroyDoesNotExist(): void {
		$this->accountService->expects(self::once())
			->method('delete')
			->with(self::equalTo($this->userId), self::equalTo($this->accountId))
			->will(self::throwException(new DoesNotExistException('test')));
		$this->expectException(DoesNotExistException::class);

		$this->controller->destroy($this->accountId);
	}

	public function testCreateManualSuccess(): void {
		$this->config->expects(self::once())
			->method('getAppValue')
			->willReturn('yes');
		$email = 'user@domain.tld';
		$accountName = 'Mail';
		$imapHost = 'localhost';
		$imapPort = 993;
		$imapSslMode = 'ssl';
		$imapUser = 'user@domain.tld';
		$imapPassword = 'mypassword';
		$smtpHost = 'localhost';
		$smtpPort = 465;
		$smtpSslMode = 'none';
		$smtpUser = 'user@domain.tld';
		$smtpPassword = 'mypassword';
		$account = $this->createMock(Account::class);
		$this->setupService->expects(self::once())
			->method('createNewAccount')
			->with($accountName, $email, $imapHost, $imapPort, $imapSslMode, $imapUser, $imapPassword, $smtpHost, $smtpPort, $smtpSslMode, $smtpUser, $smtpPassword, $this->userId, 'password')
			->willReturn($account);

		$response = $this->controller->create($accountName, $email, $imapHost, $imapPort, $imapSslMode, $imapUser, $imapPassword, $smtpHost, $smtpPort, $smtpSslMode, $smtpUser, $smtpPassword);

		$expectedResponse = \OCA\Mail\Http\JsonResponse::success($account, Http::STATUS_CREATED);

		self::assertEquals($expectedResponse, $response);
	}

	public function testCreateManualNotAllowed(): void {
		$email = 'user@domain.tld';
		$accountName = 'Mail';
		$imapHost = 'localhost';
		$imapPort = 993;
		$imapSslMode = 'ssl';
		$imapUser = 'user@domain.tld';
		$imapPassword = 'mypassword';
		$smtpHost = 'localhost';
		$smtpPort = 465;
		$smtpSslMode = 'none';
		$smtpUser = 'user@domain.tld';
		$smtpPassword = 'mypassword';
		$this->config->expects(self::once())
			->method('getAppValue')
			->willReturn('no');
		$this->logger->expects(self::once())
			->method('info');
		$this->setupService->expects(self::never())
			->method('createNewAccount');

		$expectedResponse = \OCA\Mail\Http\JsonResponse::error('Could not create account');
		$response = $this->controller->create($accountName, $email, $imapHost, $imapPort, $imapSslMode, $imapUser, $imapPassword, $smtpHost, $smtpPort, $smtpSslMode, $smtpUser, $smtpPassword);

		self::assertEquals($expectedResponse, $response);
	}


	public function testCreateManualFailure(): void {
		$email = 'user@domain.tld';
		$accountName = 'Mail';
		$imapHost = 'localhost';
		$imapPort = 993;
		$imapSslMode = 'ssl';
		$imapUser = 'user@domain.tld';
		$imapPassword = 'mypassword';
		$smtpHost = 'localhost';
		$smtpPort = 465;
		$smtpSslMode = 'none';
		$smtpUser = 'user@domain.tld';
		$smtpPassword = 'mypassword';
		$this->setupService->expects(self::once())
			->method('createNewAccount')
			->with($accountName, $email, $imapHost, $imapPort, $imapSslMode, $imapUser, $imapPassword, $smtpHost, $smtpPort, $smtpSslMode, $smtpUser, $smtpPassword, $this->userId, 'password')
			->willThrowException(new ClientException());
		$this->expectException(ClientException::class);

		$this->controller->create($accountName, $email, $imapHost, $imapPort, $imapSslMode, $imapUser, $imapPassword, $smtpHost, $smtpPort, $smtpSslMode, $smtpUser, $smtpPassword, 'password');
	}

	public function testUpdateManualSuccess(): void {
		$id = 135;
		$email = 'user@domain.tld';
		$accountName = 'Mail';
		$imapHost = 'localhost';
		$imapPort = 993;
		$imapSslMode = 'ssl';
		$imapUser = 'user@domain.tld';
		$imapPassword = 'mypassword';
		$smtpHost = 'localhost';
		$smtpPort = 465;
		$smtpSslMode = 'none';
		$smtpUser = 'user@domain.tld';
		$smtpPassword = 'mypassword';
		$account = $this->createMock(Account::class);
		$this->setupService->expects(self::once())
			->method('createNewAccount')
			->with($accountName, $email, $imapHost, $imapPort, $imapSslMode, $imapUser, $imapPassword, $smtpHost, $smtpPort, $smtpSslMode, $smtpUser, $smtpPassword, $this->userId, 'password', $id)
			->willReturn($account);

		$response = $this->controller->update($id, $accountName, $email, $imapHost, $imapPort, $imapSslMode, $imapUser, $imapPassword, $smtpHost, $smtpPort, $smtpSslMode, $smtpUser, $smtpPassword);

		$expectedResponse = \OCA\Mail\Http\JsonResponse::success($account);

		self::assertEquals($expectedResponse, $response);
	}

	public function testUpdateManualFailure(): void {
		$id = 135;
		$email = 'user@domain.tld';
		$accountName = 'Mail';
		$imapHost = 'localhost';
		$imapPort = 993;
		$imapSslMode = 'ssl';
		$imapUser = 'user@domain.tld';
		$imapPassword = 'mypassword';
		$smtpHost = 'localhost';
		$smtpPort = 465;
		$smtpSslMode = 'none';
		$smtpUser = 'user@domain.tld';
		$smtpPassword = 'mypassword';
		$this->setupService->expects(self::once())
			->method('createNewAccount')
			->with($accountName, $email, $imapHost, $imapPort, $imapSslMode, $imapUser, $imapPassword, $smtpHost, $smtpPort, $smtpSslMode, $smtpUser, $smtpPassword, $this->userId, 'password', $id)
			->willThrowException(new ClientException());
		$this->expectException(ClientException::class);

		$this->controller->update($id, $accountName, $email, $imapHost, $imapPort, $imapSslMode, $imapUser, $imapPassword, $smtpHost, $smtpPort, $smtpSslMode, $smtpUser, $smtpPassword);
	}

	public function draftDataProvider(): array {
		return [
			[false, false],
			[true, true],
			[true, false],
			[true, true],
		];
	}

	public function testDraft(): void {
		$subject = 'Hello';
		$body = 'Hi!';
		$to = 'user1@example.com';
		$cc = '"user2" <user2@example.com>, user3@example.com';
		$bcc = 'user4@example.com';
		$id = 123;
		$newId = 1245;
		$newUid = 124;
		$account = $this->createMock(Account::class);
		$mailbox = new Mailbox();
		$this->accountService->expects(self::once())
			->method('find')
			->with($this->userId, $this->accountId)
			->will(self::returnValue($this->account));
		$this->transmission->expects(self::once())
			->method('saveDraft')
			->willReturn([$account, $mailbox, $newUid]);
		$this->mailManager->expects(self::once())
			->method('getMessageIdForUid')
			->willReturn($newId);

		$actual = $this->controller->draft($this->accountId, $subject, $body, $to, $cc, $bcc, true, $id);

		$expected = new JSONResponse([
			'id' => $newId,
		]);
		self::assertEquals($expected, $actual);
	}
}
