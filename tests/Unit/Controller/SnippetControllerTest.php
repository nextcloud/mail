<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */


namespace OCA\Mail\Tests\Unit\Controller;

use OCA\Mail\Controller\SnippetController;
use OCA\Mail\Db\Snippet;
use OCA\Mail\Db\SnippetShare;
use OCA\Mail\Http\JsonResponse;
use OCA\Mail\Service\SnippetService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SnippetControllerTest extends TestCase {

	/** @var SnippetService|MockObject */
	private $snippetService;

	/** @var IRequest|MockObject */
	private $request;

	/** @var string */
	private $userId;


	protected function setUp(): void {
		$this->snippetService = $this->createMock(SnippetService::class);
		$this->request = $this->createMock(IRequest::class);
		$this->userId = 'bob';
	}

	public function testGetOwnSnippetsNoUser(): void {
		$controller = new SnippetController($this->request, null, $this->snippetService);
		
		$response = $controller->getOwnSnippets();
		$expectedResponse = JsonResponse::error('User not found', Http::STATUS_UNAUTHORIZED);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testGetOwnSnippets(): void {
		$snippets = [
			new Snippet(),
			new Snippet()
		];

		$this->snippetService->expects($this->once())
			->method('findAll')
			->with($this->userId)
			->willReturn($snippets);

		$controller = new SnippetController($this->request, $this->userId, $this->snippetService);

		$response = $controller->getOwnSnippets();
		$expectedResponse = JsonResponse::success($snippets);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testGetSharedSnippetsNoUser(): void {
		$controller = new SnippetController($this->request, null, $this->snippetService);

		$response = $controller->getSharedSnippets();
		$expectedResponse = JsonResponse::error('User not found', Http::STATUS_UNAUTHORIZED);
		$this->assertEquals($expectedResponse, $response);
	}

	private function testGetSharedSnippetsSnippetNotFound(): void {
		$this->snippetService->expects($this->once())
			->method('findAllSharedWithMe')
			->with($this->userId)
			->willThrowException(new DoesNotExistException('Sharee does not exist'));

		$controller = new SnippetController($this->request, $this->userId, $this->snippetService);

		$response = $controller->getSharedSnippets();
		$expectedResponse = JsonResponse::error('sharee not found', Http::STATUS_UNAUTHORIZED);
		$this->assertEquals($expectedResponse, $response);
	}
	
	public function testGetSharedSnippets(): void {
		$sharedSnippets = [
			new Snippet()
		];

		$this->snippetService->expects($this->once())
			->method('findAllSharedWithMe')
			->with($this->userId)
			->willReturn($sharedSnippets);

		$controller = new SnippetController($this->request, $this->userId, $this->snippetService);

		$response = $controller->getSharedSnippets();
		$expectedResponse = JsonResponse::success($sharedSnippets);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testCreateSnippetNoUser(): void {
		$controller = new SnippetController($this->request, null, $this->snippetService);

		$response = $controller->create('New Snippet', 'New Content');
		$expectedResponse = JsonResponse::error('User not found', Http::STATUS_UNAUTHORIZED);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testCreateSnippet(): void {
		$newSnippet = new Snippet();

		$this->snippetService->expects($this->once())
			->method('create')
			->with($this->userId, 'New Snippet', 'New Content')
			->willReturn($newSnippet);

		$controller = new SnippetController($this->request, $this->userId, $this->snippetService);

		$response = $controller->create('New Snippet', 'New Content');
		$expectedResponse = JsonResponse::success($newSnippet, Http::STATUS_CREATED);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testUpdateNoUser(): void {
		$controller = new SnippetController($this->request, null, $this->snippetService);

		$response = $controller->update(1, 'Updated Snippet', 'Updated Content');
		$expectedResponse = JsonResponse::error('User not found', Http::STATUS_UNAUTHORIZED);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testUpdateSnippetNotFound(): void {
		$this->snippetService->expects($this->once())
			->method('find')
			->with(1, $this->userId)
			->willReturn(null);

		$controller = new SnippetController($this->request, $this->userId, $this->snippetService);

		$response = $controller->update(1, 'Updated Snippet', 'Updated Content');
		$expectedResponse = JsonResponse::error('Snippet not found', Http::STATUS_NOT_FOUND);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testUpdateSnippet(): void {
		$snippet = new Snippet();

		$this->snippetService->expects($this->once())
			->method('find')
			->with(1, $this->userId)
			->willReturn($snippet);

		$this->snippetService->expects($this->once())
			->method('update')
			->with(1, $this->userId, 'Updated Snippet', 'Updated Content');

		$controller = new SnippetController($this->request, $this->userId, $this->snippetService);

		$response = $controller->update(1, 'Updated Snippet', 'Updated Content');
		$expectedResponse = JsonResponse::success($snippet, Http::STATUS_OK);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testDeleteNoUser(): void {
		$controller = new SnippetController($this->request, null, $this->snippetService);

		$response = $controller->delete(1);
		$expectedResponse = JsonResponse::error('User not found', Http::STATUS_UNAUTHORIZED);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testDeleteSnippetNotFound(): void {
		$this->snippetService->expects($this->once())
			->method('delete')
			->with(1, $this->userId)
			->willThrowException(new DoesNotExistException('Sharee does not exist'));

		$controller = new SnippetController($this->request, $this->userId, $this->snippetService);

		$response = $controller->delete(1);
		$expectedResponse = JsonResponse::fail('Snippet not found', Http::STATUS_NOT_FOUND);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testDeleteSnippet(): void {
		$snippet = new Snippet();

		$this->snippetService->expects($this->once())
			->method('delete')
			->with(1, $this->userId);


		$controller = new SnippetController($this->request, $this->userId, $this->snippetService);

		$response = $controller->delete(1);
		$expectedResponse = JsonResponse::success(null, Http::STATUS_OK);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testShareNoUser(): void {
		$controller = new SnippetController($this->request, null, $this->snippetService);

		$response = $controller->share(1, 'alice', 'user');
		$expectedResponse = JsonResponse::error('User not found', Http::STATUS_UNAUTHORIZED);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testShareSnippetNotFound(): void {
		$this->snippetService->expects($this->once())
			->method('find')
			->with(1, $this->userId)
			->willReturn(null);

		$controller = new SnippetController($this->request, $this->userId, $this->snippetService);

		$response = $controller->share(1, 'alice', 'user');
		$expectedResponse = JsonResponse::error('Snippet not found', Http::STATUS_NOT_FOUND);
		$this->assertEquals($expectedResponse, $response);
	}


	public function testShareSnippetWrongShareType(): void {
		$this->snippetService->expects($this->once())
			->method('find')
			->with(1, $this->userId)
			->willReturn(new Snippet());

		$controller = new SnippetController($this->request, $this->userId, $this->snippetService);
		$response = $controller->share(1, 'alice', 'country');
		$expectedResponse = JsonResponse::fail('Invalid share type', Http::STATUS_BAD_REQUEST);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testShareSnippetUser(): void {
		$controller = new SnippetController($this->request, $this->userId, $this->snippetService);

		$this->snippetService->expects($this->once())
			->method('find')
			->with(1, $this->userId)
			->willReturn(new Snippet());
		$this->snippetService->expects($this->once())
			->method('share')
			->with(1, 'alice');
		$response = $controller->share(1, 'alice', SnippetShare::TYPE_USER);
		$expectedResponse = JsonResponse::success();
		$this->assertEquals($expectedResponse, $response);
	}
	public function testShareSnippetGroup(): void {
	
		$controller = new SnippetController($this->request, $this->userId, $this->snippetService);

		$this->snippetService->expects($this->once())
			->method('find')
			->with(1, $this->userId)
			->willReturn(new Snippet());
		$this->snippetService->expects($this->once())
			->method('shareWithGroup')
			->with(1, 'Narnia');
		$response = $controller->share(1, 'Narnia', SnippetShare::TYPE_GROUP);
		$expectedResponse = JsonResponse::success();
		$this->assertEquals($expectedResponse, $response);
	}

	public function testGetSharesNoUser(): void {
		$controller = new SnippetController($this->request, null, $this->snippetService);

		$response = $controller->getShares(1);
		$expectedResponse = JsonResponse::error('User not found', Http::STATUS_UNAUTHORIZED);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testGetSharesSnippetNotFound(): void {
		$this->snippetService->expects($this->once())
			->method('find')
			->with(1, $this->userId)
			->willReturn(null);

		$controller = new SnippetController($this->request, $this->userId, $this->snippetService);

		$response = $controller->getShares(1);
		$expectedResponse = JsonResponse::error('Snippet not found', Http::STATUS_NOT_FOUND);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testGetShares(): void {
		$shares = [
			new SnippetShare(),
			new SnippetShare()
		];

		$this->snippetService->expects($this->once())
			->method('find')
			->with(1, $this->userId)
			->willReturn(new Snippet());

		$this->snippetService->expects($this->once())
			->method('getShares')
			->with(1)
			->willReturn($shares);

		$controller = new SnippetController($this->request, $this->userId, $this->snippetService);

		$response = $controller->getShares(1);
		$expectedResponse = JsonResponse::success($shares);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testDeleteShareNoUser(): void {
		$controller = new SnippetController($this->request, null, $this->snippetService);

		$response = $controller->deleteShare(1, 'alice');
		$expectedResponse = JsonResponse::error('User not found', Http::STATUS_UNAUTHORIZED);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testDeleteShareSnippetNotFound(): void {
		$this->snippetService->expects($this->once())
			->method('find')
			->with(1, $this->userId)
			->willReturn(null);

		$controller = new SnippetController($this->request, $this->userId, $this->snippetService);

		$response = $controller->deleteShare(1, 'alice');
		$expectedResponse = JsonResponse::error('Snippet not found', Http::STATUS_NOT_FOUND);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testDeleteShare(): void {
		$this->snippetService->expects($this->once())
			->method('find')
			->with(1, $this->userId)
			->willReturn(new Snippet());

		$this->snippetService->expects($this->once())
			->method('unshare')
			->with(1, 'alice');

		$controller = new SnippetController($this->request, $this->userId, $this->snippetService);

		$response = $controller->deleteShare(1, 'alice');
		$expectedResponse = JsonResponse::success();
		$this->assertEquals($expectedResponse, $response);
	}

}
