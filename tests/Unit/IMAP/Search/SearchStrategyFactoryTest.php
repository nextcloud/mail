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

namespace OCA\Mail\Tests\Unit\IMAP\Search;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client_Data_Capability;
use Horde_Imap_Client_Search_Query;
use Horde_Imap_Client_Socket;
use OCA\Mail\IMAP\Search\FullScanSearchStrategy;
use OCA\Mail\IMAP\Search\ImapSortSearchStrategy;
use OCA\Mail\IMAP\Search\SearchStrategyFactory;
use PHPUnit\Framework\MockObject\MockObject;

class SearchStrategyFactoryTest extends TestCase {

	/** @var SearchStrategyFactory */
	private $factory;

	protected function setUp() {
		parent::setUp();

		$this->factory = new SearchStrategyFactory();
	}

	public function testGetStrategyForFetchNoFilter() {
		/** @var MockObject|Horde_Imap_Client_Socket $client */
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$mailbox = 'INBOX';
		$filter = new Horde_Imap_Client_Search_Query();
		$cursor = null;
		$capability = $this->createMock(Horde_Imap_Client_Data_Capability::class);
		$client->expects($this->once())
			->method('__get')
			->with('capability')
			->willReturn($capability);
		$capability->expects($this->once())
			->method('query')
			->with('SORT')
			->willReturn(true);

		$strategy = $this->factory->getStrategy(
			$client,
			$mailbox,
			$filter,
			$cursor
		);

		$this->assertInstanceOf(ImapSortSearchStrategy::class, $strategy);
	}

	public function testGetStrategyForFetchNoSort() {
		/** @var MockObject|Horde_Imap_Client_Socket $client */
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$mailbox = 'INBOX';
		$filter = new Horde_Imap_Client_Search_Query();
		$cursor = null;
		$capability = $this->createMock(Horde_Imap_Client_Data_Capability::class);
		$client->expects($this->once())
			->method('__get')
			->with('capability')
			->willReturn($capability);
		$capability->expects($this->once())
			->method('query')
			->with('SORT')
			->willReturn(false);

		$strategy = $this->factory->getStrategy(
			$client,
			$mailbox,
			$filter,
			$cursor
		);

		$this->assertInstanceOf(FullScanSearchStrategy::class, $strategy);
	}

	public function testGetStrategyForSearchWithSort() {
		/** @var MockObject|Horde_Imap_Client_Socket $client */
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$mailbox = 'INBOX';
		$filter = new Horde_Imap_Client_Search_Query();
		$filter->text('test');
		$cursor = null;
		$capability = $this->createMock(Horde_Imap_Client_Data_Capability::class);
		$client->expects($this->once())
			->method('__get')
			->with('capability')
			->willReturn($capability);
		$capability->expects($this->once())
			->method('query')
			->with('SORT')
			->willReturn(true);

		$strategy = $this->factory->getStrategy(
			$client,
			$mailbox,
			$filter,
			$cursor
		);

		$this->assertInstanceOf(ImapSortSearchStrategy::class, $strategy);
	}

	public function testGetStrategyForSearchWithoutSort() {
		/** @var MockObject|Horde_Imap_Client_Socket $client */
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$mailbox = 'INBOX';
		$filter = new Horde_Imap_Client_Search_Query();
		$filter->text('sort');
		$cursor = null;
		$capability = $this->createMock(Horde_Imap_Client_Data_Capability::class);
		$client->expects($this->once())
			->method('__get')
			->with('capability')
			->willReturn($capability);
		$capability->expects($this->once())
			->method('query')
			->with('SORT')
			->willReturn(true);

		$strategy = $this->factory->getStrategy(
			$client,
			$mailbox,
			$filter,
			$cursor
		);

		$this->assertInstanceOf(ImapSortSearchStrategy::class, $strategy);
	}

}
