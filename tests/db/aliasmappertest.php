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

namespace OCA\Mail\Db;

use OC\AppFramework\Db\Db;

class AliasMapperTest extends \PHPUnit_Framework_TestCase {
	/**
	 * @var AliasMapper
	 */
	private $mapper;
	/**
	 * @var \OC\DB\Connection
	 */
	private $db;
	/**
	 * @var Alias
	 */
	private $alias;
	/**
	 * Initialize Mapper
	 */
	public function setup(){
		$db = \OC::$server->getDatabaseConnection();
		$this->db = new Db($db);
		$this->mapper = new AliasMapper($this->db);
	}

	public function testFind(){
		$accountMapper = new MailAccountMapper($this->db);
		$account = new MailAccount();
		$account->setName('Peter Parker');
		$account->setInboundHost('mail.marvel.com');
		$account->setInboundPort(159);
		$account->setInboundUser('spiderman');
		$account->setInboundPassword('xxxxxxxx');
		$account->setInboundSslMode('tls');
		$account->setEmail('peter.parker@marvel.com');
		$account->setOutboundHost('smtp.marvel.com');
		$account->setOutboundPort(458);
		$account->setOutboundUser('spiderman');
		$account->setOutboundPassword('xxxx');
		$account->setOutboundSslMode('ssl');
		$account->setUserId('user12345');
		$a = $accountMapper->insert($account);
		$this->alias = new Alias();
		$this->alias->setAccountId($a->getId());
		$this->alias->setAlias('alias@marvel.com');
		$this->alias->setName('alias');
		/** @var Alias $b */
		$b = $this->mapper->insert($this->alias);

		$result = $this->mapper->find($b->getId(), $account->getUserId());

		$this->assertEquals(
			[
				'accountId' => $this->alias->getAccountId(),
				'name' => $this->alias->getName(),
				'alias' => $this->alias->getAlias(),
				'id' => $this->alias->getId()
			],
			[
				'accountId' => $result->getAccountId(),
				'name' => $result->getName(),
				'alias' => $result->getAlias(),
				'id' => $result->getId()
			]
		);
	}
	protected function tearDown() {
		parent::tearDown();

		$sql = 'DELETE FROM *PREFIX*mail_aliases WHERE `id` = ?';
		$stmt = $this->db->prepare($sql);
		if (!empty($this->alias)) {
			$stmt->execute([$this->alias->getId()]);
		}
		$sql = 'DELETE FROM *PREFIX*mail_accounts WHERE `user_id` = ?';
		$stmt = $this->db->prepare($sql);
		if (!empty($this->alias)) {
			$stmt->execute(['user12345']);
		}
	}
}
