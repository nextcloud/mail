<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Unit\Service\Attachment;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Exception\UploadException;
use OCA\Mail\Service\Attachment\AttachmentStorage;
use OCA\Mail\Service\Attachment\UploadedFile;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;
use PHPUnit_Framework_MockObject_MockObject;

class AttachmentStorageTest extends TestCase {
	private $tmpFilePath = '/tmp/nc_mail_attachment_test';

	/** @var IAppData|PHPUnit_Framework_MockObject_MockObject */
	private $appData;

	/** @var AttachmentStorage */
	private $storage;

	protected function setUp(): void {
		parent::setUp();

		file_put_contents($this->tmpFilePath, 'test test');

		$this->appData = $this->createMock(IAppData::class);
		$this->storage = new AttachmentStorage($this->appData);
	}

	public function testSaveWithFolderNotExisting() {
		$folder = $this->createMock(ISimpleFolder::class);
		$file = $this->createMock(ISimpleFile::class);
		$uploadedFile = $this->createMock(UploadedFile::class);

		$this->appData->expects($this->once())
			->method('getFolder')
			->with($this->equalTo('mail_fritz'))
			->willThrowException(new NotFoundException());
		$this->appData->expects($this->once())
			->method('newFolder')
			->with($this->equalTo('mail_fritz'))
			->willReturn($folder);
		$folder->expects($this->once())
			->method('newFile')
			->with(123)
			->willReturn($file);
		$uploadedFile->expects($this->once())
			->method('getTempPath')
			->willReturn($this->tmpFilePath);
		$file->expects($this->once())
			->method('putContent')
			->with($this->equalTo('test test'));

		$this->storage->save('fritz', 123, $uploadedFile);
	}

	public function testSaveWithPermissionProblems() {
		$folder = $this->createMock(ISimpleFolder::class);
		$file = $this->createMock(ISimpleFile::class);
		$uploadedFile = $this->createMock(UploadedFile::class);

		$this->appData->expects($this->once())
			->method('getFolder')
			->with($this->equalTo('mail_fritz'))
			->willThrowException(new NotFoundException());
		$this->appData->expects($this->once())
			->method('newFolder')
			->with($this->equalTo('mail_fritz'))
			->willThrowException(new NotPermittedException());
		$this->expectException(NotPermittedException::class);

		$this->storage->save('fritz', 123, $uploadedFile);
	}

	public function testSaveWithoutTempPath() {
		$folder = $this->createMock(ISimpleFolder::class);
		$file = $this->createMock(ISimpleFile::class);
		$uploadedFile = $this->createMock(UploadedFile::class);

		$this->appData->expects($this->once())
			->method('getFolder')
			->with($this->equalTo('mail_fritz'))
			->willThrowException(new NotFoundException());
		$this->appData->expects($this->once())
			->method('newFolder')
			->with($this->equalTo('mail_fritz'))
			->willReturn($folder);
		$folder->expects($this->once())
			->method('newFile')
			->with(123)
			->willReturn($file);
		$uploadedFile->expects($this->once())
			->method('getTempPath')
			->willReturn(null);
		$this->expectException(UploadException::class);

		$this->storage->save('fritz', 123, $uploadedFile);
	}

	public function testSaveWithFileReadError() {
		$folder = $this->createMock(ISimpleFolder::class);
		$file = $this->createMock(ISimpleFile::class);
		$uploadedFile = $this->createMock(UploadedFile::class);

		$this->appData->expects($this->once())
			->method('getFolder')
			->with($this->equalTo('mail_fritz'))
			->willThrowException(new NotFoundException());
		$this->appData->expects($this->once())
			->method('newFolder')
			->with($this->equalTo('mail_fritz'))
			->willReturn($folder);
		$folder->expects($this->once())
			->method('newFile')
			->with(123)
			->willReturn($file);
		$uploadedFile->expects($this->once())
			->method('getTempPath')
			->willReturn('/doesntexist');
		$this->expectException(UploadException::class);

		$this->storage->save('fritz', 123, $uploadedFile);
	}

	public function testSave() {
		$folder = $this->createMock(ISimpleFolder::class);
		$file = $this->createMock(ISimpleFile::class);
		$uploadedFile = $this->createMock(UploadedFile::class);

		$this->appData->expects($this->once())
			->method('getFolder')
			->with($this->equalTo('mail_fritz'))
			->willReturn($folder);
		$folder->expects($this->once())
			->method('newFile')
			->with(123)
			->willReturn($file);
		$uploadedFile->expects($this->once())
			->method('getTempPath')
			->willReturn($this->tmpFilePath);
		$file->expects($this->once())
			->method('putContent')
			->with($this->equalTo('test test'));

		$this->storage->save('fritz', 123, $uploadedFile);
	}
}
