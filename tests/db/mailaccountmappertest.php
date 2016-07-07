<?php

/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * ownCloud - Mail
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

class MailAccountMapperTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var MailAccountMapper
	 */
	private $mapper;

	/**
	 * @var \OC\DB\Connection
	 */
	private $db;

	/**
	 * @var MailAccount
	 */
	private $account;

	/**
	 * Initialize Mapper
	 */
	public function setup(){
		$db = \OC::$server->getDatabaseConnection();
		$this->db = new Db($db);
		$this->mapper = new MailAccountMapper($this->db);

		$this->account = new MailAccount();
		$this->account->setName('Peter Parker');
		$this->account->setInboundHost('mail.marvel.com');
		$this->account->setInboundPort(159);
		$this->account->setInboundUser('spiderman');
		$this->account->setInboundPassword('xxxxxxxx');
		$this->account->setInboundSslMode('tls');
		$this->account->setEmail('peter.parker@marvel.com');
		$this->account->setOutboundHost('smtp.marvel.com');
		$this->account->setOutboundPort(458);
		$this->account->setOutboundUser('spiderman');
		$this->account->setOutboundPassword('xxxx');
		$this->account->setOutboundSslMode('ssl');
		$this->account->setUserId('user12345');
	}

	public function testFind(){

		/** @var MailAccount $b */
		$b = $this->mapper->insert($this->account);

		$result = $this->mapper->find($b->getUserId(), $b->getId());
		$this->assertEquals($b->toJson(), $result->toJson());

		$result = $this->mapper->findByUserId($b->getUserId());
		$c = array_filter($result, function($a) use($b){
			/** @var MailAccount $a */
			return $a->getId() === $b->getId();
		});
		$c = array_pop($c);
		$this->assertEquals($b->toJson(), $c->toJson());
	}

	public function testSave() {

		$a = $this->account;

		// test insert
		$b = $this->mapper->save($a);
		$this->assertNotNull($b);
		$this->assertNotNull($a->getId());
		$this->assertNotNull($b->getId());
		$this->assertEquals($a->getId(), $b->getId());

		// update the entity
		$b->setEmail('spiderman@marvel.com');
		$c = $this->mapper->save($b);
		$this->assertNotNull($c);
		$this->assertNotNull($c->getId());
		$this->assertNotNull($b->getId());
		$this->assertEquals($b->getId(), $c->getId());
	}
}
