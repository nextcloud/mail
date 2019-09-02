<?php declare(strict_types=1);

/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\Mail\Tests\Db;

use ChristophWurst\Nextcloud\Testing\DatabaseTransaction;
use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Db\MailboxMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class MailboxMapperTest extends TestCase {

	use DatabaseTransaction;

	/** @var IDBConnection */
	private $db;

	/** @var MailboxMapper */
	private $mapper;

	protected function setUp() {
		parent::setUp();

		$this->db = \OC::$server->getDatabaseConnection();
		$this->mapper = new MailboxMapper(
			$this->db
		);

		$qb = $this->db->getQueryBuilder();

		$delete = $qb->delete($this->mapper->getTableName());
		$delete->execute();
	}

	public function testFindAllNoData() {
		$account = $this->createMock(Account::class);
		$account->method('getId')->willReturn(13);

		$result = $this->mapper->findAll($account);

		$this->assertEmpty($result);
	}

	public function testFindAll() {
		$account = $this->createMock(Account::class);
		$account->method('getId')->willReturn(13);
		foreach (range(1, 10) as $i) {
			$qb = $this->db->getQueryBuilder();
			$insert = $qb->insert($this->mapper->getTableName())
				->values([
					'name' => $qb->createNamedParameter("folder$i"),
					'account_id' => $qb->createNamedParameter($i <= 5 ? 13 : 14, IQueryBuilder::PARAM_INT),
					'sync_token' => $qb->createNamedParameter('VTEsVjE0Mjg1OTkxNDk='),
					'delimiter' => $qb->createNamedParameter('.'),
					'messages' => $qb->createNamedParameter($i * 100, IQueryBuilder::PARAM_INT),
					'unseen' => $qb->createNamedParameter($i, IQueryBuilder::PARAM_INT),
					'selectable' => $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL),
				]);
			$insert->execute();
		}

		$result = $this->mapper->findAll($account);

		$this->assertCount(5, $result);
	}

}
