<?php

declare(strict_types=1);

/**
 * @author Anna Larch <anna.larch@gmx.net>
 *
 * @copyright 2022 Anna Larch <anna.larch@gmx.net>
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
use OC\AppFramework\Http;
use OCA\Mail\Account;
use OCA\Mail\Controller\DraftsController;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Http\JsonResponse;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\DraftsService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\Exception;
use OCP\IRequest;

class DraftsControllerTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();

		$this->appName = 'mail';
		$this->service = $this->createMock(DraftsService::class);
		$this->userId = 'john';
		$this->request = $this->createMock(IRequest::class);
		$this->accountService = $this->createMock(AccountService::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);

		$this->controller = new DraftsController(
			$this->appName,
			$this->userId,
			$this->request,
			$this->service,
			$this->accountService,
			$this->timeFactory
		);
	}

	public function testMove(): void {
		$message = new LocalMessage();
		$message->setId(1);
		$message->setAccountId(1);
		$account = new Account(new MailAccount());

		$this->service->expects(self::once())
			->method('getMessage')
			->with($message->getId(), $this->userId)
			->willReturn($message);
		$this->accountService->expects(self::once())
			->method('find')
			->with($this->userId, $message->getAccountId())
			->willReturn($account);
		$this->service->expects(self::once())
			->method('sendMessage')
			->with($message, $account);

		$expected = JsonResponse::success('Message moved to IMAP', Http::STATUS_ACCEPTED);
		$actual = $this->controller->move($message->getId());

		$this->assertEquals($expected, $actual);
	}

	public function testMoveNoMessage(): void {
		$message = new LocalMessage();
		$message->setId(1);
		$message->setAccountId(1);

		$this->service->expects(self::once())
			->method('getMessage')
			->with($message->getId(), $this->userId)
			->willThrowException(new DoesNotExistException(''));
		$this->accountService->expects(self::never())
			->method('find');
		$this->service->expects(self::never())
			->method('sendMessage');

		$this->expectException(DoesNotExistException::class);
		$expected = JsonResponse::fail('', Http::STATUS_NOT_FOUND);
		$actual = $this->controller->move($message->getId());

		$this->assertEquals($expected, $actual);
	}

	public function testSendClientException(): void {
		$message = new LocalMessage();
		$message->setId(1);
		$message->setAccountId(1);

		$this->service->expects(self::once())
			->method('getMessage')
			->with($message->getId(), $this->userId)
			->willReturn($message);
		$this->accountService->expects(self::once())
			->method('find')
			->with($this->userId, $message->getAccountId())
			->willThrowException(new ClientException());
		$this->service->expects(self::never())
			->method('sendMessage');

		$this->expectException(ClientException::class);
		$this->controller->move($message->getId());
	}

	public function testSendServiceException(): void {
		$message = new LocalMessage();
		$message->setId(1);
		$message->setAccountId(1);
		$account = new Account(new MailAccount());

		$this->service->expects(self::once())
			->method('getMessage')
			->with($message->getId(), $this->userId)
			->willReturn($message);
		$this->accountService->expects(self::once())
			->method('find')
			->with($this->userId, $message->getAccountId())
			->willReturn($account);
		$this->service->expects(self::once())
			->method('sendMessage')
			->willThrowException(new ServiceException());

		$this->expectException(ServiceException::class);
		$this->controller->move($message->getId());
	}

	public function testDestroy(): void {
		$message = new LocalMessage();
		$message->setId(1);
		$message->setAccountId(1);
		$account = new Account(new MailAccount());

		$this->service->expects(self::once())
			->method('getMessage')
			->with($message->getId(), $this->userId)
			->willReturn($message);
		$this->accountService->expects(self::once())
			->method('find')
			->willReturn($account);
		$this->service->expects(self::once())
			->method('deleteMessage')
			->with($this->userId, $message);

		$expected = JsonResponse::success('Message deleted', Http::STATUS_ACCEPTED);
		$actual = $this->controller->destroy($message->getId());

		$this->assertEquals($expected, $actual);
	}

	public function testDestroyNoMessage(): void {
		$message = new LocalMessage();
		$message->setId(1);
		$message->setAccountId(1);

		$this->service->expects(self::once())
			->method('getMessage')
			->with($message->getId(), $this->userId)
			->willThrowException(new DoesNotExistException(''));
		$this->accountService->expects(self::never())
			->method('find');
		$this->service->expects(self::never())
			->method('deleteMessage');

		$this->expectException(DoesNotExistException::class);
		$expected = JsonResponse::fail('', Http::STATUS_NOT_FOUND);
		$actual = $this->controller->destroy($message->getId());

		$this->assertEquals($expected, $actual);
	}

	public function testCreate(): void {
		$message = new LocalMessage();
		$message->setAccountId(1);
		$message->setAliasId(2);
		$message->setSubject('subject');
		$message->setBody('message');
		$message->setEditorBody('<p>message</p>');
		$message->setHtml(true);
		$message->setInReplyToMessageId('abc');
		$message->setType(LocalMessage::TYPE_DRAFT);
		$message->setSendAt(null);
		$message->setUpdatedAt(123456);
		$to = [['label' => 'Lewis', 'email' => 'tent@stardewvalley.com']];
		$cc = [['label' => 'Pierre', 'email' => 'generalstore@stardewvalley.com']];

		$account = new Account(new MailAccount());
		$this->accountService->expects(self::once())
			->method('find')
			->with($this->userId, $message->getAccountId())
			->willReturn($account);
		$this->timeFactory->expects(self::once())
			->method('getTime')
			->willReturn(123456);
		$this->service->expects(self::once())
			->method('saveMessage')
			->with($account, $message, $to, $cc, [], []);

		$expected = JsonResponse::success($message, Http::STATUS_CREATED);
		$actual = $this->controller->create(
			$message->getAccountId(),
			$message->getSubject(),
			$message->getBody(),
			'<p>message</p>',
			$message->isHtml(),
			$to,
			$cc,
			[],
			[],
			$message->getAliasId(),
			$message->getInReplyToMessageId()
		);

		$this->assertEquals($expected, $actual);
	}

	public function testCreateFromDraft(): void {
		$message = new LocalMessage();
		$message->setAccountId(1);
		$message->setAliasId(2);
		$message->setSubject('subject');
		$message->setBody('message');
		$message->setEditorBody('<p>message</p>');
		$message->setHtml(true);
		$message->setInReplyToMessageId('abc');
		$message->setType(LocalMessage::TYPE_DRAFT);
		$message->setSendAt(null);
		$message->setUpdatedAt(123456);
		$to = [['label' => 'Lewis', 'email' => 'tent@stardewvalley.com']];
		$cc = [['label' => 'Pierre', 'email' => 'generalstore@stardewvalley.com']];

		$account = new Account(new MailAccount());
		$this->accountService->expects(self::once())
			->method('find')
			->with($this->userId, $message->getAccountId())
			->willReturn($account);
		$this->service->expects(self::once())
			->method('handleDraft');
		$this->timeFactory->expects(self::once())
			->method('getTime')
			->willReturn(123456);
		$this->service->expects(self::once())
			->method('saveMessage')
			->with($account, $message, $to, $cc, [], []);

		$expected = JsonResponse::success($message, Http::STATUS_CREATED);
		$actual = $this->controller->create(
			$message->getAccountId(),
			$message->getSubject(),
			$message->getBody(),
			'<p>message</p>',
			$message->isHtml(),
			$to,
			$cc,
			[],
			[],
			$message->getAliasId(),
			$message->getInReplyToMessageId(),
			null,
			1
		);

		$this->assertEquals($expected, $actual);
	}

	public function testCreateWithEmptyRecipients(): void {
		$message = new LocalMessage();
		$message->setAccountId(1);
		$message->setAliasId(2);
		$message->setSubject('subject');
		$message->setBody('message');
		$message->setEditorBody('<p>message</p>');
		$message->setHtml(true);
		$message->setInReplyToMessageId('abc');
		$message->setType(LocalMessage::TYPE_DRAFT);
		$message->setSendAt(null);
		$message->setUpdatedAt(123456);

		$account = new Account(new MailAccount());
		$this->accountService->expects(self::once())
			->method('find')
			->with($this->userId, $message->getAccountId())
			->willReturn($account);
		$this->timeFactory->expects(self::once())
			->method('getTime')
			->willReturn(123456);
		$this->service->expects(self::once())
			->method('saveMessage')
			->with($account, $message, [], [], [], []);

		$expected = JsonResponse::success($message, Http::STATUS_CREATED);
		$actual = $this->controller->create(
			$message->getAccountId(),
			$message->getSubject(),
			$message->getBody(),
			'<p>message</p>',
			$message->isHtml(),
			[],
			[],
			[],
			[],
			$message->getAliasId(),
			$message->getInReplyToMessageId()
		);

		$this->assertEquals($expected, $actual);
	}

	public function testCreateAccountNotFound(): void {
		$message = new LocalMessage();
		$message->setAccountId(1);
		$message->setAliasId(2);
		$message->setSubject('subject');
		$message->setBody('message');
		$message->setEditorBody('<p>message</p>');
		$message->setHtml(true);
		$message->setInReplyToMessageId('abc');
		$message->setType(LocalMessage::TYPE_OUTGOING);
		$message->setSendAt(null);
		$to = [['label' => 'Lewis', 'email' => 'tent@stardewvalley.com']];
		$cc = [['label' => 'Pierre', 'email' => 'generalstore@stardewvalley.com']];

		$this->accountService->expects(self::once())
			->method('find')
			->with($this->userId, $message->getAccountId())
			->willThrowException(new ClientException());
		$this->service->expects(self::never())
			->method('saveMessage');

		$this->expectException(ClientException::class);
		$actual = $this->controller->create(
			$message->getAccountId(),
			$message->getSubject(),
			$message->getBody(),
			'<p>message</p>',
			$message->isHtml(),
			$to,
			$cc,
			[],
			[],
			$message->getAliasId(),
			$message->getInReplyToMessageId()
		);
	}

	public function testCreateDbException(): void {
		$message = new LocalMessage();
		$message->setAccountId(1);
		$message->setAliasId(2);
		$message->setSubject('subject');
		$message->setBody('message');
		$message->setEditorBody('<p>message</p>');
		$message->setHtml(true);
		$message->setInReplyToMessageId('abc');
		$message->setType(LocalMessage::TYPE_OUTGOING);
		$to = [['label' => 'Lewis', 'email' => 'tent@stardewvalley.com']];
		$cc = [['label' => 'Pierre', 'email' => 'generalstore@stardewvalley.com']];

		$this->accountService->expects(self::once())
			->method('find')
			->with($this->userId, $message->getAccountId());
		$this->service->expects(self::once())
			->method('saveMessage')
			->willThrowException(new Exception());

		$this->expectException(Exception::class);
		$this->controller->create(
			$message->getAccountId(),
			$message->getSubject(),
			$message->getBody(),
			'<p>message</p>',
			$message->isHtml(),
			$to,
			$cc,
			[],
			[],
			$message->getAliasId(),
			$message->getInReplyToMessageId()
		);
	}

	public function testUpdate(): void {
		$message = new LocalMessage();
		$message->setId(1);
		$message->setAccountId(1);
		$message->setAliasId(2);
		$message->setSubject('subject');
		$message->setBody('message');
		$message->setEditorBody('<p>message</p>');
		$message->setHtml(true);
		$message->setInReplyToMessageId('abc');
		$message->setType(LocalMessage::TYPE_DRAFT);
		$message->setFailed(false);
		$to = [['label' => 'Lewis', 'email' => 'tent@stardewvalley.com']];
		$cc = [['label' => 'Pierre', 'email' => 'generalstore@stardewvalley.com']];

		$this->service->expects(self::once())
			->method('getMessage')
			->with($message->getId(), $this->userId)
			->willReturn($message);
		$account = new Account(new MailAccount());
		$this->accountService->expects(self::once())
			->method('find')
			->with($this->userId, $message->getAccountId())
			->willReturn($account);
		$this->service->expects(self::once())
			->method('updateMessage')
			->with($account, $message, $to, $cc, [], [])
			->willReturn($message);

		$expected = JsonResponse::success($message, Http::STATUS_ACCEPTED);
		$actual = $this->controller->update(
			$message->getId(),
			$message->getAccountId(),
			$message->getSubject(),
			$message->getBody(),
			'<p>message</p>',
			$message->isHtml(),
			false,
			$to,
			$cc,
			[],
			[],
			$message->getAliasId(),
			$message->getInReplyToMessageId()
		);

		$this->assertEquals($expected, $actual);
	}

	public function testUpdateMoveToOutbox(): void {
		$message = new LocalMessage();
		$message->setId(1);
		$message->setAccountId(1);
		$message->setAliasId(2);
		$message->setSubject('subject');
		$message->setBody('message');
		$message->setEditorBody('<p>message</p>');
		$message->setHtml(true);
		$message->setInReplyToMessageId('abc');
		$message->setType(LocalMessage::TYPE_OUTGOING);
		$message->setFailed(false);
		$message->setSendAt(123456);
		$to = [['label' => 'Lewis', 'email' => 'tent@stardewvalley.com']];
		$cc = [['label' => 'Pierre', 'email' => 'generalstore@stardewvalley.com']];

		$this->service->expects(self::once())
			->method('getMessage')
			->with($message->getId(), $this->userId)
			->willReturn($message);
		$account = new Account(new MailAccount());
		$this->accountService->expects(self::once())
			->method('find')
			->with($this->userId, $message->getAccountId())
			->willReturn($account);
		$this->service->expects(self::once())
			->method('updateMessage')
			->with($account, $message, $to, $cc, [], [])
			->willReturn($message);

		$expected = JsonResponse::success($message, Http::STATUS_ACCEPTED);
		$actual = $this->controller->update(
			$message->getId(),
			$message->getAccountId(),
			$message->getSubject(),
			$message->getBody(),
			'<p>message</p>',
			$message->isHtml(),
			false,
			$to,
			$cc,
			[],
			[],
			$message->getAliasId(),
			$message->getInReplyToMessageId(),
			$message->getSendAt()
		);

		$this->assertEquals($expected, $actual);
	}


	public function testUpdateMessageNotFound(): void {
		$message = new LocalMessage();
		$message->setId(1);
		$message->setAccountId(1);
		$message->setAliasId(2);
		$message->setSubject('subject');
		$message->setBody('message');
		$message->setEditorBody('<p>message</p>');
		$message->setHtml(true);
		$message->setInReplyToMessageId('abc');
		$message->setType(LocalMessage::TYPE_DRAFT);
		$message->setFailed(false);
		$to = [['label' => 'Lewis', 'email' => 'tent@stardewvalley.com']];
		$cc = [['label' => 'Pierre', 'email' => 'generalstore@stardewvalley.com']];

		$this->service->expects(self::once())
			->method('getMessage')
			->with($message->getId(), $this->userId)
			->willThrowException(new DoesNotExistException(''));
		$this->service->expects(self::never())
			->method('updateMessage');


		$this->expectException(DoesNotExistException::class);
		$expected = JsonResponse::fail('', Http::STATUS_NOT_FOUND);
		$actual = $this->controller->update(
			$message->getId(),
			$message->getAccountId(),
			$message->getSubject(),
			$message->getBody(),
			'<p>message</p>',
			$message->isHtml(),
			false,
			$to,
			$cc,
			[],
			[],
			$message->getAliasId(),
			$message->getInReplyToMessageId()
		);

		$this->assertEquals($expected, $actual);
	}

	public function testUpdateDbException(): void {
		$message = new LocalMessage();
		$message->setId(1);
		$message->setAccountId(1);
		$message->setAliasId(2);
		$message->setSubject('subject');
		$message->setBody('message');
		$message->setEditorBody('<p>message</p>');
		$message->setHtml(true);
		$message->setInReplyToMessageId('abc');
		$message->setType(LocalMessage::TYPE_DRAFT);
		$message->setFailed(false);
		$to = [['label' => 'Lewis', 'email' => 'tent@stardewvalley.com']];
		$cc = [['label' => 'Pierre', 'email' => 'generalstore@stardewvalley.com']];

		$this->service->expects(self::once())
			->method('getMessage')
			->with($message->getId(), $this->userId)
			->willReturn($message);
		$account = new Account(new MailAccount());
		$this->accountService->expects(self::once())
			->method('find')
			->with($this->userId, $message->getAccountId())
			->willReturn($account);
		$this->service->expects(self::once())
			->method('updateMessage')
			->with($account, $message, $to, $cc, [], [])
			->willThrowException(new Exception());

		$this->expectException(Exception::class);
		$this->controller->update(
			$message->getId(),
			$message->getAccountId(),
			$message->getSubject(),
			$message->getBody(),
			'<p>message</p>',
			$message->isHtml(),
			false,
			$to,
			$cc,
			[],
			[],
			$message->getAliasId(),
			$message->getInReplyToMessageId()
		);
	}
}
