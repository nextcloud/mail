<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Integration\Service;

use Horde_Imap_Client_Socket;
use OCA\Mail\Account;
use OCA\Mail\Folder;
use OCA\Mail\IMAP\FolderMapper;
use OCA\Mail\Tests\Integration\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class FolderMapperIntegrationTest extends TestCase {
	/** @var FolderMapper */
	private $mapper;

	/** @var LoggerInterface|MockObject */
	private $logger;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(LoggerInterface::class);
		$this->mapper = new FolderMapper($this->logger);
	}

	/**
	 * @return Horde_Imap_Client_Socket
	 */
	private function getTestClient() {
		return new Horde_Imap_Client_Socket([
			'username' => 'user@domain.tld',
			'password' => 'mypassword',
			'hostspec' => '127.0.0.1',
			'port' => 993,
			'secure' => 'ssl',
		]);
	}

	public function testGetFolders() {
		$account = $this->createMock(Account::class);
		$account->method('getId')->willReturn(13);
		$client = $this->getTestClient();

		$folders = $this->mapper->getFolders($account, $client);

		$this->assertGreaterThan(1, count($folders));
		$this->assertContainsOnlyInstancesOf(Folder::class, $folders);
	}
}
