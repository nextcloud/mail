<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Unit\Controller;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Contracts\IAttachmentService;
use OCA\Mail\Controller\LocalAttachmentsController;
use OCA\Mail\Db\LocalAttachment;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Service\Attachment\UploadedFile;
use OCA\Mail\Service\DelegationService;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;

class LocalAttachmentsControllerTest extends TestCase {
	/** @var IRequest|MockObject */
	private $request;

	/** @var IAttachmentService|MockObject */
	private $service;

	/** @var DelegationService|MockObject */
	private $delegationService;

	/** @var string */
	private $userId;

	/** @var LocalAttachmentsController */
	private $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->service = $this->createMock(IAttachmentService::class);
		$this->delegationService = $this->createMock(DelegationService::class);
		$this->userId = 'jane';

		$this->controller = new LocalAttachmentsController('mail', $this->request, $this->service, $this->delegationService, $this->userId);
	}

	public function testCreateWithoutFile() {
		$this->request->expects($this->once())
			->method('getUploadedFile')
			->with('attachment')
			->willReturn(null);
		$this->expectException(ClientException::class);

		$this->controller->create();
	}

	public function testCreate() {
		$fileData = [
			'name' => 'cat.jpg',
		];
		$this->request->expects($this->once())
			->method('getUploadedFile')
			->with('attachment')
			->willReturn($fileData);
		$attachment = new LocalAttachment();
		$uploadedFile = new UploadedFile($fileData);
		$this->delegationService->expects($this->never())
			->method('resolveAccountUserId');
		$this->service->expects($this->once())
			->method('addFile')
			->with($this->equalTo($this->userId), $this->equalTo($uploadedFile))
			->willReturn($attachment);

		$actual = $this->controller->create();

		$this->assertEquals(new JSONResponse($attachment, 201), $actual);
	}

	public function testCreateForDelegatedAccount() {
		$fileData = [
			'name' => 'cat.jpg',
		];
		$this->request->expects($this->once())
			->method('getUploadedFile')
			->with('attachment')
			->willReturn($fileData);
		$attachment = new LocalAttachment();
		$uploadedFile = new UploadedFile($fileData);
		$this->delegationService->expects($this->once())
			->method('resolveAccountUserId')
			->with(42, $this->userId)
			->willReturn('owner');
		$this->service->expects($this->once())
			->method('addFile')
			->with($this->equalTo('owner'), $this->equalTo($uploadedFile))
			->willReturn($attachment);

		$actual = $this->controller->create(42);

		$this->assertEquals(new JSONResponse($attachment, 201), $actual);
	}
}
