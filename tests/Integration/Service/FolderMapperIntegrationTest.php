<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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

namespace OCA\Mail\Tests\Integration\Service;

use Horde_Imap_Client_Socket;
use OCA\Mail\Account;
use OCA\Mail\Service\FolderMapper;
use OCP\Files\Folder;
use PHPUnit_Framework_TestCase;

class FolderMapperIntegrationTest extends PHPUnit_Framework_TestCase {

	/** @var FolderMapper */
	private $mapper;

	protected function setUp() {
		parent::setUp();

		$this->mapper = new FolderMapper();
	}

	/**
	 * @return Horde_Imap_Client_Socket
	 */
	private function getTestClient() {
		return new Horde_Imap_Client_Socket([
			'username' => 'user@domain.tld',
			'password' => 'mypassword',
			'hostspec' => 'localhost',
			'port' => 993,
			'secure' => 'ssl',
		]);
	}

	public function testGetFolders() {
		$account = $this->createMock(Account::class);
		$client = $this->getTestClient();

		$folders = $this->mapper->getFolders($account, $client);

		$this->assertGreaterThan(1, count($folders));
		foreach ($folders as $folder) {
			$this->assertInstanceOf(Folder::class, $folder);
		}
	}

}
