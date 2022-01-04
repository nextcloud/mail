<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

namespace OCA\Mail\Tests\Unit\Controller;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Controller\OutboxController;
use OCA\Mail\Db\LocalMailboxMessage;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\OutboxService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @property
 */
class OutboxControllerTest extends TestCase {

	/** @var OutboxService|MockObject */
	private $outboxService;

	/** @var string */
	private $userId;

	/** @var AccountService|MockObject */
	private $accountService;

	/** @var ITimeFactory|MockObject */
	private $time;

	protected function setUp(): void {
		parent::setUp();

		$appName = 'mail';
		$request = $this->createMock(IRequest::class);
		$this->outboxService = $this->createMock(OutboxService::class);
		$this->userId = 'manfred';
		$this->accountService = $this->createMock(AccountService::class);
		$this->time = $this->createMock(ITimeFactory::class);

		$this->controller = new OutboxController(
			$appName,
			$this->userId,
			$request,
			$this->outboxService,
			$this->accountService
		);
		$this->account = $this->createMock(Account::class);
		$this->accountId = 123;
	}

	public function testIndex(): void {
		$this->outboxService->expects($this->once())
			->method('getMessages')
			->with($this->userId)
			->willReturn([
				[
					'id' => 1,
					'type' => 0,
					'account_id' => 1,
					'send_at' => $this->time->getTime(),
					'subject' => 'Test',
					'body' => 'Test',
					'html' => false,
					'mdn' => false,
					'reply_to_message_id' => null
				],
				[
					'id' => 2,
					'type' => 0,
					'account_id' => 1,
					'send_at' => $this->time->getTime(),
					'subject' => 'Second Test',
					'body' => 'Second Test',
					'html' => true,
					'mdn' => false,
					'reply_to_message_id' => null
				]
			]);

		$response = $this->controller->index();
		$expectedResponse = new JSONResponse([
			'messages' => [
				[
					'id' => 1,
					'type' => 0,
					'account_id' => 1,
					'send_at' => $this->time->getTime(),
					'subject' => 'Test',
					'body' => 'Test',
					'html' => false,
					'mdn' => false,
					'reply_to_message_id' => null
				],
				[
					'id' => 2,
					'type' => 0,
					'account_id' => 1,
					'send_at' => $this->time->getTime(),
					'subject' => 'Second Test',
					'body' => 'Second Test',
					'html' => true,
					'mdn' => false,
					'reply_to_message_id' => null
				]
			]
		]);
		$this->assertEquals($expectedResponse, $response);
	}


	public function testGet(): void {
		$message = new LocalMailboxMessage();
		$message->setAccountId(1);

		$this->outboxService->expects($this->once())
			->method('getMessage')
			->with(1)
			->willReturn($message);
		$this->accountService->expects($this->once())
			->method('find')
			->with($this->userId, $message->getAccountId());

		$response = $this->controller->get(1);

		$expectedResponse = new JSONResponse($message);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testGetNotFound(): void {
		$message = new LocalMailboxMessage();
		$message->setAccountId(1);

		$this->outboxService->expects($this->once())
				->method('getMessage')
				->with(1)
				->willThrowException(new ServiceException('Could not fetch any messages', 400));
		$this->accountService->expects($this->never())
			->method('find');

		$response = $this->controller->get(1);
		$expectedResponse = new JSONResponse('Could not fetch any messages', 400);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testGetNoAccess(): void {
		$message = new LocalMailboxMessage();
		$message->setAccountId(1);

		$this->outboxService->expects($this->once())
			->method('getMessage')
			->with(1)
			->willReturn($message);
		$this->accountService->expects($this->once())
			->method('find')
			->with($this->userId, $message->getAccountId())
			->willThrowException(new ClientException());

		$this->controller->get(1);
	}

	public function testSave(): void {
		$message = new LocalMailboxMessage();
		$message->setAccountId(1);
		$message->setSendAt($this->time->getTime());
		$message->setSubject('Test');
		$message->setBody('Test Test Test');
		$message->setHtml(true);
		$message->setMdn(false);
		$message->setInReplyToMessageId('abc@cde.com');

		$this->accountService->expects($this->once())
			->method('find')
			->with($this->userId, $message->getAccountId());
		$this->outboxService->expects($this->once())
			->method('saveMessage')
			->with($message, [], []);

		$expectedResponse = new JSONResponse($message, Http::STATUS_CREATED);
		$response = $this->controller->save(1, $this->time->getTime(), 'Test', 'Test Test Test', true, false, 'abc@cde.com', [], []);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testSaveNoAccess(): void {
		$message = new LocalMailboxMessage();
		$message->setAccountId(1);

		$this->accountService->expects($this->once())
			->method('find')
			->with($this->userId, $message->getAccountId())
			->willThrowException(new ClientException());
		$this->outboxService->expects($this->never())
			->method('saveMessage');

		$this->controller->save(1, $this->time->getTime(), 'Test', 'Test Test Test', true, false, 'abc@cde.com', [], []);
	}

	public function testCantSave(): void {
		$message = new LocalMailboxMessage();
		$message->setAccountId(1);
		$message->setSendAt($this->time->getTime());
		$message->setSubject('Test');
		$message->setBody('Test Test Test');
		$message->setHtml(true);
		$message->setMdn(false);
		$message->setInReplyToMessageId('abc@cde.com');

		$this->accountService->expects($this->once())
			->method('find')
			->with($this->userId, $message->getAccountId());
		$this->outboxService->expects($this->once())
			->method('saveMessage')
			->with($message, [], [])
			->willThrowException(new ServiceException());

		$this->controller->save(1, $this->time->getTime(), 'Test', 'Test Test Test', true, false, 'abc@cde.com', [], []);
	}

	public function testSend(): void {
		$message = new LocalMailboxMessage();
		$message->setAccountId(1);
		$account = $this->createMock(Account::class);

		$this->outboxService->expects($this->once())
			->method('getMessage')
			->with(1)
			->willReturn($message);
		$this->accountService->expects($this->once())
			->method('find')
			->with($this->userId, $message->getAccountId())
			->willReturn($account);
		$this->outboxService->expects($this->once())
			->method('sendMessage')
			->with($message, $account);

		$this->controller->send(1);
	}

	public function testSendMessageNotFound(): void {
		$message = new LocalMailboxMessage();
		$message->setAccountId(1);

		$this->outboxService->expects($this->once())
			->method('getMessage')
			->with(1)
			->willThrowException(new ServiceException());
		$this->accountService->expects($this->never())
			->method('find');
		$this->outboxService->expects($this->never())
			->method('sendMessage');

		$this->controller->send(1);
	}

	public function testSendNoAccess(): void {
		$message = new LocalMailboxMessage();
		$message->setAccountId(1);

		$this->outboxService->expects($this->once())
			->method('getMessage')
			->with(1)
			->willReturn($message);
		$this->accountService->expects($this->once())
			->method('find')
			->with($this->userId, $message->getAccountId())
			->willThrowException(new ClientException());
		$this->outboxService->expects($this->never())
			->method('sendMessage');

		$this->controller->send(1);
	}
}
