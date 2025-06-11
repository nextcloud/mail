<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace Unit\Listener;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Events\NewMessagesSynchronized;
use OCA\Mail\Listener\NewMessagesSummarizeListener;
use OCA\Mail\Service\AiIntegrations\AiIntegrationsService;
use OCP\AppFramework\Services\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Psr\Log\Test\TestLogger;

class NewMessagesSummarizeListenerTest extends TestCase {
	private LoggerInterface $logger;
	private NewMessagesSummarizeListener $listener;
	private AiIntegrationsService|MockObject $aiService;
	private IAppConfig|MockObject $appConfig;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = new TestLogger();
		$this->aiService = $this->createMock(AiIntegrationsService::class);
		$this->appConfig = $this->createMock(IAppConfig::class);

		$this->listener = new NewMessagesSummarizeListener(
			$this->logger,
			$this->aiService,
			$this->appConfig
		);
	}

	public function testLlmEnabled(): void {
		$event = $this->createMock(NewMessagesSynchronized::class);
		$account = new Account(new MailAccount());
		$event->expects($this->once())
			->method('getAccount')
			->willReturn($account);
		$event->expects($this->once())
			->method('getMessages')
			->willReturn([]);
		$this->appConfig->expects($this->once())
			->method('getAppValueBool')
			->with('llm_processing', false)
			->willReturn(true);
		$this->aiService->expects($this->once())
			->method('summarizeMessages')
			->with($account, []);
		$this->listener->handle($event);
	}

	public function testLlmDisabled(): void {
		$event = $this->createMock(NewMessagesSynchronized::class);
		$this->appConfig->expects($this->once())
			->method('getAppValueBool')
			->with('llm_processing', false)
			->willReturn(false);
		$this->aiService->expects($this->never())
			->method('summarizeMessages');
		$this->listener->handle($event);
	}

}
