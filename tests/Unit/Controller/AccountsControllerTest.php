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
use Exception;
use Horde_Exception;
use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Contracts\IMailTransmission;
use OCA\Mail\Controller\AccountsController;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Exception\ClientException;
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
use OCP\ILogger;
use OCP\IRequest;
use OCP\Security\ICrypto;
use PHPUnit\Framework\MockObject\MockObject;

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

	/** @var ILogger|MockObject */
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
		$this->groupsIntegration->expects($this->any())
			->method('expand')
			->will($this->returnArgument(0));
		$this->userId = 'manfred';
		$this->autoConfig = $this->createMock(AutoConfig::class);
		$this->logger = $this->createMock(ILogger::class);
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

	public function testIndex() {
		$this->account->expects($this->once())
			->method('jsonSerialize')
			->will($this->returnValue([
				'accountId' => 123,
			]));
		$this->accountService->expects($this->once())
			->method('findByUserId')
			->with($this->equalTo($this->userId))
			->will($this->returnValue([$this->account]));
		$this->aliasesService->expects($this->any())
			->method('findAll')
			->with($this->equalTo($this->accountId), $this->equalTo($this->userId))
			->will($this->returnValue(['a1', 'a2']));

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
		$this->assertEquals($expectedResponse, $response);
	}

	public function testShow() {
		$this->accountService->expects($this->once())
			->method('find')
			->with($this->equalTo($this->userId), $this->equalTo($this->accountId))
			->will($this->returnValue($this->account));

		$response = $this->controller->show($this->accountId);

		$expectedResponse = new JSONResponse($this->account);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testShowDoesNotExist() {
		$this->accountService->expects($this->once())
			->method('find')
			->with($this->equalTo($this->userId), $this->equalTo($this->accountId))
			->will($this->throwException(new DoesNotExistException('test123')));
		$this->expectException(DoesNotExistException::class);

		$this->controller->show($this->accountId);
	}

	public function testUpdateSignature() {
		$this->accountService->expects($this->once())
			->method('updateSignature')
			->with($this->equalTo($this->accountId), $this->equalTo($this->userId), 'sig');

		$response = $this->controller->updateSignature($this->accountId, 'sig');

		$expectedResponse = new JSONResponse();
		$this->assertEquals($expectedResponse, $response);
	}

	public function testDeleteSignature() {
		$this->accountService->expects($this->once())
			->method('updateSignature')
			->with($this->equalTo($this->accountId), $this->equalTo($this->userId), null);

		$response = $this->controller->updateSignature($this->accountId, null);

		$expectedResponse = new JSONResponse();
		$this->assertEquals($expectedResponse, $response);
	}

	public function testDestroy() {
		$this->accountService->expects($this->once())
			->method('delete')
			->with($this->equalTo($this->userId), $this->equalTo($this->accountId));

		$response = $this->controller->destroy($this->accountId);

		$expectedResponse = new JSONResponse();
		$this->assertEquals($expectedResponse, $response);
	}

	public function testDestroyDoesNotExist() {
		$this->accountService->expects($this->once())
			->method('delete')
			->with($this->equalTo($this->userId), $this->equalTo($this->accountId))
			->will($this->throwException(new DoesNotExistException('test')));
		$this->expectException(DoesNotExistException::class);

		$this->controller->destroy($this->accountId);
	}

	public function testCreateAutoDetectSuccess() {
		$email = 'john@example.com';
		$password = '123456';
		$accountName = 'John Doe';
		$account = $this->createMock(Account::class);
		$this->setupService->expects($this->once())
			->method('createNewAutoconfiguredAccount')
			->with($accountName, $email, $password)
			->willReturn($account);

		$response = $this->controller->create($accountName, $email, $password, null, null, null, null, null, null, null, null,
			null, null, true);

		$expectedResponse = new JSONResponse($account, Http::STATUS_CREATED);

		$this->assertEquals($expectedResponse, $response);
	}

	public function testCreateAutoDetectFailure() {
		$email = 'john@example.com';
		$password = '123456';
		$accountName = 'John Doe';
		$this->setupService->expects($this->once())
			->method('createNewAutoconfiguredAccount')
			->with($accountName, $email, $password)
			->willThrowException(new \Exception());
		$this->expectException(ClientException::class);

		$this->controller->create($accountName, $email, $password, null, null, null, null, null, null, null, null,
			null, null, true);
	}

	public function testUpdateAutoDetectSuccess() {
		$email = 'john@example.com';
		$password = '123456';
		$accountName = 'John Doe';
		$account = $this->createMock(Account::class);
		$this->setupService->expects($this->once())
			->method('createNewAutoconfiguredAccount')
			->with($accountName, $email, $password)
			->willReturn($account);

		$response = $this->controller->create($accountName, $email, $password, null, null, null, null, null, null, null, null,
			null, null, true);

		$expectedResponse = new JSONResponse($account, Http::STATUS_CREATED);

		$this->assertEquals($expectedResponse, $response);
	}

	public function testUpdateAutoDetectFailure() {
		$email = 'john@example.com';
		$password = '123456';
		$accountName = 'John Doe';
		$this->setupService->expects($this->once())
			->method('createNewAutoconfiguredAccount')
			->with($accountName, $email, $password)
			->willThrowException(new Exception());
		$this->expectException(ClientException::class);

		$this->controller->create($accountName, $email, $password, null, null, null, null, null, null, null, null,
			null, null, true);
	}

	public function testCreateManualSuccess() {
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
		$this->setupService->expects($this->once())
			->method('createNewAccount')
			->with($accountName, $email, $imapHost, $imapPort, $imapSslMode, $imapUser, $imapPassword, $smtpHost, $smtpPort, $smtpSslMode, $smtpUser, $smtpPassword, $this->userId)
			->willReturn($account);

		$response = $this->controller->create($accountName, $email, $password, $imapHost, $imapPort, $imapSslMode, $imapUser, $imapPassword, $smtpHost, $smtpPort, $smtpSslMode, $smtpUser, $smtpPassword, $autoDetect);

		$expectedResponse = new JSONResponse($account, Http::STATUS_CREATED);

		$this->assertEquals($expectedResponse, $response);
	}

	public function testCreateManualFailure() {
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
		$this->setupService->expects($this->once())
			->method('createNewAccount')
			->with($accountName, $email, $imapHost, $imapPort, $imapSslMode, $imapUser, $imapPassword, $smtpHost, $smtpPort, $smtpSslMode, $smtpUser, $smtpPassword, $this->userId)
			->willThrowException(new Exception());
		$this->expectException(ClientException::class);

		$this->controller->create($accountName, $email, $password, $imapHost, $imapPort, $imapSslMode, $imapUser, $imapPassword, $smtpHost, $smtpPort, $smtpSslMode, $smtpUser, $smtpPassword, $autoDetect);
	}

	public function testUpdateManualSuccess() {
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
		$this->setupService->expects($this->once())
			->method('createNewAccount')
			->with($accountName, $email, $imapHost, $imapPort, $imapSslMode, $imapUser, $imapPassword, $smtpHost, $smtpPort, $smtpSslMode, $smtpUser, $smtpPassword, $this->userId, $id)
			->willReturn($account);

		$response = $this->controller->update($id, $autoDetect, $accountName, $email, $password, $imapHost, $imapPort, $imapSslMode, $imapUser, $imapPassword, $smtpHost, $smtpPort, $smtpSslMode, $smtpUser, $smtpPassword);

		$expectedResponse = new JSONResponse($account);

		$this->assertEquals($expectedResponse, $response);
	}

	public function testUpdateManualFailure() {
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
		$this->setupService->expects($this->once())
			->method('createNewAccount')
			->with($accountName, $email, $imapHost, $imapPort, $imapSslMode, $imapUser, $imapPassword, $smtpHost, $smtpPort, $smtpSslMode, $smtpUser, $smtpPassword, $this->userId, $id)
			->willThrowException(new Exception());
		$this->expectException(ClientException::class);

		$this->controller->update($id, $autoDetect, $accountName, $email, $password, $imapHost, $imapPort, $imapSslMode, $imapUser, $imapPassword, $smtpHost, $smtpPort, $smtpSslMode, $smtpUser, $smtpPassword);
	}

	public function testSendNewMessage() {
		$account = $this->createMock(Account::class);
		$this->accountService->expects($this->once())
			->method('find')
			->willReturn($account);
		$messageData = NewMessageData::fromRequest($account, 'to@d.com', '', '', 'sub', 'bod', []);
		$this->transmission->expects($this->once())
			->method('sendMessage')
			->with($messageData, null, null, null);
		$expected = new JSONResponse();

		$resp = $this->controller->send(13, 'sub', 'bod', 'to@d.com', '', '');

		$this->assertEquals($expected, $resp);
	}

	public function testSendingError() {
		$account = $this->createMock(Account::class);
		$this->accountService->expects($this->once())
			->method('find')
			->willReturn($account);
		$messageData = NewMessageData::fromRequest($account, 'to@d.com', '', '', 'sub', 'bod', []);
		$this->transmission->expects($this->once())
			->method('sendMessage')
			->with($messageData, null, null, null)
			->willThrowException(new Horde_Exception('error'));
		$this->expectException(Horde_Exception::class);

		$this->controller->send(13, 'sub', 'bod', 'to@d.com', '', '');
	}

	public function testSendReply() {
		$account = $this->createMock(Account::class);
		$folderId = 'INBOX';
		$messageId = 1234;
		$this->accountService->expects($this->once())
			->method('find')
			->willReturn($account);
		$messageData = NewMessageData::fromRequest($account, 'to@d.com', '', '', 'sub', 'bod', []);
		$replyData = new RepliedMessageData($account, $folderId, $messageId);
		$this->transmission->expects($this->once())
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
			null,
			base64_encode($folderId),
			$messageId,
			[],
			null);

		$this->assertEquals($expected, $resp);
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
		$this->accountService->expects($this->once())
			->method('find')
			->with($this->userId, $this->accountId)
			->will($this->returnValue($this->account));
		$this->transmission->expects($this->once())
			->method('saveDraft')
			->willReturn([$account, $mailbox, $newUid]);
		$this->mailManager->expects($this->once())
			->method('getMessageIdForUid')
			->willReturn($newId);

		$actual = $this->controller->draft($this->accountId, $subject, $body, $to, $cc, $bcc, true, $id);

		$expected = new JSONResponse([
			'id' => $newId,
		]);
		$this->assertEquals($expected, $actual);
	}
}
