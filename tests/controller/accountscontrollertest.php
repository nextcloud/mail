<?php

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
use OCA\Mail\Controller\AccountsController;
use OCA\Mail\Model\Message;
use OC\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCA\Mail\Service\AliasesService;

class AccountsControllerTest extends \Test\TestCase {

	private $appName;
	private $request;
	private $accountService;
	private $userId;
	private $userFolder;
	private $autoConfig;
	private $logger;
	private $l10n;
	private $crypto;
	private $collector;
	private $controller;
	private $accountId;
	private $account;
	private $unifiedAccount;
	private $aliasesService;

	protected function setUp() {
		parent::setUp();

		$this->appName = 'mail';
		$this->request = $this->getMockBuilder('\OCP\IRequest')
			->disableOriginalConstructor()
			->getMock();
		$this->accountService = $this->getMockBuilder('\OCA\Mail\Service\AccountService')
			->disableOriginalConstructor()
			->getMock();
		$this->userId = 'manfred';
		$this->userFolder = $this->getMockBuilder('\OCP\Files\Folder')
			->disableOriginalConstructor()
			->getMock();
		$this->autoConfig = $this->getMockBuilder('\OCA\Mail\Service\AutoConfig\AutoConfig')
			->disableOriginalConstructor()
			->getMock();
		$this->logger = $this->getMockBuilder('\OCA\Mail\Service\Logger')
			->disableOriginalConstructor()
			->getMock();
		$this->l10n = $this->getMockBuilder('\OCP\IL10N')
			->disableOriginalConstructor()
			->getMock();
		$this->crypto = $this->getMockBuilder('\OCP\Security\ICrypto')
			->disableOriginalConstructor()
			->getMock();
		$this->collector = $this->getMockBuilder('\OCA\Mail\Service\AutoCompletion\AddressCollector')
			->disableOriginalConstructor()
			->getMock();
		$this->aliasesService = $this->getMockBuilder('\OCA\Mail\Service\AliasesService')
			->disableOriginalConstructor()
			->getMock();

		$this->controller = new AccountsController($this->appName, $this->request,
			$this->accountService, $this->userId, $this->userFolder, $this->autoConfig,
			$this->logger, $this->l10n, $this->crypto, $this->collector, $this->aliasesService);

		$this->account = $this->getMockBuilder('\OCA\Mail\Account')
			->disableOriginalConstructor()
			->getMock();
		$this->unifiedAccount = $this->getMockBuilder('\OCA\Mail\Service\UnifiedAccount')
			->disableOriginalConstructor()
			->getMock();
		$this->accountId = 123;
	}

