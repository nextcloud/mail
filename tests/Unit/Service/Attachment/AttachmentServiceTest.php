<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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

namespace OCA\Mail\Tests\Unit\Service\Attachment;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client_Socket;
use OC\Files\Node\File;
use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Db\LocalAttachment;
use OCA\Mail\Db\LocalAttachmentMapper;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\Message;
use OCA\Mail\Exception\UploadException;
use OCA\Mail\IMAP\MessageMapper;
use OCA\Mail\Service\Attachment\AttachmentService;
use OCA\Mail\Service\Attachment\AttachmentStorage;
use OCA\Mail\Service\Attachment\UploadedFile;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\Files\Folder;
use OCP\Files\NotPermittedException;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class AttachmentServiceTest extends TestCase {
	/** @var LocalAttachmentMapper|MockObject */
	private $mapper;

	/** @var AttachmentStorage|MockObject */
	private $storage;

	/** @var AttachmentService */
	private $service;

	/** @var Folder|MockObject */
	private $userFolder;

	/** @var IMailManager|MockObject */
	private $mailManager;

	/** @var MessageMapper|MockObject */
	private $messageMapper;

	/** @var MockObject|LoggerInterface */
	private $logger;

	protected function setUp(): void {
		parent::setUp();

		$this->mapper = $this->createMock(LocalAttachmentMapper::class);
		$this->storage = $this->createMock(AttachmentStorage::class);
		$this->mailManager = $this->createMock(IMailManager::class);
		$this->messageMapper = $this->createMock(MessageMapper::class);
		$this->userFolder = $this->createMock(Folder::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->service = new AttachmentService(
			$this->userFolder,
			$this->mapper,
			$this->storage,
			$this->mailManager,
			$this->messageMapper,
			$this->logger
		);
	}

	public function testAddFileWithUploadException() {
		$userId = 'jan';
		$uploadedFile = $this->createMock(UploadedFile::class);
		$uploadedFile->expects($this->once())
			->method('getFileName')
			->willReturn('cat.jpg');
		$attachment = LocalAttachment::fromParams([
			'userId' => $userId,
			'fileName' => 'cat.jpg',
		]);
		$persistedAttachment = LocalAttachment::fromParams([
			'id' => 123,
			'userId' => $userId,
			'fileName' => 'cat.jpg',
		]);

		$this->mapper->expects($this->once())
			->method('insert')
			->with($this->equalTo($attachment))
			->willReturn($persistedAttachment);
		$this->storage->expects($this->once())
			->method('save')
			->with($this->equalTo($userId), $this->equalTo(123), $this->equalTo($uploadedFile))
			->willThrowException(new UploadException());
		$this->mapper->expects($this->once())
			->method('delete')
			->with($this->equalTo($persistedAttachment));
		$this->expectException(UploadException::class);

		$this->service->addFile($userId, $uploadedFile);
	}

	public function testAddFile() {
		$userId = 'jan';
		$uploadedFile = $this->createMock(UploadedFile::class);
		$uploadedFile->expects($this->once())
			->method('getFileName')
			->willReturn('cat.jpg');
		$attachment = LocalAttachment::fromParams([
			'userId' => $userId,
			'fileName' => 'cat.jpg'
		]);
		$persistedAttachment = LocalAttachment::fromParams([
			'id' => 123,
			'userId' => $userId,
			'fileName' => 'cat.jpg',
		]);

		$this->mapper->expects($this->once())
			->method('insert')
			->with($this->equalTo($attachment))
			->willReturn($persistedAttachment);
		$this->storage->expects($this->once())
			->method('save')
			->with($this->equalTo($userId), $this->equalTo(123), $this->equalTo($uploadedFile));

		$this->service->addFile($userId, $uploadedFile);
	}

	public function testAddFileFromStringWithUploadException() {
		$userId = 'jan';
		$attachment = LocalAttachment::fromParams([
			'userId' => $userId,
			'fileName' => 'cat.jpg',
			'mimeType' => 'image/jpg',
		]);
		$persistedAttachment = LocalAttachment::fromParams([
			'id' => 123,
			'userId' => $userId,
			'mimeType' => 'image/jpg',
			'fileName' => 'cat.jpg',
		]);

		$this->mapper->expects($this->once())
			->method('insert')
			->with($this->equalTo($attachment))
			->willReturn($persistedAttachment);
		$this->storage->expects($this->once())
			->method('saveContent')
			->with($this->equalTo($userId), $this->equalTo(123), $this->equalTo('sjdhfkjsdhfkjsdhfkjdshfjhdskfjhds'))
			->willThrowException(new NotPermittedException());
		$this->mapper->expects($this->once())
			->method('delete')
			->with($this->equalTo($persistedAttachment));
		$this->expectException(UploadException::class);

		$this->service->addFileFromString($userId, 'cat.jpg', 'image/jpg', 'sjdhfkjsdhfkjsdhfkjdshfjhdskfjhds');
	}

	public function testAddFileFromString() {
		$userId = 'jan';
		$attachment = LocalAttachment::fromParams([
			'userId' => $userId,
			'fileName' => 'cat.jpg',
			'mimeType' => 'image/jpg',
		]);
		$persistedAttachment = LocalAttachment::fromParams([
			'id' => 123,
			'userId' => $userId,
			'fileName' => 'cat.jpg',
			'mimeType' => 'image/jpg',
		]);

		$this->mapper->expects($this->once())
			->method('insert')
			->with($this->equalTo($attachment))
			->willReturn($persistedAttachment);
		$this->storage->expects($this->once())
			->method('saveContent')
			->with($this->equalTo($userId), $this->equalTo(123), $this->equalTo('sjdhfkjsdhfkjsdhfkjdshfjhdskfjhds'));

		$this->service->addFileFromString($userId, 'cat.jpg', 'image/jpg', 'sjdhfkjsdhfkjsdhfkjdshfjhdskfjhds');
	}

	public function testDeleteAttachment(): void {
		$userId = 'linus';

		$this->mapper->expects(self::once())
			->method('find')
			->with($userId, '1')
			->willReturn(new LocalAttachment());
		$this->storage->expects(self::once())
			->method('delete')
			->with($userId, 1);

		$this->service->deleteAttachment($userId, 1);
	}

	public function testDeleteAttachmentNotFound(): void {
		$userId = 'linus';

		$this->mapper->expects(self::once())
			->method('find')
			->with($userId, '1')
			->willThrowException(new DoesNotExistException(''));
		$this->storage->expects(self::once())
			->method('delete')
			->with($userId, 1);

		$this->service->deleteAttachment($userId, 1);
	}

	public function testDeleteLocalMessageAttachment() : void {
		$userId = 'linus';
		$attachment = new LocalAttachment();
		$attachment->setId(22);
		$attachments = [$attachment];

		$this->mapper->expects(self::once())
			->method('findByLocalMessageId')
			->with($userId, '10')
			->willReturn($attachments);
		$this->mapper->expects(self::once())
			->method('deleteForLocalMessage')
			->with($userId, '10');
		$this->storage->expects(self::once())
			->method('delete')
			->with($userId, $attachment->getId());

		$this->service->deleteLocalMessageAttachments($userId, 10);
	}

	public function testSaveLocalMessageAttachment(): void {
		$userId = 'linus';
		$attachmentIds = [1,2,3];
		$messageId = 100;

		$this->mapper->expects(self::once())
			->method('saveLocalMessageAttachments')
			->with($userId, $messageId, $attachmentIds);
		$this->mapper->expects(self::once())
			->method('findByLocalMessageId')
			->with($userId, $messageId)
			->willReturn([$this->createMock(LocalAttachment::class)]);

		$this->service->saveLocalMessageAttachments($userId, $messageId, $attachmentIds);
	}

	public function testSaveLocalMessageAttachmentNoAttachmentIds(): void {
		$userId = 'linus';
		$attachmentIds = [];
		$messageId = 100;

		$this->mapper->expects(self::never())
			->method('saveLocalMessageAttachments');
		$this->mapper->expects(self::never())
			->method('findByLocalMessageId');

		$this->service->saveLocalMessageAttachments($userId, $messageId, $attachmentIds);
	}

	public function testhandleLocalMessageAttachment(): void {
		$account = $this->createMock(Account::class);
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$attachments = [
			[
				'type' => 'local',
				'id' => 1
			]
		];
		$result = $this->service->handleAttachments($account, $attachments, $client);
		$this->assertEquals([1], $result);
	}

	public function testHandleAttachmentsForwardedMessageAttachment(): void {
		$userId = 'linus';
		$attachment = LocalAttachment::fromParams([
			'userId' => $userId,
			'fileName' => 'cat.jpg',
			'mimeType' => 'text/plain',
		]);
		$persistedAttachment = LocalAttachment::fromParams([
			'id' => 123,
			'userId' => $userId,
			'fileName' => 'cat.jpg',
			'mimeType' => 'text/plain',
		]);
		$account = $this->createConfiguredMock(Account::class, [
			'getUserId' => $userId
		]);
		$message = new Message();
		$message->setUid(123);
		$message->setMailboxId(1);
		$mailbox = new Mailbox();
		$mailbox->setName('INBOX');
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$attachments = [
			'type' => 'message',
			'id' => 123,
			'fileName' => 'cat.jpg',
			'mimeType' => 'text/plain',
		];

		$this->mailManager->expects(self::once())
			->method('getMessage')
			->with($account->getUserId(), 123)
			->willReturn($message);
		$this->mailManager->expects(self::once())
			->method('getMailbox')
			->with($account->getUserId())
			->willReturn($mailbox);
		$this->messageMapper->expects(self::once())
			->method('getFullText')
			->with($client, $mailbox->getName(), $message->getUid())
			->willReturn('sjdhfkjsdhfkjsdhfkjdshfjhdskfjhds');
		$this->mapper->expects($this->once())
			->method('insert')
			->with($this->equalTo($attachment))
			->willReturn($persistedAttachment);
		$this->storage->expects($this->once())
			->method('saveContent')
			->with($this->equalTo($userId), $this->equalTo(123), $this->equalTo('sjdhfkjsdhfkjsdhfkjdshfjhdskfjhds'));
		$this->service->handleAttachments($account, [$attachments], $client);
	}

	public function testHandleAttachmentsForwardedAttachment(): void {
		$userId = 'linus';
		$attachment = LocalAttachment::fromParams([
			'userId' => $userId,
			'fileName' => 'cat.jpg',
			'mimeType' => 'text/plain',
		]);
		$persistedAttachment = LocalAttachment::fromParams([
			'id' => 123,
			'userId' => $userId,
			'fileName' => 'cat.jpg',
			'mimeType' => 'text/plain',
		]);
		$account = $this->createConfiguredMock(Account::class, [
			'getUserId' => $userId
		]);

		$mailbox = new Mailbox();
		$mailbox->setId(9);
		$mailbox->setName('INBOX');
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$attachments = [
			'type' => 'message-attachment',
			'mailboxId' => $mailbox->getId(),
			'uid' => 999,
			'fileName' => 'cat.jpg',
			'mimeType' => 'text/plain',
		];
		$imapAttachment = ['sjdhfkjsdhfkjsdhfkjdshfjhdskfjhds'];

		$this->mailManager->expects(self::once())
			->method('getMailbox')
			->with($account->getUserId(), $mailbox->getId())
			->willReturn($mailbox);
		$this->messageMapper->expects(self::once())
			->method('getRawAttachments')
			->with($client, $mailbox->getName(), 999)
			->willReturn($imapAttachment);
		$this->mapper->expects($this->once())
			->method('insert')
			->with($this->equalTo($attachment))
			->willReturn($persistedAttachment);
		$this->storage->expects($this->once())
			->method('saveContent')
			->with($this->equalTo($userId), $this->equalTo(123), $this->equalTo('sjdhfkjsdhfkjsdhfkjdshfjhdskfjhds'));

		$this->service->handleAttachments($account, [$attachments], $client);
	}

	public function testHandleAttachmentsCloudAttachment(): void {
		$userId = 'linus';
		$file = $this->createConfiguredMock(File::class, [
			'getName' => 'cat.jpg',
			'getMimeType' => 'text/plain',
			'getContent' => 'sjdhfkjsdhfkjsdhfkjdshfjhdskfjhds'
		]);
		$account = $this->createConfiguredMock(Account::class, [
			'getUserId' => $userId
		]);
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$attachment = LocalAttachment::fromParams([
			'userId' => $userId,
			'fileName' => 'cat.jpg',
			'mimeType' => 'text/plain',
		]);
		$persistedAttachment = LocalAttachment::fromParams([
			'id' => 123,
			'userId' => $userId,
			'fileName' => 'cat.jpg',
			'mimeType' => 'text/plain',
		]);
		$attachments = [
			'type' => 'cloud',
			'messageId' => 999,
			'fileName' => 'cat.jpg',
			'mimeType' => 'text/plain',
		];

		$this->userFolder->expects(self::once())
			->method('nodeExists')
			->with('cat.jpg')
			->willReturn(true);
		$this->userFolder->expects(self::once())
			->method('get')
			->with('cat.jpg')
			->willReturn($file);
		$this->mapper->expects($this->once())
			->method('insert')
			->with($this->equalTo($attachment))
			->willReturn($persistedAttachment);
		$this->storage->expects($this->once())
			->method('saveContent')
			->with($this->equalTo($userId), $this->equalTo(123), $this->equalTo('sjdhfkjsdhfkjsdhfkjdshfjhdskfjhds'));

		$this->service->handleAttachments($account, [$attachments], $client);
	}

	public function testUpdateLocalMessageAttachments(): void {
		$userId = 'linus';
		$message = new LocalMessage();
		$message->setId(100);
		$a1 = new LocalAttachment();
		$a1->setId(4);
		$a2 = new LocalAttachment();
		$a2->setId(5);
		$attachmentIds = [4,5];
		$this->mapper->expects(self::once())
			->method('saveLocalMessageAttachments')
			->with($userId, $message->getId(), $attachmentIds);
		$this->mapper->expects(self::once())
			->method('findByLocalMessageId')
			->with($userId, $message->getId())
			->willReturn([$a1, $a2]);
		$this->service->updateLocalMessageAttachments($userId, $message, $attachmentIds);
	}

	public function testUpdateLocalMessageAttachmentsNoAttachments(): void {
		$userId = 'linus';
		$message = new LocalMessage();
		$message->setId(100);
		$attachmentIds = [];
		$attachment = LocalAttachment::fromParams([
			'id' => 5678,
			'userId' => $userId,
			'fileName' => 'cat.jpg',
			'mimeType' => 'text/plain',
		]);
		$this->mapper->expects(self::once())
			->method('findByLocalMessageId')
			->with($userId, $message->getId())
			->willReturn([$attachment]);
		$this->mapper->expects(self::once())
			->method('deleteForLocalMessage')
			->with($userId, $message->getId());
		$this->storage->expects(self::once())
			->method('delete')
			->with($userId, 5678);
		$this->service->updateLocalMessageAttachments($userId, $message, $attachmentIds);
	}

	public function testUpdateLocalMessageAttachmentsDiffAttachments(): void {
		$userId = 'linus';
		$message = new LocalMessage();
		$message->setId(100);
		$newAttachmentIds = [3, 4];
		$a1 = LocalAttachment::fromParams([
			'id' => 2,
			'userId' => $userId,
			'fileName' => 'cat.jpg',
			'mimeType' => 'text/plain',
		]);
		$a2 = LocalAttachment::fromParams([
			'id' => 3,
			'userId' => $userId,
			'fileName' => 'dog.jpg',
			'mimeType' => 'text/plain',
		]);
		$message->setAttachments([$a1, $a2]);

		$this->mapper->expects(self::once())
			->method('saveLocalMessageAttachments')
			->with($userId, $message->getId(), [ 1 => 4]);
		$this->mapper->expects(self::once())
			->method('findByIds')
			->with($userId, [2])
			->willReturn([$a1]);
		$this->mapper->expects(self::once())
			->method('delete')
			->with($a1);
		$this->storage->expects(self::once())
			->method('delete')
			->with($userId, 2);
		$this->service->updateLocalMessageAttachments($userId, $message, $newAttachmentIds);
	}
}
