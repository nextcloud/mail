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

namespace OCA\Mail\Tests\Service\Attachment;

use OCA\Mail\Db\LocalAttachment;
use OCA\Mail\Db\LocalAttachmentMapper;
use OCA\Mail\Exception\UploadException;
use OCA\Mail\Service\Attachment\AttachmentService;
use OCA\Mail\Service\Attachment\AttachmentStorage;
use OCA\Mail\Service\Attachment\UploadedFile;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;

class AttachmentServiceTest extends PHPUnit_Framework_TestCase {

	/** @var LocalAttachmentMapper|PHPUnit_Framework_MockObject_MockObject */
	private $mapper;

	/** @var AttachmentStorage|PHPUnit_Framework_MockObject_MockObject */
	private $storage;

	/** @var AttachmentService */
	private $service;

	protected function setUp() {
		parent::setUp();

		$this->mapper = $this->createMock(LocalAttachmentMapper::class);
		$this->storage = $this->createMock(AttachmentStorage::class);

		$this->service = new AttachmentService($this->mapper, $this->storage);
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
			->with($this->equalTo($userId), $this->equalTo(123), $this->equalTo($uploadedFile));

		$this->service->addFile($userId, $uploadedFile);
	}

}
