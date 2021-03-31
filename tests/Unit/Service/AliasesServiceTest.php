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

	/** @var Alias */
	private $alias;

	protected function setUp(): void {
		parent::setUp();

		$this->aliasMapper = $this->createMock(AliasMapper::class);
		$this->mailAccountMapper = $this->createMock(MailAccountMapper::class);
		$this->alias = $this->createMock(Alias::class);

		$this->service = new AliasesService(
			$this->aliasMapper,
			$this->mailAccountMapper
		);
	}

	public function testFindAll() {
		$accountId = 123;
		$this->aliasMapper->expects($this->once())
			->method('findAll')
			->with($accountId, $this->user)
			->will($this->returnValue([$this->alias]));

		$actual = $this->service->findAll($accountId, $this->user);

		$expected = [
			$this->alias
		];
		$this->assertEquals($expected, $actual);
	}

	public function testFind() {
		$aliasId = 123;
		$this->aliasMapper->expects($this->once())
			->method('find')
			->with($aliasId, $this->user)
			->will($this->returnValue($this->alias));

		$actual = $this->service->find($aliasId, $this->user);

		$expected = $this->alias;
		$this->assertEquals($expected, $actual);
	}

	public function testCreate(): void {
		$entity = new Alias();
		$entity->setAccountId(200);
		$entity->setAlias('jane@doe.com');
		$entity->setName('Jane Doe');

		$this->mailAccountMapper->expects($this->once())
			->method('find');

		$this->aliasMapper->expects($this->once())
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
		$this->expectException(ClientException::class);

		$entity = new Alias();
		$entity->setAccountId(200);
		$entity->setAlias('jane@doe.com');
		$entity->setName('Jane Doe');

		$this->mailAccountMapper->expects($this->once())
			->method('find')
			->willThrowException(new DoesNotExistException('Account does not exist'));

		$this->service->create(
			300,
			$entity->getAccountId(),
			$entity->getAlias(),
			$entity->getName()
		);
	}

	public function testDelete() {
		$aliasId = 123;
		$this->aliasMapper->expects($this->once())
			->method('find')
			->with($aliasId, $this->user)
			->will($this->returnValue($this->alias));
		$this->aliasMapper->expects($this->once())
			->method('delete')
			->with($this->alias);

		$this->service->delete($aliasId, $this->user);
	}
}
