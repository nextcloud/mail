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
use Horde_Exception;
use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Contracts\IMailTransmission;
use OCA\Mail\Controller\AccountsController;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\Message;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\ManyRecipientsException;
use OCA\Mail\Model\NewMessageData;
use OCA\Mail\Model\RepliedMessageData;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\AliasesService;
use OCA\Mail\Service\AutoConfig\AutoConfig;
use OCA\Mail\Service\SetupService;
use OCA\Mail\Service\GroupsIntegration;
use OCA\Mail\Service\Sync\SyncService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\Security\ICrypto;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class AccountsControllerTest extends TestCase {

	/** @var string */
	private $appName;

	/** @var IRequest|MockObject */
	private $request;

	/** @var AccountService|MockObject */
	private $accountService;

	/** @var GroupsIntegration|MockObject */
	private $groupsIntegration;

	/** @var string */
	private $userId;

	/** @var AutoConfig|MockObject */
	private $autoConfig;

	/** @var LoggerInterface|MockObject */
	private $logger;

	/** @var IL10N|MockObject */
	private $l10n;

	/** @var ICrypto|MockObject */
	private $crypto;

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

	protected function setUp(): void {
		parent::setUp();

		$this->appName = 'mail';
		$this->request = $this->createMock(IRequest::class);
		$this->accountService = $this->createMock(AccountService::class);
		$this->groupsIntegration = $this->createMock(GroupsIntegration::class);
		$this->groupsIntegration->expects(self::any())
			->method('expand')
			->will($this->returnArgument(0));
		$this->userId = 'manfred';
		$this->autoConfig = $this->createMock(AutoConfig::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->crypto = $this->createMock(ICrypto::class);
		$this->aliasesService = $this->createMock(AliasesService::class);
		$this->transmission = $this->createMock(IMailTransmission::class);
		$this->setupService = $this->createMock(SetupService::class);
		$this->mailManager = $this->createMock(IMailManager::class);
		$this->syncService = $this->createMock(SyncService::class);

		$this->controller = new AccountsController(
			$this->appName,
			$this->request,
			$this->accountService,
			$this->groupsIntegration,
			$this->userId,
			$this->logger,
			$this->l10n,
			$this->aliasesService,
			$this->transmission,
			$this->setupService,
			$this->mailManager,
			$this->syncService
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

	public function testCreateAutoDetectSuccess(): void {
		$email = 'john@example.com';
		$password = '123456';
		$accountName = 'John Doe';
		$account = $this->createMock(Account::class);
		$this->setupService->expects(self::once())
			->method('createNewAutoconfiguredAccount')
			->with($accountName, $email, $password)
			->willReturn($account);

		$response = $this->controller->create($accountName, $email, $password, null, null, null, null, null, null, null, null,
			null, null, true);

		$expectedResponse = \OCA\Mail\Http\JsonResponse::success($account, Http::STATUS_CREATED);

		self::assertEquals($expectedResponse, $response);
	}

	public function testCreateAutoDetectFailure(): void {
		$email = 'john@example.com';
		$password = '123456';
		$accountName = 'John Doe';
		$this->setupService->expects(self::once())
			->method('createNewAutoconfiguredAccount')
			->with($accountName, $email, $password)
			->willThrowException(new ClientException());
		$this->expectException(ClientException::class);

		$this->controller->create($accountName, $email, $password, null, null, null, null, null, null, null, null,
			null, null, true);
	}

	public function testUpdateAutoDetectSuccess(): void {
		$email = 'john@example.com';
		$password = '123456';
		$accountName = 'John Doe';
		$account = $this->createMock(Account::class);
		$this->setupService->expects(self::once())
			->method('createNewAutoconfiguredAccount')
			->with($accountName, $email, $password)
			->willReturn($account);

		$response = $this->controller->create($accountName, $email, $password, null, null, null, null, null, null, null, null,
			null, null, true);

		$expectedResponse = \OCA\Mail\Http\JsonResponse::success($account, Http::STATUS_CREATED);

		self::assertEquals($expectedResponse, $response);
	}

	public function testUpdateAutoDetectFailure(): void {
		$email = 'john@example.com';
		$password = '123456';
		$accountName = 'John Doe';
		$this->setupService->expects(self::once())
			->method('createNewAutoconfiguredAccount')
			->with($accountName, $email, $password)
			->willThrowException(new ClientException());
		$this->expectException(ClientException::class);

		$this->controller->create($accountName, $email, $password, null, null, null, null, null, null, null, null,
			null, null, true);
	}

	public function testCreateManualSuccess(): void {
		$autoDetect = false;
		$email = 'user@domain.tld';
		$password = 'mypassword';
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
			->with($accountName, $email, $imapHost, $imapPort, $imapSslMode, $imapUser, $imapPassword, $smtpHost, $smtpPort, $smtpSslMode, $smtpUser, $smtpPassword, $this->userId)
			->willReturn($account);

		$response = $this->controller->create($accountName, $email, $password, $imapHost, $imapPort, $imapSslMode, $imapUser, $imapPassword, $smtpHost, $smtpPort, $smtpSslMode, $smtpUser, $smtpPassword, $autoDetect);

		$expectedResponse = \OCA\Mail\Http\JsonResponse::success($account, Http::STATUS_CREATED);

		self::assertEquals($expectedResponse, $response);
	}

	public function testCreateManualFailure(): void {
		$autoDetect = false;
		$email = 'user@domain.tld';
		$password = 'mypassword';
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
			->with($accountName, $email, $imapHost, $imapPort, $imapSslMode, $imapUser, $imapPassword, $smtpHost, $smtpPort, $smtpSslMode, $smtpUser, $smtpPassword, $this->userId)
			->willThrowException(new ClientException());
		$this->expectException(ClientException::class);

		$this->controller->create($accountName, $email, $password, $imapHost, $imapPort, $imapSslMode, $imapUser, $imapPassword, $smtpHost, $smtpPort, $smtpSslMode, $smtpUser, $smtpPassword, $autoDetect);
	}

	public function testUpdateManualSuccess(): void {
		$autoDetect = false;
		$id = 135;
		$email = 'user@domain.tld';
		$password = 'mypassword';
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
			->with($accountName, $email, $imapHost, $imapPort, $imapSslMode, $imapUser, $imapPassword, $smtpHost, $smtpPort, $smtpSslMode, $smtpUser, $smtpPassword, $this->userId, $id)
			->willReturn($account);

		$response = $this->controller->update($id, $autoDetect, $accountName, $email, $password, $imapHost, $imapPort, $imapSslMode, $imapUser, $imapPassword, $smtpHost, $smtpPort, $smtpSslMode, $smtpUser, $smtpPassword);

		$expectedResponse = new JSONResponse($account);

		self::assertEquals($expectedResponse, $response);
	}

	public function testUpdateManualFailure(): void {
		$autoDetect = false;
		$id = 135;
		$email = 'user@domain.tld';
		$password = 'mypassword';
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
			->with($accountName, $email, $imapHost, $imapPort, $imapSslMode, $imapUser, $imapPassword, $smtpHost, $smtpPort, $smtpSslMode, $smtpUser, $smtpPassword, $this->userId, $id)
			->willThrowException(new ClientException());
		$this->expectException(ClientException::class);

		$this->controller->update($id, $autoDetect, $accountName, $email, $password, $imapHost, $imapPort, $imapSslMode, $imapUser, $imapPassword, $smtpHost, $smtpPort, $smtpSslMode, $smtpUser, $smtpPassword);
	}

	public function testSendNewMessage(): void {
		$account = $this->createMock(Account::class);
		$this->accountService->expects(self::once())
			->method('find')
			->willReturn($account);
		$messageData = NewMessageData::fromRequest($account, 'to@d.com', '', '', 'sub', 'bod', []);
		$this->transmission->expects(self::once())
			->method('sendMessage')
			->with($messageData, null, null, null);
		$expected = new JSONResponse();

		$resp = $this->controller->send(13, 'sub', 'bod', 'to@d.com', '', '');

		self::assertEquals($expected, $resp);
	}

	public function testSendingError(): void {
		$account = $this->createMock(Account::class);
		$this->accountService->expects(self::once())
			->method('find')
			->willReturn($account);
		$messageData = NewMessageData::fromRequest($account, 'to@d.com', '', '', 'sub', 'bod', []);
		$this->transmission->expects(self::once())
			->method('sendMessage')
			->with($messageData, null, null, null)
			->willThrowException(new Horde_Exception('error'));
		$this->expectException(Horde_Exception::class);

		$this->controller->send(13, 'sub', 'bod', 'to@d.com', '', '');
	}

	public function testSendingManyRecipientsError(): void {
		$this->expectException(ManyRecipientsException::class);

		$recipients = [];
		for ($i = 0; $i <= 10; $i++) {
			$recipients[] = "$i@x.com";
		}
		$recipients = implode(',', $recipients);

		$this->controller->send(13, 'sub', 'bod', $recipients, '', '');
	}

	public function testSendingManyRecipientsCcError(): void {
		$this->expectException(ManyRecipientsException::class);

		$recipients = [];
		for ($i = 0; $i <= 10; $i++) {
			$recipients[] = "$i@x.com";
		}
		$recipients = implode(',', $recipients);

		$this->controller->send(13, 'sub', 'bod', '', $recipients, '');
	}

	public function testSendReply(): void {
		$account = $this->createMock(Account::class);
		$replyMessage = new Message();
		$messageId = 1234;
		$this->accountService->expects(self::once())
			->method('find')
			->willReturn($account);
		$this->mailManager->expects(self::once())
			->method('getMessage')
			->with($this->userId, $messageId)
			->willReturn($replyMessage);
		$messageData = NewMessageData::fromRequest($account, 'to@d.com', '', '', 'sub', 'bod', []);
		$replyData = new RepliedMessageData($account, $replyMessage);
		$this->transmission->expects(self::once())
			->method('sendMessage')
			->with($messageData, $replyData, null, null);
		$expected = new JSONResponse();

		$resp = $this->controller->send(
			13,
			'sub',
			'bod',
			'to@d.com',
			'',
			'',
			true,
			false,
			null,
			$messageId,
			[],
			null);

		self::assertEquals($expected, $resp);
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
