<?php

/**
 * @copyright 2021 Anna Larch <anna@nextcloud.com>
 *
 * @author Anna Larch <anna@nextcloud.com>
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

namespace OCA\Mail\Tests\Unit\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailTransmission;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Model\NewMessageData;
use OCA\Mail\Service\AntiSpamService;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;

class AntiSpamServiceTest extends TestCase {

	/** @var AntiSpamService */
	private $service;

	/** @var IConfig|MockObject */
	private $config;

	/** @var MessageMapper|MockObject */
	private $messageMapper;

	/** @var IMailTransmission|MockObject */
	private $transmission;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->messageMapper = $this->createMock(MessageMapper::class);
		$this->transmission = $this->createMock(IMailTransmission::class);

		$this->service = new AntiSpamService(
			$this->config,
			$this->messageMapper,
			$this->transmission
		);
	}

	public function testSendReportEmailNoMessageFound(): void {
		$account = $this->createMock(Account::class);
		$mailbox = $this->createMock(Mailbox::class);

		$this->messageMapper->expects($this->once())
			->method('getIdForUid')
			->with($mailbox, 123)
			->willReturn(null);
		$this->expectException(ServiceException::class);
		$this->transmission->expects($this->never())
			->method('sendMessage');

		$this->service->sendReportEmail($account, $mailbox, 123, 'test@test.com', 'Learn as Junk');
	}

	public function testsendReportEmailTransmissionError(): void {
		$account = $this->createMock(Account::class);
		$mailbox = $this->createMock(Mailbox::class);
		$messageData = NewMessageData::fromRequest(
			$account,
			'test@test.com',
			null,
			null,
			'Learn as Junk',
			'Learn as Junk',
			[['id' => 123, 'type' => 'message/rfc822']]
		);

		$this->messageMapper->expects($this->once())
			->method('getIdForUid')
			->with($mailbox, 123)
			->willReturn(123);
		$this->transmission->expects($this->once())
			->method('sendMessage')
			->with($messageData)
			->willThrowException(new ServiceException());
		$this->expectException(ServiceException::class);

		$this->service->sendReportEmail($account, $mailbox, 123, 'test@test.com', 'Learn as Junk');
	}

	public function testSendReportEmail(): void {
		$account = $this->createMock(Account::class);
		$mailbox = $this->createMock(Mailbox::class);
		$messageData = NewMessageData::fromRequest(
			$account,
			'test@test.com',
			null,
			null,
			'Learn as Junk',
			'Learn as Junk',
			[['id' => 123, 'type' => 'message/rfc822']]
		);

		$this->messageMapper->expects($this->once())
			->method('getIdForUid')
			->with($mailbox, 123)
			->willReturn(123);
		$this->transmission->expects($this->once())
			->method('sendMessage')
			->with($messageData);

		$this->service->sendReportEmail($account, $mailbox, 123, 'test@test.com', 'Learn as Junk');
	}

	public function testSendReportEmailForHam(): void {
		$account = $this->createMock(Account::class);
		$mailbox = $this->createMock(Mailbox::class);
		$messageData = NewMessageData::fromRequest(
			$account,
			'test@test.com',
			null,
			null,
			'Learn as Not Junk',
			'Learn as Not Junk',
			[['id' => 123, 'type' => 'message/rfc822']]
		);

		$this->messageMapper->expects($this->once())
			->method('getIdForUid')
			->with($mailbox, 123)
			->willReturn(123);
		$this->transmission->expects($this->once())
			->method('sendMessage')
			->with($messageData);

		$this->service->sendReportEmail($account, $mailbox, 123, 'test@test.com', 'Learn as Not Junk');
	}
}
