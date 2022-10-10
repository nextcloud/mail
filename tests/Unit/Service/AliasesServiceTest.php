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

namespace OCA\Mail\Tests\Unit\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\Alias;
use OCA\Mail\Db\AliasMapper;
use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Service\AliasesService;
use OCP\AppFramework\Db\DoesNotExistException;

class AliasesServiceTest extends TestCase {
	/** @var AliasesService */
	private $service;

	/** @var string */
	private $user = 'herbert';

	/** @var AliasMapper */
	private $aliasMapper;

	/** @var MailAccountMapper */
	private $mailAccountMapper;

	protected function setUp(): void {
		parent::setUp();

		$this->aliasMapper = $this->createMock(AliasMapper::class);
		$this->mailAccountMapper = $this->createMock(MailAccountMapper::class);

		$this->service = new AliasesService(
			$this->aliasMapper,
			$this->mailAccountMapper
		);
	}

	public function testFindAll(): void {
		$entity = new Alias();
		$entity->setAccountId(200);
		$entity->setAlias('jane@doe.com');
		$entity->setName('Jane Doe');

		$this->aliasMapper->expects(self::once())
			->method('findAll')
			->with($entity->getAccountId(), $this->user)
			->willReturn([$entity]);

		$aliases = $this->service->findAll($entity->getAccountId(), $this->user);

		$this->assertEquals([$entity], $aliases);
	}

	public function testFind(): void {
		$entity = new Alias();
		$entity->setId(101);
		$entity->setAccountId(200);
		$entity->setAlias('jane@doe.com');
		$entity->setName('Jane Doe');

		$this->aliasMapper->expects(self::once())
			->method('find')
			->with($entity->getId(), $this->user)
			->willReturn($entity);

		$alias = $this->service->find($entity->getId(), $this->user);

		$this->assertEquals($entity, $alias);
	}

	public function testCreate(): void {
		$entity = new Alias();
		$entity->setId(101);
		$entity->setAccountId(200);
		$entity->setAlias('jane@doe.com');
		$entity->setName('Jane Doe');

		$this->mailAccountMapper->expects($this->once())
			->method('find');

		$this->aliasMapper->expects(self::once())
			->method('insert')
			->willReturnCallback(static function (Alias $alias) {
				$alias->setId(100);
				return $alias;
			});

		$result = $this->service->create(
			300,
			$entity->getAccountId(),
			$entity->getAlias(),
			$entity->getName()
		);

		$this->assertEquals(100, $result->getId());
		$this->assertEquals($entity->getAccountId(), $result->getAccountId());
		$this->assertEquals($entity->getAlias(), $result->getAlias());
		$this->assertEquals($entity->getName(), $result->getName());
	}

	public function testCreateForbiddenAccountId(): void {
		$this->expectException(DoesNotExistException::class);

		$entity = new Alias();
		$entity->setAccountId(200);
		$entity->setAlias('jane@doe.com');
		$entity->setName('Jane Doe');

		$this->mailAccountMapper->expects(self::once())
			->method('find')
			->willThrowException(new DoesNotExistException('Account does not exist'));

		$this->service->create(
			300,
			$entity->getAccountId(),
			$entity->getAlias(),
			$entity->getName()
		);
	}

	public function testDelete(): void {
		$entity = new Alias();
		$entity->setId(101);
		$entity->setAccountId(200);
		$entity->setName('Jane Doe');
		$entity->setAlias('jane@doe.com');

		$this->aliasMapper->expects(self::once())
			->method('find')
			->with($entity->getId(), $this->user)
			->willReturn($entity);
		$this->aliasMapper->expects(self::once())
			->method('delete')
			->willReturnArgument(0);

		$alias = $this->service->delete($this->user, $entity->getId());

		$this->assertEquals($entity, $alias);
	}

	public function testDeleteProvisioned(): void {
		$this->expectException(ClientException::class);
		$this->expectExceptionMessage('Deleting a provisioned alias is not allowed.');

		$entity = new Alias();
		$entity->setId(201);
		$entity->setAccountId(300);
		$entity->setName('Jane Doe');
		$entity->setAlias('jane@doe.com');
		$entity->setProvisioningId(100);

		$this->aliasMapper->expects(self::once())
			->method('find')
			->with($entity->getId(), $this->user)
			->willReturn($entity);

		$this->service->delete($this->user, $entity->getId());
	}

	public function testUpdateSignature(): void {
		$entity = new Alias();
		$entity->setId(101);
		$entity->setAccountId(200);
		$entity->setName('Jane Doe');
		$entity->setAlias('jane@doe.com');

		$this->aliasMapper->expects(self::once())
			->method('find')
			->with($entity->getId(), $this->user)
			->willReturn($entity);
		$this->aliasMapper->expects(self::once())
			->method('update')
			->willReturnArgument(0);

		$this->service->updateSignature($this->user, $entity->getId(), 'Kind regards<br>Herbert');
	}

	public function testUpateSignatureInvalidAliasId(): void {
		$this->expectException(DoesNotExistException::class);

		$this->aliasMapper->expects(self::once())
			->method('find')
			->willThrowException(new DoesNotExistException('Alias does not exist'));
		$this->aliasMapper->expects(self::never())
			->method('update');

		$this->service->updateSignature($this->user, '999999', 'Kind regards<br>Herbert');
	}
}
