<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Unit\Controller;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Controller\AliasesController;
use OCA\Mail\Db\Alias;
use OCA\Mail\Db\AliasMapper;
use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\NotImplemented;
use OCA\Mail\Service\AliasesService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;

class AliasesControllerTest extends TestCase {
	private $controller;
	private $appName = 'mail';
	private $request;
	private $userId = 'user12345';
	private $alias;

	/** @var AliasMapper */
	private $aliasMapper;

	/** @var MailAccountMapper */
	private $mailAccountMapper;

	/** @var AliasesService */
	private $aliasService;

	public function setUp(): void {
		parent::setUp();
		$this->request = $this->getMockBuilder('OCP\IRequest')
			->getMock();

		$this->alias = $this->getMockBuilder(\OCA\Mail\Db\Alias::class)
			->disableOriginalConstructor()
			->getMock();

		$this->aliasMapper = $this->createMock(AliasMapper::class);
		$this->mailAccountMapper = $this->createMock(MailAccountMapper::class);

		$this->aliasService = new AliasesService($this->aliasMapper, $this->mailAccountMapper);
		$this->controller = new AliasesController($this->appName, $this->request, $this->aliasService, $this->userId);
	}

	public function testIndex(): void {
		$alias = new Alias();
		$alias->setId(100);
		$alias->setAccountId(200);
		$alias->setName('Jane Doe');
		$alias->setAlias('jane@doe.com');

		$this->aliasMapper->expects($this->once())
			->method('findAll')
			->with($alias->getAccountId(), $this->userId)
			->willReturn([$alias]);

		$expectedResponse = new JSONResponse([$alias]);
		$response = $this->controller->index($alias->getAccountId());

		$this->assertEquals($expectedResponse, $response);
	}

	public function testShow(): void {
		$this->expectException(NotImplemented::class);
		$this->controller->show();
	}

	public function testUpdate(): void {
		$alias = new Alias();
		$alias->setId(101);
		$alias->setAccountId(200);
		$alias->setName('Jane Doe');
		$alias->setAlias('jane@doe.com');

		$this->aliasMapper->expects($this->once())
			->method('find')
			->with($alias->getId(), $this->userId)
			->willReturn($alias);

		$this->aliasMapper->expects($this->once())
			->method('update')
			->willReturnArgument(0);

		$response = $this->controller->update($alias->getId(), 'john@doe.com', 'John Doe');
		/** @var Alias $data */
		$data = $response->getData();

		$this->assertInstanceOf(Alias::class, $response->getData());
		$this->assertEquals('john@doe.com', $data->getAlias());
		$this->assertEquals('John Doe', $data->getName());
	}

	public function testUpdateProvisioned(): void {
		$alias = new Alias();
		$alias->setId(201);
		$alias->setAccountId(300);
		$alias->setName('Jane Doe');
		$alias->setAlias('jane@doe.com');
		$alias->setProvisioningId(100);

		$this->aliasMapper->expects($this->once())
			->method('find')
			->with($alias->getId(), $this->userId)
			->willReturn($alias);

		$this->aliasMapper->expects($this->once())
			->method('update')
			->willReturnArgument(0);

		$response = $this->controller->update($alias->getId(), 'john@doe.com', 'John Doe');
		/** @var Alias $data */
		$data = $response->getData();

		$this->assertInstanceOf(Alias::class, $data);
		$this->assertEquals('jane@doe.com', $data->getAlias());
		$this->assertEquals('John Doe', $data->getName());
	}

	public function testDestroy(): void {
		$alias = new Alias();
		$alias->setId(101);
		$alias->setAccountId(200);
		$alias->setName('Jane Doe');
		$alias->setAlias('jane@doe.com');

		$this->aliasMapper->expects($this->once())
			->method('find')
			->with($alias->getId(), $this->userId)
			->willReturn($alias);

		$this->aliasMapper->expects($this->once())
			->method('delete')
			->willReturnArgument(0);

		$expectedResponse = new JSONResponse($alias);
		$response = $this->controller->destroy($alias->getId());

		$this->assertEquals($expectedResponse, $response);
	}

	public function testDestroyProvisioned(): void {
		$this->expectException(ClientException::class);
		$this->expectExceptionMessage('Deleting a provisioned alias is not allowed.');

		$alias = new Alias();
		$alias->setId(201);
		$alias->setAccountId(300);
		$alias->setName('Jane Doe');
		$alias->setAlias('jane@doe.com');
		$alias->setProvisioningId(100);

		$this->aliasMapper->expects($this->once())
			->method('find')
			->with($alias->getId(), $this->userId)
			->willReturn($alias);

		$this->controller->destroy($alias->getId());
	}

	public function testCreate(): void {
		$alias = new Alias();
		$alias->setId(102);
		$alias->setAccountId(200);
		$alias->setName('Jane Doe');
		$alias->setAlias('jane@doe.com');

		$this->mailAccountMapper->expects($this->once())
			->method('find');

		$this->aliasMapper->expects($this->once())
			->method('insert')
			->willReturn($alias);

		$expectedResponse = new JSONResponse(
			$alias,
			Http::STATUS_CREATED
		);
		$response = $this->controller->create(
			$alias->getAccountId(),
			$alias->getAlias(),
			$alias->getName()
		);

		$this->assertEquals($expectedResponse, $response);
	}

	public function testCreateForbiddenAccountId(): void {
		$this->expectException(DoesNotExistException::class);

		$entity = new Alias();
		$entity->setAccountId(200);
		$entity->setAlias('jane@doe.com');
		$entity->setName('Jane Doe');

		$this->mailAccountMapper->expects($this->once())
			->method('find')
			->willThrowException(new DoesNotExistException('Account does not exist'));

		$this->controller->create(
			$entity->getAccountId(),
			$entity->getAlias(),
			$entity->getName()
		);
	}

	public function testUpdateSignature(): void {
		$alias = new Alias();
		$alias->setId(102);
		$alias->setAccountId(200);
		$alias->setName('Jane Doe');
		$alias->setAlias('jane@doe.com');
		$alias->setSignature('my old signature');

		$this->aliasMapper->expects($this->once())
			->method('find')
			->willReturn($alias);
		$this->aliasMapper->expects($this->once())
			->method('update')
			->willReturnArgument(0);

		$expectedResponse = new JSONResponse(
			$alias,
			Http::STATUS_OK
		);
		$response = $this->controller->updateSignature(
			$alias->getId(),
			'my new signature'
		);

		$this->assertEquals($expectedResponse, $response);
	}

	public function testUpdateSignatureInvalidAliasId(): void {
		$this->expectException(DoesNotExistException::class);

		$entity = new Alias();
		$entity->setId(999999);
		$entity->setAccountId(200);
		$entity->setAlias('jane@doe.com');
		$entity->setName('Jane Doe');

		$this->aliasMapper->expects($this->once())
			->method('find')
			->willThrowException(new DoesNotExistException('Alias does not exist'));

		$this->controller->updateSignature(
			$entity->getId(),
			'my new signature'
		);
	}
}
