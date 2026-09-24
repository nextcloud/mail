<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Unit\Listener;

use ChristophWurst\Nextcloud\Testing\TestCase;
use DateInterval;
use DateTime;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\Message;
use OCA\Mail\Events\NewMessagesSynchronized;
use OCA\Mail\Listener\NewMessagesSummarizeListener;
use OCA\Mail\Service\AiIntegrations\AiIntegrationsService;
use OCP\AppFramework\Services\IAppConfig;
use OCP\AppFramework\Utility\ITimeFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Psr\Log\Test\TestLogger;

class NewMessagesSummarizeListenerTest extends TestCase {
	private const NOW = 1_750_000_000;

	private LoggerInterface $logger;
	private NewMessagesSummarizeListener $listener;
	private AiIntegrationsService&MockObject $aiService;
	private IAppConfig&MockObject $appConfig;
	private ITimeFactory&MockObject $timeFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = new TestLogger();
		$this->aiService = $this->createMock(AiIntegrationsService::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->timeFactory->method('getDateTime')
			->willReturnCallback(static fn () => new DateTime('@' . self::NOW));

		$this->listener = new NewMessagesSummarizeListener(
			$this->logger,
			$this->aiService,
			$this->appConfig,
			$this->timeFactory,
		);
	}

	private function createMailbox(int $id, string $specialUse = '[]'): Mailbox {
		$mailbox = new Mailbox();
		$mailbox->setId($id);
		$mailbox->setSpecialUse($specialUse);
		return $mailbox;
	}

	private function createMessage(int $sentAt): Message {
		$message = new Message();
		$message->setSentAt($sentAt);
		return $message;
	}

	private function enableLlm(): void {
		$this->appConfig->expects($this->once())
			->method('getAppValueBool')
			->with('llm_processing', false)
			->willReturn(true);
	}

	private function maxAgeCutoff(): int {
		return (new DateTime('@' . self::NOW))
			->sub(new DateInterval(AiIntegrationsService::RECENT_MESSAGE_MAX_AGE))
			->getTimestamp();
	}

	public function testLlmEnabled(): void {
		$account = new Account(new MailAccount());
		$message = $this->createMessage(self::NOW);
		$event = new NewMessagesSynchronized($account, $this->createMailbox(1), [$message]);
		$this->enableLlm();
		$this->aiService->expects($this->once())
			->method('summarizeMessages')
			->with($account, [$message]);

		$this->listener->handle($event);
	}

	public function testLlmDisabled(): void {
		$event = $this->createStub(NewMessagesSynchronized::class);
		$this->appConfig->expects($this->once())
			->method('getAppValueBool')
			->with('llm_processing', false)
			->willReturn(false);
		$this->aiService->expects($this->never())
			->method('summarizeMessages');

		$this->listener->handle($event);
	}

	public static function provideSkippedSpecialUses(): array {
		return [
			'all' => ['["all"]'],
			'archive' => ['["archive"]'],
			'drafts' => ['["drafts"]'],
			'flagged' => ['["flagged"]'],
			'junk' => ['["junk"]'],
			'sent' => ['["sent"]'],
			'trash' => ['["trash"]'],
		];
	}

	/**
	 * @dataProvider provideSkippedSpecialUses
	 */
	public function testSkipsSpecialUseMailbox(string $specialUse): void {
		$account = new Account(new MailAccount());
		$event = new NewMessagesSynchronized($account, $this->createMailbox(1, $specialUse), [$this->createMessage(self::NOW)]);
		$this->enableLlm();
		$this->aiService->expects($this->never())
			->method('summarizeMessages');

		$this->listener->handle($event);
	}

	public static function provideAccountMailboxSetters(): array {
		return [
			'archive' => ['setArchiveMailboxId'],
			'drafts' => ['setDraftsMailboxId'],
			'junk' => ['setJunkMailboxId'],
			'sent' => ['setSentMailboxId'],
			'snooze' => ['setSnoozeMailboxId'],
			'trash' => ['setTrashMailboxId'],
		];
	}

	/**
	 * @dataProvider provideAccountMailboxSetters
	 */
	public function testSkipsAccountConfiguredMailbox(string $setter): void {
		$mailAccount = new MailAccount();
		$mailAccount->$setter(7);
		$account = new Account($mailAccount);
		$event = new NewMessagesSynchronized($account, $this->createMailbox(7), [$this->createMessage(self::NOW)]);
		$this->enableLlm();
		$this->aiService->expects($this->never())
			->method('summarizeMessages');

		$this->listener->handle($event);
	}

	public function testOnlySummarizesRecentMessages(): void {
		$account = new Account(new MailAccount());
		$recent = $this->createMessage(self::NOW);
		$atCutoff = $this->createMessage($this->maxAgeCutoff());
		$old = $this->createMessage($this->maxAgeCutoff() - 1);
		$event = new NewMessagesSynchronized($account, $this->createMailbox(1), [$old, $recent, $atCutoff]);
		$this->enableLlm();
		$this->aiService->expects($this->once())
			->method('summarizeMessages')
			->with($account, [$recent, $atCutoff]);

		$this->listener->handle($event);
	}

	public function testSkipsWhenAllMessagesAreOld(): void {
		$account = new Account(new MailAccount());
		$event = new NewMessagesSynchronized($account, $this->createMailbox(1), [$this->createMessage($this->maxAgeCutoff() - 1)]);
		$this->enableLlm();
		$this->aiService->expects($this->never())
			->method('summarizeMessages');

		$this->listener->handle($event);
	}
}
