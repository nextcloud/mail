<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Lukas Reschke <lukas@owncloud.com>
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
use OC\AppFramework\Http\Request;
use OCA\Mail\Account;
use OCA\Mail\Attachment;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Contracts\IMailSearch;
use OCA\Mail\Controller\MessagesController;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Http\AttachmentDownloadResponse;
use OCA\Mail\Http\HtmlResponse;
use OCA\Mail\Mailbox;
use OCA\Mail\Model\IMAPMessage;
use OCA\Mail\Model\Message;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\MailManager;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\Folder;
use OCP\Files\IMimeTypeDetector;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IURLGenerator;
use PHPUnit\Framework\MockObject\MockObject;

class MessagesControllerTest extends TestCase {

	/** @var string */
	private $appName;

	/** @var MockObject|IRequest */
	private $request;

	/** @var MockObject|AccountService */
	private $accountService;

	/** @var MockObject|MailManager */
	private $mailManager;

	/** @var MockObject|IMailSearch */
	private $mailSearch;

	/** @var string */
	private $userId;

	/** @var MockObject|Folder */
	private $userFolder;

	/** @var MockObject|ILogger */
	private $logger;

	/** @var MockObject|IL10N */
	private $l10n;

	/** @var MessagesController */
	private $controller;

	/** @var MockObject|Account */
	private $account;

	/** @var MockObject|Mailbox */
	private $mailbox;

	/** @var MockObject|Message */
	private $message;

	/** @var MockObject|Attachment */
	private $attachment;

	/** @var MockObject|IMimeTypeDetector */
	private $mimeTypeDetector;

	/** @var MockObject|IURLGenerator */
	private $urlGenerator;

	/** @var ITimeFactory */
	private $oldFactory;

