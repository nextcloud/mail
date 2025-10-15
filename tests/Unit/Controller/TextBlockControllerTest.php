<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */


namespace OCA\Mail\Tests\Unit\Controller;

use OCA\Mail\Controller\TextBlockController;
use OCA\Mail\Db\TextBlock;
use OCA\Mail\Http\JsonResponse;
use OCA\Mail\Service\TextBlockService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TextBlockControllerTest extends TestCase {

	/** @var TextBlockService|MockObject */
	private $textBlockService;

	/** @var IRequest|MockObject */
	private $request;

	/** @var string */
	private $userId;


	protected function setUp(): void {
		$this->textBlockService = $this->createMock(TextBlockService::class);
		$this->request = $this->createMock(IRequest::class);
		$this->userId = 'bob';
	}

	public function testGetOwnTextBlocksNoUser(): void {
		$controller = new TextBlockController($this->request, null, $this->textBlockService);

		$response = $controller->index();
		$expectedResponse = JsonResponse::error('User not found', Http::STATUS_UNAUTHORIZED);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testGetOwnTextBlocks(): void {
		$textBlocks = [
			new TextBlock(),
			new TextBlock()
		];

		$this->textBlockService->expects($this->once())
			->method('findAll')
			->with($this->userId)
			->willReturn($textBlocks);

		$controller = new TextBlockController($this->request, $this->userId, $this->textBlockService);

		$response = $controller->index();
		$expectedResponse = JsonResponse::success($textBlocks);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testCreateTextBlockNoUser(): void {
		$controller = new TextBlockController($this->request, null, $this->textBlockService);

		$response = $controller->create('New Text Block', 'New Content');
		$expectedResponse = JsonResponse::error('User not found', Http::STATUS_UNAUTHORIZED);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testCreateTextBlock(): void {
		$newTextBlock = new TextBlock();

		$this->textBlockService->expects($this->once())
			->method('create')
			->with($this->userId, 'New Text Block', 'New Content')
			->willReturn($newTextBlock);

		$controller = new TextBlockController($this->request, $this->userId, $this->textBlockService);

		$response = $controller->create('New Text Block', 'New Content');
		$expectedResponse = JsonResponse::success($newTextBlock, Http::STATUS_CREATED);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testUpdateNoUser(): void {
		$controller = new TextBlockController($this->request, null, $this->textBlockService);

		$response = $controller->update(1, 'Updated Text Block', 'Updated Content');
		$expectedResponse = JsonResponse::error('User not found', Http::STATUS_UNAUTHORIZED);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testUpdateTextBlockNotFound(): void {
		$this->textBlockService->expects($this->once())
			->method('find')
			->with(1, $this->userId)
			->willReturn(null);

		$controller = new TextBlockController($this->request, $this->userId, $this->textBlockService);

		$response = $controller->update(1, 'Updated Text Block', 'Updated Content');
		$expectedResponse = JsonResponse::error('Text block not found', Http::STATUS_NOT_FOUND);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testUpdateTextBlock(): void {
		$textBlock = new TextBlock();
		$updatedTextBlock = new TextBlock();

		$this->textBlockService->expects($this->once())
			->method('find')
			->with(1, $this->userId)
			->willReturn($textBlock);

		$this->textBlockService->expects($this->once())
			->method('update')
			->with($textBlock, $this->userId, 'Updated Text Block', 'Updated Content')
			->willReturn($updatedTextBlock);

		$controller = new TextBlockController($this->request, $this->userId, $this->textBlockService);

		$response = $controller->update(1, 'Updated Text Block', 'Updated Content');
		$expectedResponse = JsonResponse::success($updatedTextBlock, Http::STATUS_OK);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testDeleteNoUser(): void {
		$controller = new TextBlockController($this->request, null, $this->textBlockService);

		$response = $controller->destroy(1);
		$expectedResponse = JsonResponse::error('User not found', Http::STATUS_UNAUTHORIZED);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testDeleteTextBlockNotFound(): void {
		$this->textBlockService->expects($this->once())
			->method('delete')
			->with(1, $this->userId)
			->willThrowException(new DoesNotExistException('Sharee does not exist'));

		$controller = new TextBlockController($this->request, $this->userId, $this->textBlockService);

		$response = $controller->destroy(1);
		$expectedResponse = JsonResponse::fail('Text block not found', Http::STATUS_NOT_FOUND);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testDeleteTextBlock(): void {
		$textBlock = new TextBlock();

		$this->textBlockService->expects($this->once())
			->method('delete')
			->with(1, $this->userId);


		$controller = new TextBlockController($this->request, $this->userId, $this->textBlockService);

		$response = $controller->destroy(1);
		$expectedResponse = JsonResponse::success(null, Http::STATUS_OK);
		$this->assertEquals($expectedResponse, $response);
	}

}
