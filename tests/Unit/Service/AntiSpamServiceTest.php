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
use OCA\Mail\Events\MessageFlaggedEvent;
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

	public function testSendReportEmailNoEmailFound(): void {
		$event = $this->createConfiguredMock(MessageFlaggedEvent::class, [
			'getAccount' => $this->createMock(Account::class),
			'getMailbox' => $this->createMock(Mailbox::class),
			'getFlag' => '$junk'
		]);

		$this->config->expects(self::once())
			->method('getAppValue')
			->with('mail', 'antispam_reporting_spam')
			->willReturn('');
		$this->messageMapper->expects(self::never())
			->method('getIdForUid');
		$this->transmission->expects(self::never())
			->method('sendMessage');

		$this->service->sendReportEmail($event->getAccount(), $event->getMailbox(), 123, $event->getFlag());
	}

	public function testSendReportEmailNoMessageFound(): void {
		$event = $this->createConfiguredMock(MessageFlaggedEvent::class, [
			'getAccount' => $this->createMock(Account::class),
			'getMailbox' => $this->createMock(Mailbox::class),
			'getFlag' => '$junk'
		]);

		$this->config->expects(self::once())
			->method('getAppValue')
			->with('mail', 'antispam_reporting_spam')
			->willReturn('test@test.com');
		$this->messageMapper->expects(self::once())
			->method('getIdForUid')
			->with($event->getMailbox(), 123)
			->willReturn(null);
		$this->expectException(ServiceException::class);
		$this->transmission->expects(self::never())
			->method('sendMessage');

		$this->service->sendReportEmail($event->getAccount(), $event->getMailbox(), 123, $event->getFlag());
	}

	public function testSendReportEmailTransmissionError(): void {
		$event = $this->createConfiguredMock(MessageFlaggedEvent::class, [
			'getAccount' => $this->createMock(Account::class),
			'getMailbox' => $this->createMock(Mailbox::class),
			'getFlag' => '$junk'
		]);

		$this->config->expects(self::once())
			->method('getAppValue')
			->with('mail', 'antispam_reporting_spam')
			->willReturn('test@test.com');
		$messageData = NewMessageData::fromRequest(
			$event->getAccount(),
			'test@test.com',
			null,
			null,
			'Learn as Junk',
			'Learn as Junk',
			[['id' => 123, 'type' => 'message/rfc822']]
		);

		$this->messageMapper->expects(self::once())
			->method('getIdForUid')
			->with($event->getMailbox(), 123)
			->willReturn(123);
		$this->transmission->expects(self::once())
			->method('sendMessage')
			->with($messageData)
			->willThrowException(new ServiceException());
		$this->expectException(ServiceException::class);

		$this->service->sendReportEmail($event->getAccount(), $event->getMailbox(), 123, $event->getFlag());
	}

	public function testSendReportEmail(): void {
		$event = $this->createConfiguredMock(MessageFlaggedEvent::class, [
			'getAccount' => $this->createMock(Account::class),
			'getMailbox' => $this->createMock(Mailbox::class),
			'getFlag' => '$junk'
		]);

		$this->config->expects(self::once())
			->method('getAppValue')
			->with('mail', 'antispam_reporting_spam')
			->willReturn('test@test.com');
		$messageData = NewMessageData::fromRequest(
			$event->getAccount(),
			'test@test.com',
			null,
			null,
			'Learn as Junk',
			'Learn as Junk',
			[['id' => 123, 'type' => 'message/rfc822']]
		);

		$this->messageMapper->expects(self::once())
			->method('getIdForUid')
			->with($event->getMailbox(), 123)
			->willReturn(123);
		$this->transmission->expects(self::once())
			->method('sendMessage')
			->with($messageData);

		$this->service->sendReportEmail($event->getAccount(), $event->getMailbox(), 123, $event->getFlag());
	}

	public function testSendReportEmailForHam(): void {
		$event = $this->createConfiguredMock(MessageFlaggedEvent::class, [
			'getAccount' => $this->createMock(Account::class),
			'getMailbox' => $this->createMock(Mailbox::class),
			'getFlag' => '$notjunk'
		]);

		$this->config->expects(self::once())
			->method('getAppValue')
			->with('mail', 'antispam_reporting_ham')
			->willReturn('test@test.com');
		$messageData = NewMessageData::fromRequest(
			$event->getAccount(),
			'test@test.com',
			null,
			null,
			'Learn as Not Junk',
			'Learn as Not Junk',
			[['id' => 123, 'type' => 'message/rfc822']]
		);

		$this->messageMapper->expects(self::once())
			->method('getIdForUid')
			->with($event->getMailbox(), 123)
			->willReturn(123);
		$this->transmission->expects(self::once())
			->method('sendMessage')
			->with($messageData);

		$this->service->sendReportEmail($event->getAccount(), $event->getMailbox(), 123, $event->getFlag());
	}
}