	protected function setUp() {
		parent::setUp();

		$this->appName = 'mail';
		$this->request = $this->getMockBuilder(IRequest::class)->getMock();
		$this->accountService = $this->createMock(AccountService::class);
		$this->mailManager = $this->createMock(IMailManager::class);
		$this->mailSearch = $this->createMock(IMailSearch::class);
		$this->userId = 'john';
		$this->userFolder = $this->createMock(Folder::class);
		$this->request = $this->createMock(Request::class);
		$this->logger = $this->createMock(ILogger::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->mimeTypeDetector = $this->createMock(IMimeTypeDetector::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);

		$timeFactory = $this->createMocK(ITimeFactory::class);
		$timeFactory->expects($this->any())
			->method('getTime')
			->willReturn(10000);
		$this->oldFactory = \OC::$server->offsetGet(ITimeFactory::class);
		\OC::$server->registerService(ITimeFactory::class, function() use ($timeFactory) {
			return $timeFactory;
		});

		$this->controller = new MessagesController(
			$this->appName,
			$this->request,
			$this->accountService,
			$this->mailManager,
			$this->mailSearch,
			$this->userId,
			$this->userFolder,
			$this->logger,
			$this->l10n,
			$this->mimeTypeDetector,
			$this->urlGenerator
		);

		$this->account = $this->createMock(Account::class);
		$this->mailbox = $this->createMock(Mailbox::class);
		$this->message = $this->createMock(IMAPMessage::class);
		$this->attachment = $this->createMock(Attachment::class);
	}

	protected function tearDown() {
		parent::tearDown();

		\OC::$server->offsetUnset(ITimeFactory::class);
		\OC::$server->offsetSet(ITimeFactory::class, $this->oldFactory);
	}

	public function testIndex() {
		// TODO: write test
		$this->markTestSkipped('todo');
	}

	public function testShow() {
		// TODO: write test
		$this->markTestSkipped('todo');
	}

	public function testShowMessageNotFound() {
		// TODO: write test
		$this->markTestSkipped('todo');
	}

	public function testGetHtmlBody() {
		$accountId = 17;
		$folderId = 'testfolder';
		$messageId = 4321;
		$message = $this->createMock(IMAPMessage::class);

		$this->accountService->expects($this->once())
			->method('find')
			->with($this->equalTo($this->userId), $this->equalTo($accountId))
			->will($this->returnValue($this->account));
		$this->mailManager->expects($this->once())
			->method('getMessage')
			->with($this->account, $folderId, $messageId, true)
			->willReturn($message);

		$expectedResponse = new HtmlResponse('');
		$expectedResponse->cacheFor(3600);
		if (class_exists('\OCP\AppFramework\Http\ContentSecurityPolicy')) {
			$policy = new ContentSecurityPolicy();
			$policy->allowEvalScript(false);
			$policy->disallowScriptDomain('\'self\'');
			$policy->disallowConnectDomain('\'self\'');
			$policy->disallowFontDomain('\'self\'');
			$policy->disallowMediaDomain('\'self\'');
			$expectedResponse->setContentSecurityPolicy($policy);
		}

		$actualResponse = $this->controller->getHtmlBody($accountId,
			base64_encode($folderId), $messageId);

		$this->assertEquals($expectedResponse, $actualResponse);
	}

	public function testDownloadAttachment() {
		$accountId = 17;
		$folderId = base64_encode('my folder');
		$messageId = 123;
		$attachmentId = 3;

		// Attachment data
		$contents = 'abcdef';
		$name = 'cat.jpg';
		$type = 'image/jpg';

		$this->accountService->expects($this->once())
			->method('find')
			->with($this->equalTo($this->userId), $this->equalTo($accountId))
			->will($this->returnValue($this->account));
		$this->account->expects($this->once())
			->method('getMailbox')
			->with(base64_decode($folderId))
			->will($this->returnValue($this->mailbox));
		$this->mailbox->expects($this->once())
			->method('getAttachment')
			->with($messageId, $attachmentId)
			->will($this->returnValue($this->attachment));
		$this->attachment->expects($this->once())
			->method('getContents')
			->will($this->returnValue($contents));
		$this->attachment->expects($this->once())
			->method('getName')
			->will($this->returnValue($name));
		$this->attachment->expects($this->once())
			->method('getType')
			->will($this->returnValue($type));

		$expected = new AttachmentDownloadResponse($contents, $name, $type);
		$response = $this->controller->downloadAttachment($accountId, $folderId,
			$messageId, $attachmentId);

		$this->assertEquals($expected, $response);
	}

	public function testSaveSingleAttachment() {
		$accountId = 17;
		$folderId = base64_encode('my folder');
		$messageId = 123;
		$attachmentId = 3;
		$targetPath = 'Downloads';

		$this->accountService->expects($this->once())
			->method('find')
			->with($this->equalTo($this->userId), $this->equalTo($accountId))
			->will($this->returnValue($this->account));
		$this->account->expects($this->once())
			->method('getMailbox')
			->with(base64_decode($folderId))
			->will($this->returnValue($this->mailbox));
		$this->mailbox->expects($this->once())
			->method('getAttachment')
			->with($messageId, $attachmentId)
			->will($this->returnValue($this->attachment));
		$this->attachment->expects($this->once())
			->method('getName')
			->with()
			->will($this->returnValue('cat.jpg'));
		$this->userFolder->expects($this->once())
			->method('nodeExists')
			->with("Downloads/cat.jpg")
			->will($this->returnValue(false));
		$file = $this->getMockBuilder('\OCP\Files\File')
			->disableOriginalConstructor()
			->getMock();
		$this->userFolder->expects($this->once())
			->method('newFile')
			->with("Downloads/cat.jpg")
			->will($this->returnValue($file));
		$file->expects($this->once())
			->method('putContent')
			->with('abcdefg');
		$this->attachment->expects($this->once())
			->method('getContents')
			->will($this->returnValue('abcdefg'));

		$expected = new JSONResponse();
		$response = $this->controller->saveAttachment($accountId, $folderId,
			$messageId, $attachmentId, $targetPath);

		$this->assertEquals($expected, $response);
	}

	public function testSaveAllAttachments() {
		$accountId = 17;
		$folderId = base64_encode('my folder');
		$messageId = 123;
		$attachmentId = 3;
		$targetPath = 'Downloads';

		$this->accountService->expects($this->once())
			->method('find')
			->with($this->equalTo($this->userId), $this->equalTo($accountId))
			->will($this->returnValue($this->account));
		$this->account->expects($this->once())
			->method('getMailbox')
			->with(base64_decode($folderId))
			->will($this->returnValue($this->mailbox));
		$this->mailbox->expects($this->once())
			->method('getMessage')
			->with($messageId)
			->will($this->returnValue($this->message));
		$this->message->attachments = [
			[
				'id' => $attachmentId
			]
		];

		$this->mailbox->expects($this->once())
			->method('getAttachment')
			->with($messageId, $attachmentId)
			->will($this->returnValue($this->attachment));
		$this->attachment->expects($this->once())
			->method('getName')
			->with()
			->will($this->returnValue('cat.jpg'));
		$this->userFolder->expects($this->once())
			->method('nodeExists')
			->with("Downloads/cat.jpg")
			->will($this->returnValue(false));
		$file = $this->getMockBuilder('\OCP\Files\File')
			->disableOriginalConstructor()
			->getMock();
		$this->userFolder->expects($this->once())
			->method('newFile')
			->with("Downloads/cat.jpg")
			->will($this->returnValue($file));
		$file->expects($this->once())
			->method('putContent')
			->with('abcdefg');
		$this->attachment->expects($this->once())
			->method('getContents')
			->will($this->returnValue('abcdefg'));

		$expected = new JSONResponse();
		$response = $this->controller->saveAttachment($accountId, $folderId,
			$messageId, 0, $targetPath);

		$this->assertEquals($expected, $response);
	}

	public function testSetFlagsUnseen() {
		$accountId = 17;
		$folderId = base64_encode('my folder');
		$messageId = 123;
		$flags = [
			'unseen' => false
		];

		$this->accountService->expects($this->once())
			->method('find')
			->with($this->equalTo($this->userId), $this->equalTo($accountId))
			->will($this->returnValue($this->account));
		$this->account->expects($this->once())
			->method('getMailbox')
			->with(base64_decode($folderId))
			->will($this->returnValue($this->mailbox));
		$this->mailbox->expects($this->once())
			->method('setMessageFlag')
			->with($messageId, '\\seen', true);

		$expected = new JSONResponse();
		$response = $this->controller->setFlags($accountId, $folderId, $messageId,
			$flags);

		$this->assertEquals($expected, $response);
	}

	public function testSetFlagsFlagged() {
		$accountId = 17;
		$folderId = base64_encode('my folder');
		$messageId = 123;
		$flags = [
			'flagged' => true
		];

		$this->accountService->expects($this->once())
			->method('find')
			->with($this->equalTo($this->userId), $this->equalTo($accountId))
			->will($this->returnValue($this->account));
		$this->account->expects($this->once())
			->method('getMailbox')
			->with(base64_decode($folderId))
			->will($this->returnValue($this->mailbox));
		$this->mailbox->expects($this->once())
			->method('setMessageFlag')
			->with($messageId, '\\flagged', true);

		$expected = new JSONResponse();
		$response = $this->controller->setFlags($accountId, $folderId, $messageId,
			$flags);

		$this->assertEquals($expected, $response);
	}

	public function testDestroy() {
		$accountId = 17;
		$folderId = base64_encode('my folder');
		$messageId = 123;

		$this->accountService->expects($this->once())
			->method('find')
			->with($this->equalTo($this->userId), $this->equalTo($accountId))
			->will($this->returnValue($this->account));
		$this->mailManager->expects($this->once())
			->method('deleteMessage')
			->with($this->account, base64_decode($folderId), $messageId);

		$expected = new JSONResponse();
		$result = $this->controller->destroy($accountId, $folderId, $messageId);

		$this->assertEquals($expected, $result);
	}

	public function testDestroyWithAccountNotFound() {
		$accountId = 17;
		$folderId = base64_encode('my folder');
		$messageId = 123;
		$this->accountService->expects($this->once())
			->method('find')
			->with($this->equalTo($this->userId), $this->equalTo($accountId))
			->will($this->throwException(new DoesNotExistException('')));

		$expected = new JSONResponse(null, Http::STATUS_FORBIDDEN);

		$this->assertEquals($expected, $this->controller->destroy($accountId, $folderId, $messageId));
	}

	public function testDestroyWithFolderOrMessageNotFound() {
		$accountId = 17;
		$folderId = base64_encode('my folder');
		$messageId = 123;
		$this->accountService->expects($this->once())
			->method('find')
			->with($this->equalTo($this->userId), $this->equalTo($accountId))
			->will($this->returnValue($this->account));
		$this->mailManager->expects($this->once())
			->method('deleteMessage')
			->with($this->account, base64_decode($folderId), $messageId)
			->willThrowException(new ServiceException());
		$this->expectException(ServiceException::class);

		$this->controller->destroy($accountId, $folderId, $messageId);
	}

}