	public function testIndex() {
		$this->account->expects($this->once())
			->method('getConfiguration')
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
			->will($this->returnValue('aliases'));

		$response = $this->controller->index();

		$expectedResponse = new JSONResponse([
			[
				'accountId' => 123,
				'aliases' => 'aliases'
			]
		]);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testShow() {
		$this->accountService->expects($this->once())
			->method('find')
			->with($this->equalTo($this->userId), $this->equalTo($this->accountId))
			->will($this->returnValue($this->account));
		$this->account->expects($this->once())
			->method('getConfiguration')
			->will($this->returnValue('conf'));

		$response = $this->controller->show($this->accountId);

		$expectedResponse = new JSONResponse('conf');
		$this->assertEquals($expectedResponse, $response);
	}

	public function testShowDoesNotExist() {
		$this->accountService->expects($this->once())
			->method('find')
			->with($this->equalTo($this->userId), $this->equalTo($this->accountId))
			->will($this->returnValue($this->account));
		$this->account->expects($this->once())
			->method('getConfiguration')
			->will($this->throwException(new OCP\AppFramework\Db\DoesNotExistException('test123')));

		$response = $this->controller->show($this->accountId);

		$expectedResponse = new JSONResponse([]);
		$expectedResponse->setStatus(404);
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
			->will($this->throwException(new \OCP\AppFramework\Db\DoesNotExistException('test')));

		$response = $this->controller->destroy($this->accountId);

		$expectedResponse = new JSONResponse();
		$this->assertEquals($expectedResponse, $response);
	}

	public function testCreateAutoDetectSuccess() {
		$email = 'john@example.com';
		$password = '123456';
		$accountName = 'John Doe';

		$this->account->expects($this->exactly(2))
			->method('getId')
			->will($this->returnValue(135));
		$this->autoConfig->expects($this->once())
			->method('createAutoDetected')
			->with($this->equalTo($email), $this->equalTo($password),
				$this->equalTo($accountName))
			->will($this->returnValue($this->account));
		$this->accountService->expects($this->once())
			->method('save')
			->with($this->equalTo($this->account));

		$response = $this->controller->create($accountName, $email, $password, null,
			null, null, null, null, null, null, null, null, null, true);

		$expectedResponse = new JSONResponse([
			'data' => [
				'id' => 135,
			],
			], Http::STATUS_CREATED);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testCreateAutoDetectFailure() {
		$email = 'john@example.com';
		$password = '123456';
		$accountName = 'John Doe';

		$this->autoConfig->expects($this->once())
			->method('createAutoDetected')
			->with($this->equalTo($email), $this->equalTo($password),
				$this->equalTo($accountName))
			->will($this->returnValue(null));
		$this->l10n->expects($this->once())
			->method('t')
			->will($this->returnValue('fail'));

		$response = $this->controller->create($accountName, $email, $password, null,
			null, null, null, null, null, null, null, null, null, true);

		$expectedResponse = new JSONResponse([
			'message' => 'fail',
			], Http::STATUS_BAD_REQUEST);
		$this->assertEquals($expectedResponse, $response);
	}

	public function newMessageDataProvider() {
		return [
			[false, false, false],
			[true, false, false],
			[false, true, false],
			[true, true, true],
		];
	}

	/**
	 * @dataProvider newMessageDataProvider
	 */
	public function testSend($isUnifiedInbox, $isReply, $addressCollectorError) {
		$account = $isUnifiedInbox ? $this->unifiedAccount : $this->account;
		$folderId = base64_encode('My folder');
		$subject = 'Hello';
		$body = 'Hi!';
		$from = 'test@example.com';
		$to = 'user1@example.com';
		$cc = '"user2" <user2@example.com>, user3@example.com';
		$bcc = 'user4@example.com';
		$draftUID = 45;
		$messageId = $isReply ? 123 : null;
		$attachmentName = 'kitten.png';
		$attachments = [
			[
				'fileName' => $attachmentName
			],
		];
		$aliasId = null;

		$this->accountService->expects($this->once())
			->method('find')
			->with($this->userId, $this->accountId)
			->will($this->returnValue($account));
		if ($isUnifiedInbox) {
			$this->unifiedAccount->expects($this->once())
				->method('resolve')
				->with($messageId)
				->will($this->returnValue([$this->account, $folderId, $messageId]));
		}

		if ($isReply) {
			$message = $this->getMockBuilder('OCA\Mail\Model\ReplyMessage')
				->disableOriginalConstructor()
				->getMock();
			$this->account->expects($this->once())
				->method('newReplyMessage')
				->will($this->returnValue($message));
			$mailbox = $this->getMockBuilder('OCA\Mail\Service\IMailBox')
				->disableOriginalConstructor()
				->getMock();
			$this->account->expects($this->once())
				->method('getMailbox')
				->with(base64_decode($folderId))
				->will($this->returnValue($mailbox));
			$reply = new Message();
			$mailbox->expects($this->once())
				->method('getMessage')
				->with($messageId)
				->will($this->returnValue($reply));
			$message->expects($this->once())
				->method('setRepliedMessage')
				->with($reply);
		} else {
			$message = $this->getMockBuilder('OCA\Mail\Model\Message')
				->disableOriginalConstructor()
				->getMock();
			$this->account->expects($this->once())
				->method('newMessage')
				->will($this->returnValue($message));
		}

		$message->expects($this->once())
			->method('setTo')
			->with(Message::parseAddressList($to));
		$message->expects($this->once())
			->method('setSubject')
			->with($subject);
		$message->expects($this->once())
			->method('setFrom')
			->with($from);
		$message->expects($this->once())
			->method('setCC')
			->with(Message::parseAddressList($cc));
		$message->expects($this->once())
			->method('setBcc')
			->with(Message::parseAddressList($bcc));
		$message->expects($this->once())
			->method('setContent')
			->with($body);
		$message->expects($this->once())
			->method('getToList')
			->willReturn([]);
		$message->expects($this->once())
			->method('getCCList')
			->willReturn([]);
		$message->expects($this->once())
			->method('getBCCList')
			->willReturn([]);

		$this->account->expects($this->once())
			->method('getEMailAddress')
			->will($this->returnValue($from));
		$this->account->expects($this->once())
			->method('sendMessage')
			->with($message, $draftUID);
		$this->userFolder->expects($this->at(0))
			->method('nodeExists')
			->with($attachmentName)
			->will($this->returnValue(true));
		$file = $this->getMockBuilder('OCP\Files\File')
			->disableOriginalConstructor()
			->getMock();
		$this->userFolder->expects($this->once())
			->method('get')
			->with($attachmentName)
			->will($this->returnValue($file));
		if ($addressCollectorError) {
			$this->collector->expects($this->once())
				->method('addAddresses')
				->will($this->throwException(new \Exception('some error')));
		} else {
			$this->collector->expects($this->once())
				->method('addAddresses');
		}

		$expected = new JSONResponse();
		$actual = $this->controller->send($this->accountId, $folderId, $subject,
			$body, $to, $cc, $bcc, $draftUID, $messageId, $attachments, $aliasId);

		$this->assertEquals($expected, $actual);
	}

	public function draftDataProvider() {
		return [
			[false, false],
			[true, true],
			[true, false],
			[true, true],
		];
	}

	/**
	 * @dataProvider newMessageDataProvider
	 */
	public function testDraft($isUnifiedInbox, $withPreviousDraft) {
		$account = $isUnifiedInbox ? $this->unifiedAccount : $this->account;
		$subject = 'Hello';
		$body = 'Hi!';
		$from = 'test@example.com';
		$to = 'user1@example.com';
		$cc = '"user2" <user2@example.com>, user3@example.com';
		$bcc = 'user4@example.com';
		$messageId = 123;
		$uid = $withPreviousDraft ? 123 : null;
		$newUID = 124;

		$this->accountService->expects($this->once())
			->method('find')
			->with($this->userId, $this->accountId)
			->will($this->returnValue($account));
		if ($isUnifiedInbox) {
			$this->unifiedAccount->expects($this->once())
				->method('resolve')
				->with($messageId)
				->will($this->returnValue([$this->account]));
		}

		$message = $this->getMockBuilder('OCA\Mail\Model\Message')
			->disableOriginalConstructor()
			->getMock();
		$this->account->expects($this->once())
			->method('newMessage')
			->will($this->returnValue($message));
		$message->expects($this->once())
			->method('setTo')
			->with(Message::parseAddressList($to));
		$message->expects($this->once())
			->method('setSubject')
			->with($subject);
		$message->expects($this->once())
			->method('setFrom')
			->with($from);
		$message->expects($this->once())
			->method('setCC')
			->with(Message::parseAddressList($cc));
		$message->expects($this->once())
			->method('setBcc')
			->with(Message::parseAddressList($bcc));
		$message->expects($this->once())
			->method('setContent')
			->with($body);
		$this->account->expects($this->once())
			->method('getEMailAddress')
			->will($this->returnValue($from));
		$this->account->expects($this->once())
			->method('saveDraft')
			->with($message, $uid)
			->will($this->returnValue($newUID));

		$expected = new JSONResponse([
			'uid' => $newUID,
		]);
		$actual = $this->controller->draft($this->accountId, $subject, $body, $to,
			$cc, $bcc, $uid, $messageId);

		$this->assertEquals($expected, $actual);
	}

}
