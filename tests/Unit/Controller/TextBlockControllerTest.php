<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */


namespace OCA\Mail\Tests\Unit\Controller;

use OCA\Mail\Controller\TextBlockController;
use OCA\Mail\Db\TextBlock;
use OCA\Mail\Db\TextBlockShare;
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
		
		$response = $controller->getOwnTextBlocks();
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

		$response = $controller->getOwnTextBlocks();
		$expectedResponse = JsonResponse::success($textBlocks);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testGetSharedTextBlocksNoUser(): void {
		$controller = new TextBlockController($this->request, null, $this->textBlockService);

		$response = $controller->getSharedTextBlocks();
		$expectedResponse = JsonResponse::error('User not found', Http::STATUS_UNAUTHORIZED);
		$this->assertEquals($expectedResponse, $response);
	}

	private function testGetSharedTextBlocksTextBlockNotFound(): void {
		$this->textBlockService->expects($this->once())
			->method('findAllSharedWithMe')
			->with($this->userId)
			->willThrowException(new DoesNotExistException('Sharee does not exist'));

		$controller = new TextBlockController($this->request, $this->userId, $this->textBlockService);

		$response = $controller->getSharedTextBlocks();
		$expectedResponse = JsonResponse::error('sharee not found', Http::STATUS_UNAUTHORIZED);
		$this->assertEquals($expectedResponse, $response);
	}
	
	public function testGetSharedTextBlocks(): void {
		$sharedTextBlocks = [
			new TextBlock()
		];

		$this->textBlockService->expects($this->once())
			->method('findAllSharedWithMe')
			->with($this->userId)
			->willReturn($sharedTextBlocks);

		$controller = new TextBlockController($this->request, $this->userId, $this->textBlockService);

		$response = $controller->getSharedTextBlocks();
		$expectedResponse = JsonResponse::success($sharedTextBlocks);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testCreateTextBlockNoUser(): void {
		$controller = new TextBlockController($this->request, null, $this->textBlockService);

		$response = $controller->create('New TextBlock', 'New Content');
		$expectedResponse = JsonResponse::error('User not found', Http::STATUS_UNAUTHORIZED);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testCreateTextBlock(): void {
		$newTextBlock = new TextBlock();

		$this->textBlockService->expects($this->once())
			->method('create')
			->with($this->userId, 'New TextBlock', 'New Content')
			->willReturn($newTextBlock);

		$controller = new TextBlockController($this->request, $this->userId, $this->textBlockService);

		$response = $controller->create('New TextBlock', 'New Content');
		$expectedResponse = JsonResponse::success($newTextBlock, Http::STATUS_CREATED);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testUpdateNoUser(): void {
		$controller = new TextBlockController($this->request, null, $this->textBlockService);

		$response = $controller->update(1, 'Updated TextBlock', 'Updated Content');
		$expectedResponse = JsonResponse::error('User not found', Http::STATUS_UNAUTHORIZED);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testUpdateTextBlockNotFound(): void {
		$this->textBlockService->expects($this->once())
			->method('find')
			->with(1, $this->userId)
			->willReturn(null);

		$controller = new TextBlockController($this->request, $this->userId, $this->textBlockService);

		$response = $controller->update(1, 'Updated TextBlock', 'Updated Content');
		$expectedResponse = JsonResponse::error('TextBlock not found', Http::STATUS_NOT_FOUND);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testUpdateTextBlock(): void {
		$textBlock = new TextBlock();

		$this->textBlockService->expects($this->once())
			->method('find')
			->with(1, $this->userId)
			->willReturn($textBlock);

		$this->textBlockService->expects($this->once())
			->method('update')
			->with(1, $this->userId, 'Updated TextBlock', 'Updated Content');

		$controller = new TextBlockController($this->request, $this->userId, $this->textBlockService);

		$response = $controller->update(1, 'Updated TextBlock', 'Updated Content');
		$expectedResponse = JsonResponse::success($textBlock, Http::STATUS_OK);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testDeleteNoUser(): void {
		$controller = new TextBlockController($this->request, null, $this->textBlockService);

		$response = $controller->delete(1);
		$expectedResponse = JsonResponse::error('User not found', Http::STATUS_UNAUTHORIZED);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testDeleteTextBlockNotFound(): void {
		$this->textBlockService->expects($this->once())
			->method('delete')
			->with(1, $this->userId)
			->willThrowException(new DoesNotExistException('Sharee does not exist'));

		$controller = new TextBlockController($this->request, $this->userId, $this->textBlockService);

		$response = $controller->delete(1);
		$expectedResponse = JsonResponse::fail('TextBlock not found', Http::STATUS_NOT_FOUND);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testDeleteTextBlock(): void {
		$textBlock = new TextBlock();

		$this->textBlockService->expects($this->once())
			->method('delete')
			->with(1, $this->userId);


		$controller = new TextBlockController($this->request, $this->userId, $this->textBlockService);

		$response = $controller->delete(1);
		$expectedResponse = JsonResponse::success(null, Http::STATUS_OK);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testShareNoUser(): void {
		$controller = new TextBlockController($this->request, null, $this->textBlockService);

		$response = $controller->share(1, 'alice', 'user');
		$expectedResponse = JsonResponse::error('User not found', Http::STATUS_UNAUTHORIZED);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testShareTextBlockNotFound(): void {
		$this->textBlockService->expects($this->once())
			->method('find')
			->with(1, $this->userId)
			->willReturn(null);

		$controller = new TextBlockController($this->request, $this->userId, $this->textBlockService);

		$response = $controller->share(1, 'alice', 'user');
		$expectedResponse = JsonResponse::error('TextBlock not found', Http::STATUS_NOT_FOUND);
		$this->assertEquals($expectedResponse, $response);
	}


	public function testShareTextBlockWrongShareType(): void {
		$this->textBlockService->expects($this->once())
			->method('find')
			->with(1, $this->userId)
			->willReturn(new TextBlock());

		$controller = new TextBlockController($this->request, $this->userId, $this->textBlockService);
		$response = $controller->share(1, 'alice', 'country');
		$expectedResponse = JsonResponse::fail('Invalid share type', Http::STATUS_BAD_REQUEST);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testShareTextBlockUser(): void {
		$controller = new TextBlockController($this->request, $this->userId, $this->textBlockService);

		$this->textBlockService->expects($this->once())
			->method('find')
			->with(1, $this->userId)
			->willReturn(new TextBlock());
		$this->textBlockService->expects($this->once())
			->method('share')
			->with(1, 'alice');
		$response = $controller->share(1, 'alice', TextBlockShare::TYPE_USER);
		$expectedResponse = JsonResponse::success();
		$this->assertEquals($expectedResponse, $response);
	}
	public function testShareTextBlockGroup(): void {
	
		$controller = new TextBlockController($this->request, $this->userId, $this->textBlockService);

		$this->textBlockService->expects($this->once())
			->method('find')
			->with(1, $this->userId)
			->willReturn(new TextBlock());
		$this->textBlockService->expects($this->once())
			->method('shareWithGroup')
			->with(1, 'Narnia');
		$response = $controller->share(1, 'Narnia', TextBlockShare::TYPE_GROUP);
		$expectedResponse = JsonResponse::success();
		$this->assertEquals($expectedResponse, $response);
	}

	public function testGetSharesNoUser(): void {
		$controller = new TextBlockController($this->request, null, $this->textBlockService);

		$response = $controller->getShares(1);
		$expectedResponse = JsonResponse::error('User not found', Http::STATUS_UNAUTHORIZED);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testGetSharesTextBlockNotFound(): void {
		$this->textBlockService->expects($this->once())
			->method('find')
			->with(1, $this->userId)
			->willReturn(null);

		$controller = new TextBlockController($this->request, $this->userId, $this->textBlockService);

		$response = $controller->getShares(1);
		$expectedResponse = JsonResponse::error('TextBlock not found', Http::STATUS_NOT_FOUND);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testGetShares(): void {
		$shares = [
			new TextBlockShare(),
			new TextBlockShare()
		];

		$this->textBlockService->expects($this->once())
			->method('find')
			->with(1, $this->userId)
			->willReturn(new TextBlock());

		$this->textBlockService->expects($this->once())
			->method('getShares')
			->with(1)
			->willReturn($shares);

		$controller = new TextBlockController($this->request, $this->userId, $this->textBlockService);

		$response = $controller->getShares(1);
		$expectedResponse = JsonResponse::success($shares);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testDeleteShareNoUser(): void {
		$controller = new TextBlockController($this->request, null, $this->textBlockService);

		$response = $controller->deleteShare(1, 'alice');
		$expectedResponse = JsonResponse::error('User not found', Http::STATUS_UNAUTHORIZED);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testDeleteShareTextBlockNotFound(): void {
		$this->textBlockService->expects($this->once())
			->method('find')
			->with(1, $this->userId)
			->willReturn(null);

		$controller = new TextBlockController($this->request, $this->userId, $this->textBlockService);

		$response = $controller->deleteShare(1, 'alice');
		$expectedResponse = JsonResponse::error('TextBlock not found', Http::STATUS_NOT_FOUND);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testDeleteShare(): void {
		$this->textBlockService->expects($this->once())
			->method('find')
			->with(1, $this->userId)
			->willReturn(new TextBlock());

		$this->textBlockService->expects($this->once())
			->method('unshare')
			->with(1, 'alice');

		$controller = new TextBlockController($this->request, $this->userId, $this->textBlockService);

		$response = $controller->deleteShare(1, 'alice');
		$expectedResponse = JsonResponse::success();
		$this->assertEquals($expectedResponse, $response);
	}

}
