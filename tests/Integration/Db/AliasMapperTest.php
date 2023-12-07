<?php

declare(strict_types=1);

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

namespace OCA\Mail\Tests\Integration\Db;

use ChristophWurst\Nextcloud\Testing\DatabaseTransaction;
use ChristophWurst\Nextcloud\Testing\TestCase;
use OC;
use OCA\Mail\Db\Alias;
use OCA\Mail\Db\AliasMapper;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\MailAccountMapper;
use OCP\IDBConnection;

/**
 * @group DB
 */
class AliasMapperTest extends TestCase {
	use DatabaseTransaction;

	/** @var AliasMapper */
	private $mapper;

	/** @var IDBConnection */
	private $db;

	/** @var Alias */
	private $alias;

	/**
	 * Initialize Mapper
	 */
	public function setup(): void {
		parent::setUp();
		$this->db = OC::$server->getDatabaseConnection();
		$this->mapper = new AliasMapper($this->db);
	}

	public function testFind() {
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
		$this->alias->setSignature('Kind regards<br>Alias');
		/** @var Alias $b */
		$b = $this->mapper->insert($this->alias);

		$result = $this->mapper->find($b->getId(), $account->getUserId());

		$this->assertEquals(
			[
				'accountId' => $this->alias->getAccountId(),
				'name' => $this->alias->getName(),
				'alias' => $this->alias->getAlias(),
				'id' => $this->alias->getId(),
				'signature' => $this->alias->getSignature(),
			], [
				'accountId' => $result->getAccountId(),
				'name' => $result->getName(),
				'alias' => $result->getAlias(),
				'id' => $result->getId(),
				'signature' => $this->alias->getSignature(),
			]
		);
	}
}
