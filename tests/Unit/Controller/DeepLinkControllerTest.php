<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Controller;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Controller\DeepLinkController;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Service\AccountService;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

class DeepLinkControllerTest extends TestCase {
	private string $appName;
	private IRequest $request;
	private MailAccountMapper $mailAccountMapper;
	private AccountService $accountService;
	private MessageMapper $messageMapper;
	private IURLGenerator $urlGenerator;
	private IUserSession $userSession;
	private LoggerInterface $logger;
	private DeepLinkController $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->appName = 'mail';
		$this->request = $this->createMock(IRequest::class);
		$this->mailAccountMapper = $this->createMock(MailAccountMapper::class);
		$this->accountService = $this->createMock(AccountService::class);
		$this->messageMapper = $this->createMock(MessageMapper::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->controller = new DeepLinkController(
			$this->appName,
			$this->request,
			$this->mailAccountMapper,
			$this->accountService,
			$this->messageMapper,
			$this->urlGenerator,
			$this->userSession,
			$this->logger
		);
	}

	public function testOpenNotLoggedIn(): void {
		$this->userSession->expects(self::once())
			->method('getUser')
			->willReturn(null);

		$this->urlGenerator->expects(self::once())
			->method('linkToRouteAbsolute')
			->with('core.page.login')
			->willReturn('http://localhost/login');

		$response = $this->controller->open('test-message-id');

		self::assertInstanceOf(RedirectResponse::class, $response);
		self::assertSame('http://localhost/login', $response->getRedirectURL());
	}

	public function testOpenMessageFound(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user123');

		$this->userSession->expects(self::once())
			->method('getUser')
			->willReturn($user);

		$lightAccount = new MailAccount();
		$lightAccount->setId(1);

		$this->mailAccountMapper->expects(self::once())
			->method('findByUserId')
			->with('user123')
			->willReturn([$lightAccount]);

		$account = $this->createMock(Account::class);
		$this->accountService->expects(self::once())
			->method('find')
			->with('user123', 1)
			->willReturn($account);

		$message = new Message();
		$message->setId(42);
		$message->setMailboxId(5);

		$this->messageMapper->expects(self::once())
			->method('findByMessageId')
			->with($account, '<test-message-id>')
			->willReturn([$message]);

		$this->urlGenerator->expects(self::once())
			->method('linkToRouteAbsolute')
			->with('mail.page.thread', ['mailboxId' => 5, 'id' => 42])
			->willReturn('http://localhost/apps/mail/box/5/thread/42');

		$response = $this->controller->open('test-message-id');

		self::assertInstanceOf(RedirectResponse::class, $response);
		self::assertSame('http://localhost/apps/mail/box/5/thread/42', $response->getRedirectURL());
	}

	public function testOpenMessageNotFound(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user123');

		$this->userSession->expects(self::once())
			->method('getUser')
			->willReturn($user);

		$lightAccount = new MailAccount();
		$lightAccount->setId(1);

		$this->mailAccountMapper->expects(self::once())
			->method('findByUserId')
			->with('user123')
			->willReturn([$lightAccount]);

		$account = $this->createMock(Account::class);
		$this->accountService->expects(self::once())
			->method('find')
			->with('user123', 1)
			->willReturn($account);

		$this->messageMapper->expects(self::once())
			->method('findByMessageId')
			->with($account, '<test-message-id>')
			->willReturn([]);

		$this->urlGenerator->expects(self::once())
			->method('linkToRouteAbsolute')
			->with('mail.page.index', [])
			->willReturn('http://localhost/apps/mail/');

		$response = $this->controller->open('test-message-id');

		self::assertInstanceOf(RedirectResponse::class, $response);
		self::assertSame('http://localhost/apps/mail/', $response->getRedirectURL());
	}

	public function testOpenException(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user123');

		$this->userSession->expects(self::once())
			->method('getUser')
			->willReturn($user);

		$exception = new \RuntimeException('Database error');
		$this->mailAccountMapper->expects(self::once())
			->method('findByUserId')
			->with('user123')
			->willThrowException($exception);

		$this->logger->expects(self::once())
			->method('error')
			->with('DeepLinkController: An unexpected error occurred.', [
				'exception' => $exception,
				'messageId' => 'test-message-id',
			]);

		$this->urlGenerator->expects(self::once())
			->method('linkToRouteAbsolute')
			->with('mail.page.index', [])
			->willReturn('http://localhost/apps/mail/');

		$response = $this->controller->open('test-message-id');

		self::assertInstanceOf(RedirectResponse::class, $response);
		self::assertSame('http://localhost/apps/mail/', $response->getRedirectURL());
	}
}
