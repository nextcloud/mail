<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Richard Steinmetz <richard@steinmetz.cloud>
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

use Horde_Imap_Client_Socket;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\Service\SMimeService;
use OCP\IL10N;
use OCP\IRequest;
use OCA\Mail\Db\Tag;
use OCA\Mail\Account;
use OCA\Mail\Mailbox;
use OCP\Files\Folder;
use ReflectionObject;
use OCP\IURLGenerator;
use OCA\Mail\Attachment;
use OCP\AppFramework\Http;
use OCA\Mail\Model\Message;
use Psr\Log\LoggerInterface;
use OCA\Mail\Http\HtmlResponse;
use OCA\Mail\Model\IMAPMessage;
use OCP\Files\IMimeTypeDetector;
use OC\AppFramework\Http\Request;
use OCA\Mail\Service\MailManager;
use OCA\Mail\Contracts\IMailSearch;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\ItineraryService;
use OCP\AppFramework\Http\ZipResponse;
use OCA\Mail\Exception\ClientException;
use OCP\AppFramework\Http\JSONResponse;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Contracts\IMailTransmission;
use OCP\AppFramework\Utility\ITimeFactory;
use OCA\Mail\Controller\MessagesController;
use PHPUnit\Framework\MockObject\MockObject;
use OCA\Mail\Contracts\ITrustedSenderService;
use OCA\Mail\Http\AttachmentDownloadResponse;
use ChristophWurst\Nextcloud\Testing\TestCase;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OC\Security\CSP\ContentSecurityPolicyNonceManager;

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

	/** @var MockObject|ContentSecurityPolicyNonceManager */
	private $nonceManager;

	/** @var MockObject|ITrustedSenderService */
	private $trustedSenderService;

	/** @var MockObject|IMailTransmission */
	private $mailTransmission;

	/** @var ITimeFactory */
	private $oldFactory;

	/** @var MockObject|SMimeService */
	private $sMimeService;

	/** @var MockObject|IMAPClientFactory  */
	private $clientFactory;

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
		$this->nonceManager = $this->createMock(ContentSecurityPolicyNonceManager::class);
		$this->trustedSenderService = $this->createMock(ITrustedSenderService::class);
		$this->mailTransmission = $this->createMock(IMailTransmission::class);
		$this->sMimeService = $this->createMock(SMimeService::class);
		$this->clientFactory = $this->createMock(IMAPClientFactory::class);

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
			$this->urlGenerator,
			$this->nonceManager,
			$this->trustedSenderService,
			$this->mailTransmission,
			$this->sMimeService,
			$this->clientFactory,
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

	public function testGetHtmlBody(): void {
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
		$this->mailManager->expects($this->exactly(2))
			->method('getMessage')
			->with($this->userId, $messageId)
			->willReturn($message);
		$this->mailManager->expects($this->exactly(2))
			->method('getMailbox')
			->with($this->userId, $mailboxId)
			->willReturn($mailbox);
		$this->accountService->expects($this->exactly(2))
			->method('find')
			->with($this->equalTo($this->userId), $this->equalTo($accountId))
			->will($this->returnValue($this->account));
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$imapMessage = $this->createMock(IMAPMessage::class);
		$this->mailManager->expects($this->exactly(2))
			->method('getImapMessage')
			->with($client, $this->account, $mailbox, 123, true)
			->willReturn($imapMessage);
		$this->clientFactory->expects($this->exactly(2))
			->method('getClient')
			->with($this->account)
			->willReturn($client);

		$expectedPlainResponse = HtmlResponse::plain('');
		$expectedPlainResponse->cacheFor(3600);

		$nonce = "abc123";
		$relativeScriptUrl = "/script.js";
		$scriptUrl = "next.cloud/script.js";
		$this->nonceManager->expects($this->once())
			->method('getNonce')
			->willReturn($nonce);
		$this->urlGenerator->expects($this->once())
			->method('linkTo')
			->with('mail', 'js/htmlresponse.js')
			->willReturn($relativeScriptUrl);
		$this->urlGenerator->expects($this->once())
			->method('getAbsoluteURL')
			->with($relativeScriptUrl)
			->willReturn($scriptUrl);
		$expectedRichResponse = HtmlResponse::withResizer('', $nonce, $scriptUrl);
		$expectedRichResponse->cacheFor(3600);

		$policy = new ContentSecurityPolicy();
		$policy->allowEvalScript(false);
		$policy->disallowScriptDomain('\'self\'');
		$policy->disallowConnectDomain('\'self\'');
		$policy->disallowFontDomain('\'self\'');
		$policy->disallowMediaDomain('\'self\'');
		$expectedPlainResponse->setContentSecurityPolicy($policy);
		$expectedPlainResponse->cacheFor(60 * 60, false, true);
		$expectedRichResponse->setContentSecurityPolicy($policy);
		$expectedRichResponse->cacheFor(60 * 60, false, true);

		$actualPlainResponse = $this->controller->getHtmlBody($messageId, true);
		$actualRichResponse = $this->controller->getHtmlBody($messageId, false);

		$this->assertEquals($expectedPlainResponse, $actualPlainResponse);
		$this->assertEquals($expectedRichResponse, $actualRichResponse);
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
			->with($uid)
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

	public function testDownloadAttachments() {
		$accountId = 17;
		$mailboxId = 987;
		$id = 123;
		$uid = 321;
		$message = new \OCA\Mail\Db\Message();
		$message->setMailboxId($mailboxId);
		$message->setUid($uid);
		$mailbox = new \OCA\Mail\Db\Mailbox();
		$mailbox->setName('INBOX');
		$mailbox->setAccountId($accountId);
		$attachments = [
			[
				'content' => 'abcdefg',
				'name' => 'cat.png',
				'size' => ''
			]
		];

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

		//
		$this->mailManager->expects($this->once())
			->method('getMailAttachments')
			->with($this->account, $mailbox, $message)
			->willReturn($attachments);
		// build our zip
		$response = $this->controller->downloadAttachments(
			$id
		);

		$this->assertInstanceOf(ZipResponse::class, $response);

		$zip = new ZipResponse($this->request, 'attachments');
		foreach ($attachments as $attachment) {
			$fileName = $attachment['name'];
			$fh = fopen("php://temp", 'r+');
			fputs($fh, $attachment['content']);
			$size = (int)$attachment['size'];
			rewind($fh);
			$zip->addResource($fh, $fileName, $size);
		}

		// Reflection is needed to get private properties
		$refZip = new ReflectionObject($zip);
		$prop = $refZip->getProperty('resources');
		$prop->setAccessible(true);
		$zipValues = $prop->getValue($zip);
		$refResponse = new ReflectionObject($response);
		$prop = $refResponse->getProperty('resources');
		$prop->setAccessible(true);
		$responseValues = $prop->getValue($zip);

		$this->assertTrue(is_resource($zipValues[0]['resource']));
		$this->assertTrue(is_resource($responseValues[0]['resource']));

		// ZipResponse will write fopen id into the array
		// so assert equals needs to have these values unset before comparison
		unset($zipValues[0]['resource']);
		unset($responseValues[0]['resource']);

		$this->assertEquals($zipValues, $responseValues);
	}

	public function testDownloadAttachmentsNoAccountError() {
		$accountId = 17;
		$mailboxId = 987;
		$id = 123;
		$uid = 321;
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
			->willThrowException(new ClientException());


		// test our json error response
		$this->expectException(ClientException::class);
		$response = $this->controller->downloadAttachments(
			$id
		);

		$this->assertInstanceOf(JSONResponse::class, $response);
	}

	public function testDownloadAttachmentsNoMailboxError() {
		$accountId = 17;
		$mailboxId = 987;
		$id = 123;
		$uid = 321;
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
			->willThrowException(new ClientException());

		// test our json error response
		$this->expectException(ClientException::class);
		$response = $this->controller->downloadAttachments(
			$id
		);

		$this->assertInstanceOf(JSONResponse::class, $response);
	}

	public function testDownloadAttachmentsNoMessageError() {
		$accountId = 17;
		$mailboxId = 987;
		$id = 123;
		$uid = 321;
		$message = new \OCA\Mail\Db\Message();
		$message->setMailboxId($mailboxId);
		$message->setUid($uid);
		$mailbox = new \OCA\Mail\Db\Mailbox();
		$mailbox->setName('INBOX');
		$mailbox->setAccountId($accountId);

		$this->mailManager->expects($this->once())
			->method('getMessage')
			->willThrowException(new ServiceException());
		// test our json error response
		$this->expectException(ServiceException::class);
		$response = $this->controller->downloadAttachments(
			$id
		);

		$this->assertInstanceOf(JSONResponse::class, $response);
	}


	public function testSetFlagsUnseen() {
		$accountId = 17;
		$mailboxId = 987;
		$id = 123;
		$flags = [
			'seen' => false
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
			->with($this->account, 'INBOX', 444, 'seen', false);

		$expected = new JSONResponse();
		$response = $this->controller->setFlags(
			$id,
			$flags
		);

		$this->assertEquals($expected, $response);
	}

	public function testSetTagFailing() {
		$accountId = 17;
		$mailboxId = 987;
		$id = 1;
		$message = new \OCA\Mail\Db\Message();
		$message->setUid(444);
		$message->setMailboxId($mailboxId);
		$message->setMessageId('<jhfjkhdsjkfhdsjkhfjkdsh@test.com>');
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
			->willThrowException(new DoesNotExistException(''));
		$this->mailManager->expects($this->never())
			->method('getTagByImapLabel');
		$this->mailManager->expects($this->never())
			->method('tagMessage');

		$this->controller->setTag($id, Tag::LABEL_IMPORTANT);
	}

	public function testSetTagNotFound() {
		$accountId = 17;
		$mailboxId = 987;
		$id = 1;
		$imapLabel = '$label6';
		$message = new \OCA\Mail\Db\Message();
		$message->setUid(444);
		$message->setMailboxId($mailboxId);
		$message->setMessageId('<jhfjkhdsjkfhdsjkhfjkdsh@test.com>');
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
			->method('getTagByImapLabel')
			->with($imapLabel, $this->userId)
			->willThrowException(new ClientException('Computer says no'));
		$this->mailManager->expects($this->never())
			->method('tagMessage');

		$this->controller->setTag($id, $imapLabel);
	}

	public function testSetTag() {
		$accountId = 17;
		$mailboxId = 987;
		$id = 1;
		$tag = new Tag();
		$tag->setImapLabel(Tag::LABEL_IMPORTANT);
		$message = new \OCA\Mail\Db\Message();
		$message->setUid(444);
		$message->setMailboxId($mailboxId);
		$message->setMessageId('<jhfjkhdsjkfhdsjkhfjkdsh@test.com>');
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
			->method('getTagByImapLabel')
			->with($tag->getImapLabel(), $this->userId)
			->willReturn($tag);
		$this->mailManager->expects($this->once())
			->method('tagMessage')
			->with($this->account, $mailbox->getName(), $message, $tag, true);

		$this->controller->setTag($id, $tag->getImapLabel());
	}

	public function testRemoveTagFailing() {
		$accountId = 17;
		$mailboxId = 987;
		$id = 1;
		$message = new \OCA\Mail\Db\Message();
		$message->setUid(444);
		$message->setMailboxId($mailboxId);
		$message->setMessageId('<jhfjkhdsjkfhdsjkhfjkdsh@test.com>');
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
			->willThrowException(new DoesNotExistException(''));
		$this->mailManager->expects($this->never())
			->method('getTagByImapLabel');
		$this->mailManager->expects($this->never())
			->method('tagMessage');

		$this->controller->removeTag($id, Tag::LABEL_IMPORTANT);
	}

	public function testRemoveTagNotFound() {
		$accountId = 17;
		$mailboxId = 987;
		$id = 1;
		$imapLabel = '$label6';
		$message = new \OCA\Mail\Db\Message();
		$message->setUid(444);
		$message->setMailboxId($mailboxId);
		$message->setMessageId('<jhfjkhdsjkfhdsjkhfjkdsh@test.com>');
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
			->method('getTagByImapLabel')
			->with($imapLabel, $this->userId)
			->willThrowException(new ClientException('Computer says no'));
		$this->mailManager->expects($this->never())
			->method('tagMessage');

		$this->controller->removeTag($id, $imapLabel);
	}

	public function testRemoveTag() {
		$accountId = 17;
		$mailboxId = 987;
		$id = 1;
		$tag = new Tag();
		$tag->setImapLabel(Tag::LABEL_IMPORTANT);
		$message = new \OCA\Mail\Db\Message();
		$message->setUid(444);
		$message->setMailboxId($mailboxId);
		$message->setMessageId('<jhfjkhdsjkfhdsjkhfjkdsh@test.com>');
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
			->method('getTagByImapLabel')
			->with($tag->getImapLabel(), $this->userId)
			->willReturn($tag);
		$this->mailManager->expects($this->once())
			->method('tagMessage')
			->with($this->account, $mailbox->getName(), $message, $tag, false);

		$this->controller->removeTag($id, $tag->getImapLabel());
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

		$expected = new JSONResponse([], Http::STATUS_FORBIDDEN);

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

	public function testGetThread(): void {
		$accountId = 17;
		$mailboxId = 987;
		$id = 123;
		$message = new \OCA\Mail\Db\Message();
		$message->setUid(444);
		$message->setMailboxId($mailboxId);
		$message->setThreadRootId('<marlon@slimehunter.com>');
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
			->willReturn($this->account);
		$this->mailManager->expects($this->once())
			->method('getThread')
			->with($this->account, $message->getThreadRootId());

		$this->controller->getThread($id);
	}

	public function testGetThreadNoThreadRootId(): void {
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
			->willReturn($this->account);
		$this->mailManager->expects($this->never())
			->method('getThread');

		$this->controller->getThread($id);
	}

	public function testGetThreadThreadRootIdEmptyString(): void {
		$accountId = 17;
		$mailboxId = 987;
		$id = 123;
		$message = new \OCA\Mail\Db\Message();
		$message->setUid(444);
		$message->setMailboxId($mailboxId);
		$message->setMessageId('<123@cde.com>');
		$message->setThreadRootId('');
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
			->willReturn($this->account);
		$this->mailManager->expects($this->once())
			->method('getThread');

		$this->controller->getThread($id);
	}

	public function testExport() {
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
		$message->setSubject('core/master has new results');
		$mailbox->setAccountId($accountId);
		$mailbox->setName($folderId);
		$this->mailManager->expects($this->exactly(1))
			->method('getMessage')
			->with($this->userId, $messageId)
			->willReturn($message);
		$this->mailManager->expects($this->exactly(1))
			->method('getMailbox')
			->with($this->userId, $mailboxId)
			->willReturn($mailbox);
		$this->accountService->expects($this->exactly(1))
			->method('find')
			->with($this->equalTo($this->userId), $this->equalTo($accountId))
			->will($this->returnValue($this->account));
		$source = file_get_contents(__DIR__ . '/../../data/mail-message-123.txt');
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$this->mailManager->expects($this->exactly(1))
			->method('getSource')
			->with($client, $this->account, $folderId, 123)
			->willReturn($source);
		$this->clientFactory->expects($this->once())
			->method('getClient')
			->willReturn($client);

		$expectedResponse = new AttachmentDownloadResponse(
			$source,
			'core/master has new results.eml',
			'message/rfc822'
		);
		$actualResponse = $this->controller->export($messageId);

		$this->assertEquals($expectedResponse, $actualResponse);
	}
}
