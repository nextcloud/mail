<?php

declare(strict_types=1);

/*
 * @copyright 2021 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2021 Christoph Wurst <christoph@winzerhof-wurst.at>
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

namespace OCA\Mail\Tests\Unit\Service;

use ChristophWurst\Nextcloud\Testing\ServiceMockObject;
use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Address;
use OCA\Mail\AddressList;
use OCA\Mail\Model\NewMessageData;
use OCA\Mail\Service\AntiAbuseService;
use OCP\IMemcache;
use OCP\IUser;
use function array_map;
use function range;

class AntiAbuseServiceTest extends TestCase {
	/** @var AntiAbuseService */
	private $service;

	/** @var ServiceMockObject */
	private $serviceMock;

	protected function setUp(): void {
		parent::setUp();

		$this->serviceMock = $this->createServiceMock(AntiAbuseService::class);
		$this->service = $this->serviceMock->getService();
	}

	public function testThresholdDisabled(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user123');
		$account = $this->createMock(Account::class);
		$messageData = new NewMessageData(
			$account,
			new AddressList([]),
			new AddressList([]),
			new AddressList([]),
			'subject',
			'henlo',
		);
		$this->serviceMock->getParameter('config')
			->expects(self::once())
			->method('getAppValue')
			->withConsecutive(
				['mail', 'abuse_detection', 'off'],
			)->willReturnOnConsecutiveCalls(
				'off',
			);
		$this->serviceMock->getParameter('logger')
			->expects(self::never())
			->method('alert');

		$this->service->onBeforeMessageSent(
			$user,
			$messageData,
		);
	}

	public function testThresholdReached(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user123');
		$account = $this->createMock(Account::class);
		$messageData = new NewMessageData(
			$account,
			new AddressList(array_map(static function (int $i) {
				return Address::fromRaw(
					"user$i@domain.tld",
					"user$i@domain.tld",
				);
			}, range(1, 50))),
			new AddressList(array_map(static function (int $i) {
				return Address::fromRaw(
					"user$i@domain.tld",
					"user$i@domain.tld",
				);
			}, range(51, 60))),
			new AddressList(array_map(static function (int $i) {
				return Address::fromRaw(
					"user$i@domain.tld",
					"user$i@domain.tld",
				);
			}, range(51, 70))),
			'subject',
			'henlo',
		);
		$this->serviceMock->getParameter('config')
			->method('getAppValue')
			->withConsecutive(
				['mail', 'abuse_detection', 'off'],
				['mail', 'abuse_number_of_recipients_per_message_threshold', '0'],
			)->willReturnOnConsecutiveCalls(
				'on',
				'50',
			);
		$this->serviceMock->getParameter('logger')
			->expects(self::once())
			->method('alert')
			->with(self::anything(), [
				'user' => 'user123',
				'expected' => 50,
				'actual' => 80,
			]);

		$this->service->onBeforeMessageSent(
			$user,
			$messageData,
		);
	}

	public function test15mThreshold(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user123');
		$account = $this->createMock(Account::class);
		$messageData = new NewMessageData(
			$account,
			new AddressList([
				Address::fromRaw(
					"user@domain.tld",
					"user@domain.tld",
				)
			]),
			new AddressList([]),
			new AddressList([]),
			'subject',
			'henlo',
		);
		$this->serviceMock->getParameter('config')
			->method('getAppValue')
			->withConsecutive(
				['mail', 'abuse_detection', 'off'],
				['mail', 'abuse_number_of_recipients_per_message_threshold', '0'],
				['mail', 'abuse_number_of_messages_per_15m', '0']
			)->willReturnOnConsecutiveCalls(
				'on',
				'0',
				'5',
			);
		$this->serviceMock->getParameter('cacheFactory')
			->expects(self::once())
			->method('isAvailable')
			->willReturn(true);
		$cache = $this->createMock(IMemcache::class);
		$this->serviceMock->getParameter('cacheFactory')
			->expects(self::once())
			->method('createDistributed')
			->willReturn($cache);
		$this->serviceMock->getParameter('timeFactory')
			->expects(self::once())
			->method('getTime')
			->willReturn(123456);
		$cache->expects(self::once())
			->method('add')
			->with('counter_15m_123300', 0);
		$cache->expects(self::once())
			->method('inc')
			->with('counter_15m_123300')
			->willReturn(5);
		$this->serviceMock->getParameter('logger')
			->expects(self::once())
			->method('alert')
			->with(self::anything(), [
				'user' => 'user123',
				'period' => '15m',
				'expected' => 5,
				'actual' => 5,
			]);

		$this->service->onBeforeMessageSent(
			$user,
			$messageData,
		);
	}
}
