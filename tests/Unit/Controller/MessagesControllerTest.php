<?php

declare(strict_types=1);

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
use OCA\Mail\Service\ItineraryService;
use OCA\Mail\Service\MailManager;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\Folder;
use OCP\Files\IMimeTypeDetector;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

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

	/** @var ItineraryService|MockObject */
	private $itineraryService;

	/** @var string */
	private $userId;

	/** @var MockObject|Folder */
	private $userFolder;

	/** @var MockObject|LoggerInterface */
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

	protected function setUp(): void {
		parent::setUp();

		$this->appName = 'mail';
		$this->request = $this->getMockBuilder(IRequest::class)->getMock();
		$this->accountService = $this->createMock(AccountService::class);
		$this->mailManager = $this->createMock(IMailManager::class);
		$this->mailSearch = $this->createMock(IMailSearch::class);
		$this->itineraryService = $this->createMock(ItineraryService::class);
		$this->userId = 'john';
		$this->userFolder = $this->createMock(Folder::class);
		$this->request = $this->createMock(Request::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->mimeTypeDetector = $this->createMock(IMimeTypeDetector::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);

		$timeFactory = $this->createMocK(ITimeFactory::class);
		$timeFactory->expects($this->any())
			->method('getTime')
			->willReturn(10000);
		$this->oldFactory = \OC::$server->offsetGet(ITimeFactory::class);
		\OC::$server->registerService(ITimeFactory::class, function () use ($timeFactory) {
			return $timeFactory;
		});

		$this->controller = new MessagesController(
			$this->appName,
			$this->request,
			$this->accountService,
			$this->mailManager,
			$this->mailSearch,
			$this->itineraryService,
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

	protected function tearDown(): void {
		parent::tearDown();

		\OC::$server->offsetUnset(ITimeFactory::class);
		\OC::$server->offsetSet(ITimeFactory::class, $this->oldFactory);
	}

	public function testGetHtmlBody() {
		$accountId = 17;
		$mailboxId = 13;
		$folderId = 'testfolder';
		$messageId = 4321;
		$this->account
			->method('getId')
			->willReturn($accountId);
		$mailbox = new \OCA\Mail\Db\Mailbox();
		$message = new \OCA\Mail\Db\Message();
		$message->setMailboxId($mailboxId);
		$message->setUid(123);
		$mailbox->setAccountId($accountId);
		$mailbox->setName($folderId);
		$this->mailManager->expects($this->once())
			->method('getMessage')
			->with($this->userId, $messageId)
			->willReturn($message);
		$this->mailManager->expects($this->once())
			->method('getMailbox')
			->with($this->userId, $mailboxId)
			->willReturn($mailbox);
		$this->accountService->expects($this->once())
			->method('find')
			->with($this->equalTo($this->userId), $this->equalTo($accountId))
			->will($this->returnValue($this->account));
		$imapMessage = $this->createMock(IMAPMessage::class);
		$this->mailManager->expects($this->once())
			->method('getImapMessage')
			->with($this->account, $mailbox, 123, true)
			->willReturn($imapMessage);

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

		$actualResponse = $this->controller->getHtmlBody($messageId);

		$this->assertEquals($expectedResponse, $actualResponse);
	}

	public function testDownloadAttachment() {
		$accountId = 17;
		$mailboxId = 987;
		$id = 123;
		$uid = 321;
		$attachmentId = "3";

		// Attachment data
		$contents = 'abcdef';
		$name = 'cat.jpg';
		$type = 'image/jpg';
		$message = new \OCA\Mail\Db\Message();
		$message->setMailboxId($mailboxId);
		$message->setUid($uid);
		$mailbox = new \OCA\Mail\Db\Mailbox();
		$mailbox->setName('INBOX');
		$mailbox->setAccountId($accountId);
		$this->mailManager->expects($this->once())
			->method('getMessage')
			->with($this->userId, $id)
			->willReturn($message);
		$this->mailManager->expects($this->once())
			->method('getMailbox')
			->with($this->userId, $mailboxId)
			->willReturn($mailbox);
		$this->accountService->expects($this->once())
			->method('find')
			->with($this->equalTo($this->userId), $this->equalTo($accountId))
			->will($this->returnValue($this->account));
		$this->account->expects($this->once())
			->method('getMailbox')
			->willReturn($this->mailbox);
		$this->mailbox->expects($this->once())
			->method('getAttachment')
			->with($uid, $attachmentId)
			->will($this->returnValue($this->attachment));
		$this->attachment->expects($this->once())
			->method('getContents')
			->will($this->returnValue($contents));
		$this->attachment->expects($this->any())
			->method('getName')
			->will($this->returnValue($name));
		$this->attachment->expects($this->once())
			->method('getType')
			->will($this->returnValue($type));

		$expected = new AttachmentDownloadResponse($contents, $name, $type);
		$response = $this->controller->downloadAttachment(
			$id,
			$attachmentId
		);

		$this->assertEquals($expected, $response);
	}

	public function testSaveSingleAttachment() {
		$accountId = 17;
		$mailboxId = 987;
		$id = 123;
		$uid = 321;
		$attachmentId = '2.2';
		$targetPath = 'Downloads';
		$message = new \OCA\Mail\Db\Message();
		$message->setMailboxId($mailboxId);
		$message->setUid($uid);
		$mailbox = new \OCA\Mail\Db\Mailbox();
		$mailbox->setName('INBOX');
		$mailbox->setAccountId($accountId);
		$this->mailManager->expects($this->once())
			->method('getMessage')
			->with($this->userId, $id)
			->willReturn($message);
		$this->mailManager->expects($this->once())
			->method('getMailbox')
			->with($this->userId, $mailboxId)
			->willReturn($mailbox);
		$this->accountService->expects($this->once())
			->method('find')
			->with($this->equalTo($this->userId), $this->equalTo($accountId))
			->will($this->returnValue($this->account));
		$this->account->expects($this->once())
			->method('getMailbox')
			->with('INBOX')
			->will($this->returnValue($this->mailbox));
		$this->mailbox->expects($this->once())
			->method('getAttachment')
			->with($uid, $attachmentId)
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
		$response = $this->controller->saveAttachment(
			$id,
			$attachmentId,
			$targetPath
		);

		$this->assertEquals($expected, $response);
	}

	public function testSaveAllAttachments() {
		$accountId = 17;
		$mailboxId = 987;
		$id = 123;
		$uid = 321;
		$attachmentId = '0';
		$targetPath = 'Downloads';
		$message = new \OCA\Mail\Db\Message();
		$message->setMailboxId($mailboxId);
		$message->setUid($uid);
		$mailbox = new \OCA\Mail\Db\Mailbox();
		$mailbox->setName('INBOX');
		$mailbox->setAccountId($accountId);
		$this->mailManager->expects($this->once())
			->method('getMessage')
			->with($this->userId, $id)
			->willReturn($message);
		$this->mailManager->expects($this->once())
			->method('getMailbox')
			->with($this->userId, $mailboxId)
			->willReturn($mailbox);
		$this->accountService->expects($this->once())
			->method('find')
			->with($this->equalTo($this->userId), $this->equalTo($accountId))
			->will($this->returnValue($this->account));
		$this->account->expects($this->once())
			->method('getMailbox')
			->with('INBOX')
			->will($this->returnValue($this->mailbox));
		$this->mailbox->expects($this->once())
			->method('getMessage')
			->with($id)
			->will($this->returnValue($this->message));
		$this->message->attachments = [
			[
				'id' => $attachmentId
			]
		];

		$this->mailbox->expects($this->once())
			->method('getAttachment')
			->with($uid, $attachmentId)
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
		$response = $this->controller->saveAttachment(
			$id,
			$attachmentId,
			$targetPath
		);

		$this->assertEquals($expected, $response);
	}

	public function testSetFlagsUnseen() {
		$accountId = 17;
		$mailboxId = 987;
		$id = 123;
		$flags = [
			'unseen' => false
		];
		$message = new \OCA\Mail\Db\Message();
		$message->setUid(444);
		$message->setMailboxId($mailboxId);
		$mailbox = new \OCA\Mail\Db\Mailbox();
		$mailbox->setName('INBOX');
		$mailbox->setAccountId($accountId);
		$this->mailManager->expects($this->once())
			->method('getMessage')
			->with($this->userId, $id)
			->willReturn($message);
		$this->mailManager->expects($this->once())
			->method('getMailbox')
			->with($this->userId, $mailboxId)
			->willReturn($mailbox);
		$this->accountService->expects($this->once())
			->method('find')
			->with($this->equalTo($this->userId), $this->equalTo($accountId))
			->will($this->returnValue($this->account));
		$this->mailManager->expects($this->once())
			->method('flagMessage')
			->with($this->account, 'INBOX', 444, 'unseen', false);

		$expected = new JSONResponse();
		$response = $this->controller->setFlags(
			$id,
			$flags
		);

		$this->assertEquals($expected, $response);
	}

	public function testSetFlagsFlagged() {
		$accountId = 17;
		$mailboxId = 987;
		$id = 123;
		$flags = [
			'flagged' => true
		];
		$message = new \OCA\Mail\Db\Message();
		$message->setUid(444);
		$message->setMailboxId($mailboxId);
		$mailbox = new \OCA\Mail\Db\Mailbox();
		$mailbox->setName('INBOX');
		$mailbox->setAccountId($accountId);
		$this->mailManager->expects($this->once())
			->method('getMessage')
			->with($this->userId, $id)
			->willReturn($message);
		$this->mailManager->expects($this->once())
			->method('getMailbox')
			->with($this->userId, $mailboxId)
			->willReturn($mailbox);
		$this->accountService->expects($this->once())
			->method('find')
			->with($this->equalTo($this->userId), $this->equalTo($accountId))
			->will($this->returnValue($this->account));
		$this->mailManager->expects($this->once())
			->method('flagMessage')
			->with($this->account, 'INBOX', 444, 'flagged', true);

		$expected = new JSONResponse();
		$response = $this->controller->setFlags(
			$id,
			$flags
		);

		$this->assertEquals($expected, $response);
	}

	public function testDestroy() {
		$accountId = 17;
		$mailboxId = 987;
		$id = 123;
		$message = new \OCA\Mail\Db\Message();
		$message->setUid(444);
		$message->setMailboxId($mailboxId);
		$mailbox = new \OCA\Mail\Db\Mailbox();
		$mailbox->setName('INBOX');
		$mailbox->setAccountId($accountId);
		$this->mailManager->expects($this->once())
			->method('getMessage')
			->with($this->userId, $id)
			->willReturn($message);
		$this->mailManager->expects($this->once())
			->method('getMailbox')
			->with($this->userId, $mailboxId)
			->willReturn($mailbox);
		$this->accountService->expects($this->once())
			->method('find')
			->with($this->equalTo($this->userId), $this->equalTo($accountId))
			->will($this->returnValue($this->account));
		$this->mailManager->expects($this->once())
			->method('deleteMessage')
			->with($this->account, 'INBOX', 444);

		$expected = new JSONResponse();
		$result = $this->controller->destroy($id);

		$this->assertEquals($expected, $result);
	}

	public function testDestroyWithAccountNotFound() {
		$accountId = 17;
		$mailboxId = 987;
		$id = 123;
		$message = new \OCA\Mail\Db\Message();
		$message->setUid(444);
		$message->setMailboxId($mailboxId);
		$mailbox = new \OCA\Mail\Db\Mailbox();
		$mailbox->setName('INBOX');
		$mailbox->setAccountId($accountId);
		$this->mailManager->expects($this->once())
			->method('getMessage')
			->with($this->userId, $id)
			->willReturn($message);
		$this->mailManager->expects($this->once())
			->method('getMailbox')
			->with($this->userId, $mailboxId)
			->willReturn($mailbox);
		$this->accountService->expects($this->once())
			->method('find')
			->with($this->equalTo($this->userId), $this->equalTo($accountId))
			->will($this->throwException(new DoesNotExistException('')));

		$expected = new JSONResponse(null, Http::STATUS_FORBIDDEN);

		$this->assertEquals($expected, $this->controller->destroy($id));
	}

	public function testDestroyWithFolderOrMessageNotFound() {
		$accountId = 17;
		$mailboxId = 987;
		$id = 123;
		$message = new \OCA\Mail\Db\Message();
		$message->setUid(444);
		$message->setMailboxId($mailboxId);
		$mailbox = new \OCA\Mail\Db\Mailbox();
		$mailbox->setName('INBOX');
		$mailbox->setAccountId($accountId);
		$this->mailManager->expects($this->once())
			->method('getMessage')
			->with($this->userId, $id)
			->willReturn($message);
		$this->mailManager->expects($this->once())
			->method('getMailbox')
			->with($this->userId, $mailboxId)
			->willReturn($mailbox);
		$this->accountService->expects($this->once())
			->method('find')
			->with($this->equalTo($this->userId), $this->equalTo($accountId))
			->will($this->returnValue($this->account));
		$this->mailManager->expects($this->once())
			->method('deleteMessage')
			->with($this->account, 'INBOX', 444)
			->willThrowException(new ServiceException());
		$this->expectException(ServiceException::class);

		$this->controller->destroy($id);
	}
}
