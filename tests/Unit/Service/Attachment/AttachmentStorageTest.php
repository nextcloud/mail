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
use PHPUnit\Framework\MockObject\MockObject;

class AttachmentStorageTest extends TestCase {
	private $tmpFilePath = '/tmp/nc_mail_attachment_test';

	/** @var IAppData|MockObject */
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
			->with($this->callback($this->streamHasContent('test test')));

		$this->storage->save('fritz', 123, $uploadedFile);
	}

	public function testSaveWithPermissionProblems() {
		$folder = $this->createStub(ISimpleFolder::class);
		$file = $this->createStub(ISimpleFile::class);
		$uploadedFile = $this->createStub(UploadedFile::class);

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
		$file = $this->createStub(ISimpleFile::class);
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
		$file->expects($this->never())
			->method('putContent');
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
			->with($this->callback($this->streamHasContent('test test')));

		$this->storage->save('fritz', 123, $uploadedFile);
	}

	public function testSaveClosesStream(): void {
		$stream = null;
		$file = $this->mockFileForSave();
		$file->expects($this->once())
			->method('putContent')
			->willReturnCallback(static function ($data) use (&$stream): void {
				$stream = $data;
			});

		$this->storage->save('fritz', 123, $this->mockUploadedFile());

		$this->assertNotNull($stream);
		$this->assertFalse(is_resource($stream));
	}

	public function testSaveWithStreamClosedByStorage(): void {
		$file = $this->mockFileForSave();
		$file->expects($this->once())
			->method('putContent')
			->willReturnCallback(static function ($data): void {
				fclose($data);
			});

		$this->storage->save('fritz', 123, $this->mockUploadedFile());
	}

	public function testSaveClosesStreamWhenWriteFails(): void {
		$stream = null;
		$file = $this->mockFileForSave();
		$file->expects($this->once())
			->method('putContent')
			->willReturnCallback(static function ($data) use (&$stream): void {
				$stream = $data;
				throw new NotPermittedException();
			});

		try {
			$this->storage->save('fritz', 123, $this->mockUploadedFile());
			$this->fail('Expected NotPermittedException');
		} catch (NotPermittedException $e) {
			$this->assertNotNull($stream);
			$this->assertFalse(is_resource($stream));
		}
	}

	private function mockFileForSave(): ISimpleFile&MockObject {
		$folder = $this->createMock(ISimpleFolder::class);
		$file = $this->createMock(ISimpleFile::class);
		$this->appData->method('getFolder')
			->with('mail_fritz')
			->willReturn($folder);
		$folder->method('newFile')
			->with(123)
			->willReturn($file);

		return $file;
	}

	private function mockUploadedFile(): UploadedFile&MockObject {
		$uploadedFile = $this->createMock(UploadedFile::class);
		$uploadedFile->method('getTempPath')
			->willReturn($this->tmpFilePath);

		return $uploadedFile;
	}

	private function streamHasContent(string $expected): callable {
		return static function ($stream) use ($expected): bool {
			if (!is_resource($stream)) {
				return false;
			}
			rewind($stream);

			return stream_get_contents($stream) === $expected;
		};
	}
}
