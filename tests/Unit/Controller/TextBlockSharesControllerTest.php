<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */


namespace OCA\Mail\Tests\Unit\Controller;

use OCA\Mail\Controller\TextBlockSharesController;
use OCA\Mail\Db\TextBlock;
use OCA\Mail\Db\TextBlockShare;
use OCA\Mail\Http\JsonResponse;
use OCA\Mail\Service\TextBlockService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TextBlockSharesControllerTest extends TestCase {

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

	public function testGetSharedTextBlocksNoUser(): void {
		$controller = new TextBlockSharesController($this->request, null, $this->textBlockService);

		$response = $controller->index();
		$expectedResponse = JsonResponse::error('User not found', Http::STATUS_UNAUTHORIZED);
		$this->assertEquals($expectedResponse, $response);
	}

	private function testGetSharedTextBlocksShareeNotFound(): void {
		$this->textBlockService->expects($this->once())
			->method('findAllSharedWithMe')
			->with($this->userId)
			->willThrowException(new DoesNotExistException('Sharee does not exist'));

		$controller = new TextBlockSharesController($this->request, $this->userId, $this->textBlockService);

		$response = $controller->index();
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

		$controller = new TextBlockSharesController($this->request, $this->userId, $this->textBlockService);

		$response = $controller->index();
		$expectedResponse = JsonResponse::success($sharedTextBlocks);
		$this->assertEquals($expectedResponse, $response);
	}


	public function testShareNoUser(): void {
		$controller = new TextBlockSharesController($this->request, null, $this->textBlockService);

		$response = $controller->create(1, 'alice', 'user');
		$expectedResponse = JsonResponse::error('User not found', Http::STATUS_UNAUTHORIZED);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testShareTextBlockNotFound(): void {
		$this->textBlockService->expects($this->once())
			->method('find')
			->with(1, $this->userId)
			->willThrowException(new DoesNotExistException('not found'));

		$controller = new TextBlockSharesController($this->request, $this->userId, $this->textBlockService);

		$response = $controller->create(1, 'alice', 'user');
		$expectedResponse = JsonResponse::error('Text block not found', Http::STATUS_NOT_FOUND);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testShareTextBlockWrongShareType(): void {
		$this->textBlockService->expects($this->once())
			->method('find')
			->with(1, $this->userId)
			->willReturn(new TextBlock());

		$controller = new TextBlockSharesController($this->request, $this->userId, $this->textBlockService);
		$response = $controller->create(1, 'alice', 'country');
		$expectedResponse = JsonResponse::fail('Invalid share type', Http::STATUS_BAD_REQUEST);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testShareTextBlockUser(): void {
		$controller = new TextBlockSharesController($this->request, $this->userId, $this->textBlockService);

		$this->textBlockService->expects($this->once())
			->method('find')
			->with(1, $this->userId)
			->willReturn(new TextBlock());
		$this->textBlockService->expects($this->once())
			->method('share')
			->with(1, 'alice');
		$response = $controller->create(1, 'alice', TextBlockShare::TYPE_USER);
		$expectedResponse = JsonResponse::success();
		$this->assertEquals($expectedResponse, $response);
	}

	public function testShareTextBlockGroup(): void {

		$controller = new TextBlockSharesController($this->request, $this->userId, $this->textBlockService);

		$this->textBlockService->expects($this->once())
			->method('find')
			->with(1, $this->userId)
			->willReturn(new TextBlock());
		$this->textBlockService->expects($this->once())
			->method('shareWithGroup')
			->with(1, 'Narnia');
		$response = $controller->create(1, 'Narnia', TextBlockShare::TYPE_GROUP);
		$expectedResponse = JsonResponse::success();
		$this->assertEquals($expectedResponse, $response);
	}

	public function testDeleteShareNoUser(): void {
		$controller = new TextBlockSharesController($this->request, null, $this->textBlockService);

		$response = $controller->destroy(1, 'alice');
		$expectedResponse = JsonResponse::error('User not found', Http::STATUS_UNAUTHORIZED);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testDeleteShareTextBlockNotFound(): void {
		$this->textBlockService->expects($this->once())
			->method('find')
			->with(1, $this->userId)
			->willThrowException(new DoesNotExistException('Text block not found'));

		$controller = new TextBlockSharesController($this->request, $this->userId, $this->textBlockService);

		$response = $controller->destroy(1, 'alice');
		$expectedResponse = JsonResponse::error('Text block not found', Http::STATUS_NOT_FOUND);
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

		$controller = new TextBlockSharesController($this->request, $this->userId, $this->textBlockService);

		$response = $controller->destroy(1, 'alice');
		$expectedResponse = JsonResponse::success();
		$this->assertEquals($expectedResponse, $response);
	}
	public function testGetSharesTextBlockNotFound(): void {
		$this->textBlockService->expects($this->once())
			->method('find')
			->with(1, $this->userId)
			->willThrowException(new DoesNotExistException('Text block not found'));

		$controller = new TextBlockSharesController($this->request, $this->userId, $this->textBlockService);

		$response = $controller->getTextBlockShares(1);
		$expectedResponse = JsonResponse::error('Text block not found', Http::STATUS_NOT_FOUND);
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

		$controller = new TextBlockSharesController($this->request, $this->userId, $this->textBlockService);

		$response = $controller->getTextBlockShares(1);
		$expectedResponse = JsonResponse::success($shares);
		$this->assertEquals($expectedResponse, $response);
	}


	public function testGetSharesNoUser(): void {
		$controller = new TextBlockSharesController($this->request, null, $this->textBlockService);

		$response = $controller->getTextBlockShares(1);
		$expectedResponse = JsonResponse::error('User not found', Http::STATUS_UNAUTHORIZED);
		$this->assertEquals($expectedResponse, $response);
	}


}
