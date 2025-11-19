<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Integration\Db;

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

	private ?\OCA\Mail\Db\ProvisioningMapper $mapper = null;

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
		$this->db = \OCP\Server::get(\OCP\IDBConnection::class);
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

	public function testValidate(): void {
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
			$getter = 'get' . ucfirst((string)$key);
			if ($key === 'imapPort' || $key === 'smtpPort') {
				$this->assertIsInt($provisioning->$getter());
				$this->assertEquals($provisioning->$getter(), (int)$value);
			} else {
				$this->assertEquals($provisioning->$getter(), $value);
			}
		}
	}

	public function testValidateException(): void {
		$data = [];
		$data['provisioningDomain'] = 'heart-of-gold.com' ;

		$this->expectException(ValidationException::class);

		$this->mapper->validate($data);
	}

	public function testGetNoResult(): void {
		$db = $this->mapper->get(99999);
		$this->assertNull($db);
	}

	public function testUpdate(): void {
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

	public function testGetAll(): void {
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
		$this->mapper->insert($provisioning);

		$results = $this->mapper->getAll();

		$this->assertIsArray($results);
		$this->assertNotEmpty($results);
		$this->assertContainsOnlyInstancesOf(Provisioning::class, $results);
	}

	public function testGet(): void {
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
			$getter = 'get' . ucfirst((string)$key);
			if ($key === 'imapPort' || $key === 'smtpPort') {
				$this->assertEquals($db->$getter(), (int)$value);
			} else {
				$this->assertEquals($db->$getter(), $value);
			}
		}
	}

	public function testFindUniqueImapHosts(): void {
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
		$this->mapper->insert($provisioning);

		$hosts = $this->mapper->findUniqueImapHosts();

		$this->assertIsArray($hosts);
		$this->assertNotEmpty($hosts);
		$this->assertEquals($this->data['imapHost'], $hosts[0]);
	}
}
