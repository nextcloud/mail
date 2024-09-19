<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Controller;

use ChristophWurst\Nextcloud\Testing\ServiceMockObject;
use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client_DateTime;
use OCA\Mail\Account;
use OCA\Mail\AddressList;
use OCA\Mail\Controller\ListController;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\Message;
use OCA\Mail\Model\IMAPMessage;
use OCA\Mail\Service\Html;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\Http\Client\IClient;

class ListControllerTest extends TestCase {
	private ServiceMockObject $serviceMock;
	private ListController $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->serviceMock = $this->createServiceMock(ListController::class, [
			'userId' => 'user123',
		]);
		$this->controller = $this->serviceMock->getService();
	}

	public function testMessageNotFound(): void {
		$this->serviceMock->getParameter('mailManager')
			->expects(self::once())
			->method('getMessage')
			->willThrowException(new DoesNotExistException(''));

		$response = $this->controller->unsubscribe(123);

		self::assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus());
	}

	public function testMailboxNotFound(): void {
		$message = new Message();
		$message->setMailboxId(321);
		$this->serviceMock->getParameter('mailManager')
			->expects(self::once())
			->method('getMessage')
			->willReturn($message);
		$this->serviceMock->getParameter('mailManager')
			->expects(self::once())
			->method('getMailbox')
			->with('user123', 321)
			->willThrowException(new DoesNotExistException(''));

		$response = $this->controller->unsubscribe(123);

		self::assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus());
	}

	public function testAccountNotFound(): void {
		$message = new Message();
		$message->setMailboxId(321);
		$this->serviceMock->getParameter('mailManager')
			->expects(self::once())
			->method('getMessage')
			->willReturn($message);
		$mailbox = new Mailbox();
		$mailbox->setAccountId(567);
		$this->serviceMock->getParameter('mailManager')
			->expects(self::once())
			->method('getMailbox')
			->with('user123', 321)
			->willReturn($mailbox);
		$this->serviceMock->getParameter('accountService')
			->expects(self::once())
			->method('find')
			->with('user123', 567)
			->willThrowException(new DoesNotExistException(''));

		$response = $this->controller->unsubscribe(123);

		self::assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus());
	}

	public function testUnsupportedMessage(): void {
		$message = new Message();
		$message->setUid(987);
		$message->setMailboxId(321);
		$this->serviceMock->getParameter('mailManager')
			->expects(self::once())
			->method('getMessage')
			->willReturn($message);
		$mailbox = new Mailbox();
		$mailbox->setAccountId(567);
		$this->serviceMock->getParameter('mailManager')
			->expects(self::once())
			->method('getMailbox')
			->with('user123', 321)
			->willReturn($mailbox);
		$mailAccount = new MailAccount([]);
		$account = new Account($mailAccount);
		$this->serviceMock->getParameter('accountService')
			->expects(self::once())
			->method('find')
			->with('user123', 567)
			->willReturn($account);
		$imapMessage = new IMAPMessage(
			123,
			'',
			[],
			new AddressList([]),
			new AddressList([]),
			new AddressList([]),
			new AddressList([]),
			new AddressList([]),
			'',
			'',
			'',
			false,
			[],
			[],
			false,
			[],
			new Horde_Imap_Client_DateTime(),
			'',
			'',
			false,
			[],
			null,
			false,
			'',
			'',
			false,
			false,
			false,
			$this->createMock(Html::class),
			false,
		);
		$this->serviceMock->getParameter('mailManager')
			->expects(self::once())
			->method('getImapMessage')
			->willReturn($imapMessage);

		$response = $this->controller->unsubscribe(123);

		self::assertEquals(Http::STATUS_FORBIDDEN, $response->getStatus());
	}

	public function testUnsubscribe(): void {
		$message = new Message();
		$message->setUid(987);
		$message->setMailboxId(321);
		$this->serviceMock->getParameter('mailManager')
			->expects(self::once())
			->method('getMessage')
			->willReturn($message);
		$mailbox = new Mailbox();
		$mailbox->setAccountId(567);
		$this->serviceMock->getParameter('mailManager')
			->expects(self::once())
			->method('getMailbox')
			->with('user123', 321)
			->willReturn($mailbox);
		$mailAccount = new MailAccount([]);
		$account = new Account($mailAccount);
		$this->serviceMock->getParameter('accountService')
			->expects(self::once())
			->method('find')
			->with('user123', 567)
			->willReturn($account);
		$imapMessage = new IMAPMessage(
			123,
			'',
			[],
			new AddressList([]),
			new AddressList([]),
			new AddressList([]),
			new AddressList([]),
			new AddressList([]),
			'',
			'',
			'',
			false,
			[],
			[],
			false,
			[],
			new Horde_Imap_Client_DateTime(),
			'',
			'',
			false,
			[],
			'https://un.sub.scribe/me',
			true,
			'',
			'',
			false,
			false,
			false,
			$this->createMock(Html::class),
			false,
		);
		$this->serviceMock->getParameter('mailManager')
			->expects(self::once())
			->method('getImapMessage')
			->willReturn($imapMessage);
		$httpClient = $this->createMock(IClient::class);
		$this->serviceMock->getParameter('httpClientService')
			->expects(self::once())
			->method('newClient')
			->willReturn($httpClient);
		$httpClient->expects(self::once())
			->method('post')
			->with('https://un.sub.scribe/me');

		$response = $this->controller->unsubscribe(123);

		self::assertEquals(Http::STATUS_OK, $response->getStatus());
	}
}
