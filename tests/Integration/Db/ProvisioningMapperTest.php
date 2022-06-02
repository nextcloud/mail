<?php

declare(strict_types=1);

/**
 * @author Anna Larch <anna.larch@nextcloud.com>
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

use OC;
use ChristophWurst\Nextcloud\Testing\DatabaseTransaction;
use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\Provisioning;
use OCA\Mail\Db\ProvisioningMapper;
use OCA\Mail\Exception\ValidationException;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

/**
 * @group DB
 */
class ProvisioningMapperTest extends TestCase {
	use DatabaseTransaction;

	/** @var ProvisioningMapper */
	private $mapper;

	/** @var IDBConnection */
	private $db;

	/** @var MockObject */
	private $logger;

	/** @var Alias */
	private $alias;

	/** @var [] */
	public $data = [];

	/**
	 * Initialize Mapper
	 */
	public function setup(): void {
		parent::setUp();
		$this->db = OC::$server->getDatabaseConnection();
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->mapper = new ProvisioningMapper($this->db, $this->logger);

		$this->data['provisioningDomain'] = 'heart-of-gold.com' ;
		$this->data['emailTemplate'] = '%USERID%@heart-of-gold.com';
		$this->data['imapUser'] = 'marvin@heart-of-gold.com';
		$this->data['imapHost'] = 'imap.heart-of-gold.com';
		$this->data['imapPort'] = '42';
		$this->data['imapSslMode'] = 'none';
		$this->data['smtpUser'] = 'trillian@heart-of-gold.com';
		$this->data['smtpHost'] = 'smtp.heart-of-gold.com';
		$this->data['smtpPort'] = 24;
		$this->data['smtpSslMode'] = 'none';
		$this->data['sieveEnabled'] = false;
	}

	public function testValidate() {
		$provisioning = new Provisioning();
		$provisioning->setId(1);
		$provisioning->setProvisioningDomain($this->data['provisioningDomain']);
		$provisioning->setEmailTemplate($this->data['emailTemplate']);
		$provisioning->setImapUser($this->data['imapUser']);
		$provisioning->setImapHost($this->data['imapHost']);
		$provisioning->setImapPort(42);
		$provisioning->setImapSslMode($this->data['imapSslMode']);
		$provisioning->setSmtpUser($this->data['smtpUser']);
		$provisioning->setSmtpHost($this->data['smtpHost']);
		$provisioning->setSmtpPort(24);
		$provisioning->setSmtpSslMode($this->data['smtpSslMode']);
		$provisioning->setSieveEnabled($this->data['sieveEnabled']);

		$provisioning = $this->mapper->validate($this->data);

		$this->assertInstanceOf(Provisioning::class, $provisioning);
		foreach ($this->data as $key => $value) {
			$getter = 'get' . ucfirst($key);
			if ($key === 'imapPort' || $key === 'smtpPort') {
				$this->assertIsInt($provisioning->$getter());
				$this->assertEquals($provisioning->$getter(), (int)$value);
			} else {
				$this->assertEquals($provisioning->$getter(), $value);
			}
		}
	}

	public function testValidateException() {
		$data = [];
		$data['provisioningDomain'] = 'heart-of-gold.com' ;

		$this->expectException(ValidationException::class);

		$provisioning = $this->mapper->validate($data);
	}

	public function testGetNoResult() {
		$db = $this->mapper->get(99999);
		$this->assertNull($db);
	}

	/**
	 * @return void
	 */
	public function testUpdate() {
		$provisioning = new Provisioning();
		$provisioning->setProvisioningDomain('somebody-elses-problem.com');
		$provisioning->setEmailTemplate($this->data['emailTemplate']);
		$provisioning->setImapUser($this->data['imapUser']);
		$provisioning->setImapHost($this->data['imapHost']);
		$provisioning->setImapPort(42);
		$provisioning->setImapSslMode($this->data['imapSslMode']);
		$provisioning->setSmtpUser($this->data['smtpUser']);
		$provisioning->setSmtpHost($this->data['smtpHost']);
		$provisioning->setSmtpPort(24);
		$provisioning->setSmtpSslMode($this->data['smtpSslMode']);
		$provisioning->setSieveEnabled($this->data['sieveEnabled']);
		$provisioning = $this->mapper->insert($provisioning);
		$id = $provisioning->getId();
		$provisioning->setProvisioningDomain('arthur-dent.com');

		$update = $this->mapper->update($provisioning);

		$this->assertInstanceOf(Provisioning::class, $update);
		$this->assertEquals($id, $update->getId());
		$this->assertEquals('arthur-dent.com', $update->getProvisioningDomain());
	}

	public function testGetAll() {
		$provisioning = new Provisioning();
		$provisioning->setProvisioningDomain($this->data['provisioningDomain']);
		$provisioning->setEmailTemplate($this->data['emailTemplate']);
		$provisioning->setImapUser($this->data['imapUser']);
		$provisioning->setImapHost($this->data['imapHost']);
		$provisioning->setImapPort(42);
		$provisioning->setImapSslMode($this->data['imapSslMode']);
		$provisioning->setSmtpUser($this->data['smtpUser']);
		$provisioning->setSmtpHost($this->data['smtpHost']);
		$provisioning->setSmtpPort(24);
		$provisioning->setSmtpSslMode($this->data['smtpSslMode']);
		$provisioning->setSieveEnabled($this->data['sieveEnabled']);
		$provisioning = $this->mapper->insert($provisioning);

		$results = $this->mapper->getAll();

		$this->assertIsArray($results);
		$this->assertNotEmpty($results);
		foreach ($results as $result) {
			$this->assertInstanceOf(Provisioning::class, $result);
		}
	}

	public function testGet() {
		$provisioning = new Provisioning();
		$provisioning->setProvisioningDomain($this->data['provisioningDomain']);
		$provisioning->setEmailTemplate($this->data['emailTemplate']);
		$provisioning->setImapUser($this->data['imapUser']);
		$provisioning->setImapHost($this->data['imapHost']);
		$provisioning->setImapPort(42);
		$provisioning->setImapSslMode($this->data['imapSslMode']);
		$provisioning->setSmtpUser($this->data['smtpUser']);
		$provisioning->setSmtpHost($this->data['smtpHost']);
		$provisioning->setSmtpPort(24);
		$provisioning->setSmtpSslMode($this->data['smtpSslMode']);
		$provisioning->setSieveEnabled($this->data['sieveEnabled']);
		$provisioning = $this->mapper->insert($provisioning);

		$db = $this->mapper->get($provisioning->getId());

		$this->assertInstanceOf(Provisioning::class, $db);
		foreach ($this->data as $key => $value) {
			$getter = 'get' . ucfirst($key);
			if ($key === 'imapPort' || $key === 'smtpPort') {
				$this->assertEquals($db->$getter(), (int)$value);
			} else {
				$this->assertEquals($db->$getter(), $value);
			}
		}
	}
}
