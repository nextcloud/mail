<?php

/**
 * @author Tahaa Karim <tahaalibra@gmail.com>
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

		$this->alias = $this->getMockBuilder('\OCA\Mail\Db\Alias')
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
		$this->expectException(NotImplemented::class);
		$this->controller->update();
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
			->with($alias);

		$expectedResponse = new JSONResponse($alias);
		$response = $this->controller->destroy($alias->getId());

		$this->assertEquals($expectedResponse, $response);
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
		$this->expectException(ClientException::class);

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
}
