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

namespace OCA\Mail\Tests\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client_Socket;
use OCA\Mail\Account;
use OCA\Mail\IMAP\FolderMapper;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\MailboxPrefixDetector;
use OCA\Mail\IMAP\MessageMapper;
use OCA\Mail\IMAP\Sync\Request;
use OCA\Mail\IMAP\Sync\Response;
use OCA\Mail\IMAP\Sync\Synchronizer;
use OCA\Mail\Service\FolderNameTranslator;
use OCA\Mail\Service\MailManager;
use OCP\Files\Folder;
use PHPUnit_Framework_MockObject_MockObject;

class MailManagerTest extends TestCase {

	/** @var IMAPClientFactory|PHPUnit_Framework_MockObject_MockObject */
	private $imapClientFactory;

	/** @var FolderMapper|PHPUnit_Framework_MockObject_MockObject */
	private $folderMapper;

	/** @var MailboxPrefixDetector|PHPUnit_Framework_MockObject_MockObject */
	private $prefixDetector;

	/** @var MessageMapper|PHPUnit_Framework_MockObject_MockObject */
	private $messageMapper;

	/** @var FolderNameTranslator|PHPUnit_Framework_MockObject_MockObject */
	private $translator;

	/** @var Synchronizer|PHPUnit_Framework_MockObject_MockObject */
	private $sync;

	/** @varr MailManager */
	private $manager;

	protected function setUp() {
		parent::setUp();

		$this->imapClientFactory = $this->createMock(IMAPClientFactory::class);
		$this->folderMapper = $this->createMock(FolderMapper::class);
		$this->prefixDetector = $this->createMock(MailboxPrefixDetector::class);
		$this->messageMapper = $this->createMock(MessageMapper::class);
		$this->translator = $this->createMock(FolderNameTranslator::class);
		$this->sync = $this->createMock(Synchronizer::class);

		$this->manager = new MailManager($this->imapClientFactory,
			$this->folderMapper, $this->prefixDetector, $this->translator, $this->sync,
			$this->messageMapper);
	}

	public function testGetFolders() {
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$account = $this->createMock(Account::class);
		$this->prefixDetector->expects($this->once())
			->method('havePrefix')
			->willReturn(false);
		$this->imapClientFactory->expects($this->once())
			->method('getClient')
			->willReturn($client);
		$folders = [
			$this->createMock(Folder::class),
			$this->createMock(Folder::class),
		];
		$this->folderMapper->expects($this->once())
			->method('getFolders')
			->with($this->equalTo($account), $this->equalTo($client))
			->willReturn($folders);
		$this->folderMapper->expects($this->once())
			->method('getFoldersStatus')
			->with($this->equalTo($folders));
		$this->folderMapper->expects($this->once())
			->method('detectFolderSpecialUse')
			->with($this->equalTo($folders));
		$this->folderMapper->expects($this->once())
			->method('sortFolders')
			->with($this->equalTo($folders));
		$this->translator->expects($this->once())
			->method('translateAll')
			->with($this->equalTo($folders), $this->equalTo(false));
		$this->folderMapper->expects($this->once())
			->method('buildFolderHierarchy')
			->with($this->equalTo($folders))
			->willReturn($folders);

		$this->manager->getFolders($account);
	}

	public function testSync() {
		$account = $this->createMock(Account::class);
		$syncRequest = $this->createMock(Request::class);
		$syncResonse = $this->createMock(Response::class);
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$this->imapClientFactory->expects($this->once())
			->method('getClient')
			->willReturn($client);
		$this->sync->expects($this->once())
			->method('sync')
			->with($client, $syncRequest)
			->willReturn($syncResonse);

		$this->manager->syncMessages($account, $syncRequest);
	}

}
