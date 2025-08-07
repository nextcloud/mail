<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Mail\Tests\Unit\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client_Socket;
use OCA\Mail\Account;
use OCA\Mail\Address;
use OCA\Mail\AddressList;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\Message;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\Model\IMAPMessage;
use OCA\Mail\Service\AiIntegrations\AiIntegrationsService;
use OCA\Mail\Service\AiIntegrations\Cache;
use OCP\IConfig;
use OCP\IL10N;
use OCP\TaskProcessing\IManager as TaskProcessingManager;
use OCP\TaskProcessing\IProvider as TaskProcessingProvider;
use OCP\TextProcessing\FreePromptTaskType;
use OCP\TextProcessing\IManager as TextProcessingManager;
use OCP\TextProcessing\SummaryTaskType;
use OCP\TextProcessing\Task as TextProcessingTask;
use OCP\TextProcessing\TopicsTaskType;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;

class AiIntegrationsServiceTest extends TestCase {

	private TextProcessingManager|MockObject $textProcessingManager;
	private IConfig|MockObject $config;
	private NullLogger|MockObject $logger;
	private AiIntegrationsService $aiIntegrationsService;
	private Cache|MockObject $cache;
	private IMAPClientFactory|MockObject $clientFactory;
	private IMailManager|MockObject $mailManager;
	private TaskProcessingManager|MockObject $taskProcessingManager;
	private TaskProcessingProvider|MockObject $taskProcessingProvider;
	private TextProcessingProvider|MockObject $textProcessingProvider;
	private IL10N|MockObject $l10n;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(NullLogger::class);
		$this->config = $this->createMock(IConfig::class);
		$this->cache = $this->createMock(Cache::class);
		$this->clientFactory = $this->createMock(IMAPClientFactory::class);
		$this->mailManager = $this->createMock(IMailManager::class);
		$this->taskProcessingManager = $this->createMock(TaskProcessingManager::class);
		$this->textProcessingManager = $this->createMock(TextProcessingManager::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->aiIntegrationsService = new AiIntegrationsService(
			$this->logger,
			$this->config,
			$this->cache,
			$this->clientFactory,
			$this->mailManager,
			$this->taskProcessingManager,
			$this->textProcessingManager,
			$this->l10n
		);

		$this->taskProcessingProvider = $this->createMock(TaskProcessingProvider::class);
	}

	public function testSummarizeThreadNoBackend(): void {
		$account = new Account(new MailAccount());
		$this->textProcessingManager->method('getAvailableTaskTypes')->willReturn([]);
		$this->expectException(ServiceException::class);
		$this->expectExceptionMessage('No language model available for summary');
		$this->aiIntegrationsService->summarizeThread($account, '', [], '');
	}


	public function testSmartReplyNoBackend(): void {
		$account = new Account(new MailAccount());
		$mailbox = new Mailbox();
		$message = new Message();
		$this->textProcessingManager
			->method('getAvailableTaskTypes')
			->willReturn([]);
		$this->expectException(ServiceException::class);
		$this->expectExceptionMessage('No language model available for smart replies');
		$this->aiIntegrationsService->getSmartReply($account, $mailbox, $message, '');
	}

	public function testSmartReply(): void {
		$account = new Account(new MailAccount());
		$mailbox = new Mailbox();
		$message = new Message();
		$imapMessage = $this->createMock(IMAPMessage::class);
		$message->setUid(1);
		$currentUserId = 'user';
		$this->textProcessingManager
			->method('getAvailableTaskTypes')
			->willReturn([FreePromptTaskType::class]);
		$this->cache->method('getValue')->willReturn(false);
		$this->clientFactory->method('getClient')->with($account)->willReturn($this->createMock(Horde_Imap_Client_Socket::class));
		$this->mailManager->method('getImapMessage')->willReturn($imapMessage);
		$imapMessage->method('isOneClickUnsubscribe')->willReturn(false);
		$imapMessage->method('getUnsubscribeUrl')->willReturn(null);
		$fromList = new AddressList([ Address::fromRaw('personal@example.com', 'personal@example.com')]);
		$imapMessage->method('getFrom')->willReturn($fromList);
		$imapMessage->method('getPlainBody')->willReturn('This is a test message');

		$this->textProcessingManager->expects($this->once())
			->method('runTask')
			->will($this->returnCallback(function (TextProcessingTask $task) {
				$task->setOutput('{"reply1":"reply1","reply2":"reply2"}');
				return '';
			}));

		$result = $this->aiIntegrationsService->getSmartReply($account, $mailbox, $message, $currentUserId);

		$this->assertEquals(
			[
				'reply1' => 'reply1',
				'reply2' => 'reply2'
			],
			$result
		);
	}

	public function testSmartReplyMarkdownFormat(): void {
		$account = new Account(new MailAccount());
		$mailbox = new Mailbox();
		$message = new Message();
		$imapMessage = $this->createMock(IMAPMessage::class);
		$message->setUid(1);
		$currentUserId = 'user';
		$this->textProcessingManager
			->method('getAvailableTaskTypes')
			->willReturn([FreePromptTaskType::class]);
		$this->cache->method('getValue')->willReturn(false);
		$this->clientFactory->method('getClient')->with($account)->willReturn($this->createMock(Horde_Imap_Client_Socket::class));
		$this->mailManager->method('getImapMessage')->willReturn($imapMessage);
		$imapMessage->method('isOneClickUnsubscribe')->willReturn(false);
		$imapMessage->method('getUnsubscribeUrl')->willReturn(null);
		$fromList = new AddressList([ Address::fromRaw('personal@example.com', 'personal@example.com')]);
		$imapMessage->method('getFrom')->willReturn($fromList);
		$imapMessage->method('getPlainBody')->willReturn('This is a test message');

		$this->textProcessingManager->expects($this->once())
			->method('runTask')
			->will($this->returnCallback(function (TextProcessingTask $task) {
				$task->setOutput('```json{"reply1":"reply1","reply2":"reply2"}```');
				return '';
			}));

		$result = $this->aiIntegrationsService->getSmartReply($account, $mailbox, $message, $currentUserId);

		$this->assertEquals(
			[
				'reply1' => 'reply1',
				'reply2' => 'reply2'
			],
			$result
		);
	}

	public function testGeneratedMessage(): void {
		$account = new Account(new MailAccount());
		$mailbox = new Mailbox();
		$message = new Message();
		$message->setUid(1);
		$imapMessage = $this->createMock(IMAPMessage::class);
		$addessList = $this->createMock(AddressList::class);
		$addessList->method('first')->willreturn('normal@email.com');
		$this->mailManager->method('getImapMessage')->willReturn($imapMessage);
		$this->textProcessingManager
			->method('getAvailableTaskTypes')
			->willReturn([FreePromptTaskType::class]);
		$imapMessage->method('isOneClickUnsubscribe')->willReturn(true);
		$replies = $this->aiIntegrationsService->getSmartReply($account, $mailbox, $message, '');
		$this->assertEquals($replies, []);
		$imapMessage->method('isOneClickUnsubscribe')->willReturn(false);
		$imapMessage->method('getUnsubscribeUrl')->willReturn('iAmAnUnsubscribeUrl');
		$replies = $this->aiIntegrationsService->getSmartReply($account, $mailbox, $message, '');
		$this->assertEquals($replies, []);
		$imapMessage->method('isOneClickUnsubscribe')->willReturn(false);
		$imapMessage->method('getUnsubscribeUrl')->willReturn(null);
		$addessList->method('first')->willreturn('noreply@test.com');
		$replies = $this->aiIntegrationsService->getSmartReply($account, $mailbox, $message, '');
		$this->assertEquals($replies, []);
	}

	public function testLlmAvailable(): void {
		$this->textProcessingManager
			->method('getAvailableTaskTypes')
			->willReturn([SummaryTaskType::class, TopicsTaskType::class, FreePromptTaskType::class]);
		$isAvailable = $this->aiIntegrationsService->isLlmAvailable(SummaryTaskType::class);
		$this->assertTrue($isAvailable);

	}

	public function testLlmUnavailable(): void {
		$this->textProcessingManager
			->method('getAvailableTaskTypes')
			->willReturn([TopicsTaskType::class, FreePromptTaskType::class]);
		$isAvailable = $this->aiIntegrationsService->isLlmAvailable(SummaryTaskType::class);
		$this->assertFalse($isAvailable);

	}

	public function isLlmProcessingEnabledDataProvider(): array {
		return [
			['no', false],
			['yes', true],
		];
	}

	/**
	 * @dataProvider isLlmProcessingEnabledDataProvider
	 */
	public function testIsLlmProcessingEnabled(string $appConfigValue, bool $expected) {
		$this->config->expects(self::once())
			->method('getAppValue')
			->with('mail', 'llm_processing', 'no')
			->willReturn($appConfigValue);

		$this->assertEquals($expected, $this->aiIntegrationsService->isLlmProcessingEnabled());
	}

	public function testCached(): void {
		$account = new Account(new MailAccount());
		$mailbox = new Mailbox();

		$message1 = new Message();
		$message1->setMessageId('300');
		$message1->setPreviewText('message1');
		$message1->setThreadRootId('some-thread-root-id-1');

		$message2 = new Message();
		$message2->setMessageId('301');
		$message2->setPreviewText('message2');
		$message2->setThreadRootId('some-thread-root-id-1');

		$message3 = new Message();
		$message3->setMessageId('302');
		$message3->setPreviewText('message3');
		$message3->setThreadRootId('some-thread-root-id-1');

		$messages = [ $message1,$message2,$message3];
		$this->textProcessingManager
			->method('getAvailableTaskTypes')
			->willReturn([SummaryTaskType::class]);

		$messageIds = [ $message1->getMessageId(),$message2->getMessageId(),$message3->getMessageId()];
		$key = $this->cache->buildUrlKey($messageIds);
		$this->cache
			->method('getValue')
			->with($key)
			->willReturn('this is a cached summary');

		$this->assertEquals('this is a cached summary', $this->aiIntegrationsService->summarizeThread($account, 'some-thread-root-id-1', $messages, 'admin'));
	}

	public function testGenerateEventDataFreePromptUnavailable(): void {
		$account = $this->createMock(Account::class);
		$message1 = new Message();
		$message2 = new Message();
		$this->textProcessingManager->expects(self::once())
			->method('getAvailableTaskTypes')
			->willReturn([]);

		$result = $this->aiIntegrationsService->generateEventData(
			$account,
			'thread1',
			[$message1, $message2],
			'user123',
		);

		self::assertNull($result);
	}

	public function testGenerateEventDataInvalidJson(): void {

		$account = $this->createMock(Account::class);
		$message1 = new Message();
		$message1->setUid(1);
		$message1->setMailboxId(123);
		$message2 = new Message();
		$message2->setUid(2);
		$message2->setMailboxId(456);
		$this->textProcessingManager->expects(self::once())
			->method('getAvailableTaskTypes')
			->willReturn([FreePromptTaskType::class]);
		$imapMessage = $this->createMock(IMAPMessage::class);
		$this->mailManager->expects(self::exactly(2))
			->method('getImapMessage')
			->willReturn($imapMessage);
		$imapMessage->expects(self::exactly(2))
			->method('getPlainBody')
			->willReturn('plain');
		$this->textProcessingManager->expects(self::once())
			->method('runTask')
			->willReturn('Jason');

		$result = $this->aiIntegrationsService->generateEventData(
			$account,
			'thread1',
			[$message1, $message2],
			'user123',
		);

		self::assertNull($result);
	}

	public function testGenerateEventData(): void {

		$account = $this->createMock(Account::class);
		$message1 = new Message();
		$message1->setUid(1);
		$message1->setMailboxId(123);
		$message2 = new Message();
		$message2->setUid(2);
		$message2->setMailboxId(456);
		$this->textProcessingManager->expects(self::once())
			->method('getAvailableTaskTypes')
			->willReturn([FreePromptTaskType::class]);
		$imapMessage = $this->createMock(IMAPMessage::class);
		$this->mailManager->expects(self::exactly(2))
			->method('getImapMessage')
			->willReturn($imapMessage);
		$imapMessage->expects(self::exactly(2))
			->method('getPlainBody')
			->willReturn('plain');
		$this->textProcessingManager->expects(self::once())
			->method('runTask')
			->willReturn('{"title":"Meeting", "agenda":"* Q&A"}');

		$result = $this->aiIntegrationsService->generateEventData(
			$account,
			'thread1',
			[$message1, $message2],
			'user123',
		);

		self::assertNotNull($result);
		self::assertSame('Meeting', $result->getSummary());
		self::assertSame('* Q&A', $result->getDescription());
	}

	public function testSummarizeMessagesNoProvider(): void {
		$account = new Account(new MailAccount());
		$message = new Message();
		$this->taskProcessingManager->expects(self::once())
			->method('getAvailableTaskTypes')
			->willReturn([]);
		$this->logger->expects(self::once())
			->method('info')
			->with('No text summary provider available');

		$this->aiIntegrationsService->summarizeMessages($account, [$message]);
	}

	public function testSummarizeMessagesContainsSummary(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setId(123);
		$mailAccount->setEmail('user@domain.tld');
		$mailAccount->setInboundHost('127.0.0.1');
		$mailAccount->setInboundPort(993);
		$mailAccount->setInboundSslMode('ssl');
		$mailAccount->setInboundUser('user@domain.tld');
		$mailAccount->setInboundPassword('encrypted');
		$account = new Account($mailAccount);
		$message = new Message();
		$message->setSummary('Test Summary');

		$this->taskProcessingManager->expects(self::once())
			->method('getAvailableTaskTypes')
			->willReturn(['core:text2text' => $this->taskProcessingProvider]);
		$this->clientFactory->expects(self::once())
			->method('getClient');

		$this->aiIntegrationsService->summarizeMessages($account, [$message]);
	}

	public function testSummarizeMessagesIsEncrypted(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setId(123);
		$mailAccount->setUserId('user1');
		$mailAccount->setEmail('user1@domain.tld');
		$mailAccount->setInboundHost('127.0.0.1');
		$mailAccount->setInboundPort(993);
		$mailAccount->setInboundSslMode('ssl');
		$mailAccount->setInboundUser('user1@domain.tld');
		$mailAccount->setInboundPassword('encrypted');
		$account = new Account($mailAccount);

		$mailBox = new Mailbox();
		$mailBox->setId(1);

		$message = new Message();
		$message->setId(1);
		$message->setUid(100);
		$message->setMailboxId(1);

		$imapClient = $this->clientFactory->getClient($account);

		$imapMessage = $this->createMock(IMAPMessage::class);
		$imapMessage->expects(self::never())
			->method('getPlainBody')
			->willReturn('This is a test message');
		$imapMessage->expects(self::once())
			->method('isEncrypted')->willReturn(true);

		$this->taskProcessingManager->expects(self::once())
			->method('getAvailableTaskTypes')
			->willReturn(['core:text2text' => $this->taskProcessingProvider]);
		$this->taskProcessingManager->expects(self::never())
			->method('scheduleTask');

		$this->clientFactory->expects(self::once())
			->method('getClient');

		$this->mailManager->expects(self::once())
			->method('getMailbox')
			->with(
				$account->getUserId(),
				$message->getMailboxId()
			)
			->willReturn($mailBox);
		$this->mailManager->expects(self::once())
			->method('getImapMessage')
			->with(
				$imapClient,
				$account,
				$mailBox,
				$message->getUid(),
				true
			)
			->willReturn($imapMessage);

		$this->aiIntegrationsService->summarizeMessages($account, [$message]);
	}

	public function testSummarizeMessages(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setId(123);
		$mailAccount->setUserId('user1');
		$mailAccount->setEmail('user1@domain.tld');
		$mailAccount->setInboundHost('127.0.0.1');
		$mailAccount->setInboundPort(993);
		$mailAccount->setInboundSslMode('ssl');
		$mailAccount->setInboundUser('user1@domain.tld');
		$mailAccount->setInboundPassword('encrypted');
		$account = new Account($mailAccount);

		$mailBox = new Mailbox();
		$mailBox->setId(1);

		$message = new Message();
		$message->setId(1);
		$message->setUid(100);
		$message->setMailboxId(1);

		$imapClient = $this->clientFactory->getClient($account);

		$imapMessage = $this->createMock(IMAPMessage::class);
		$imapMessage->expects(self::atMost(2))
			->method('getPlainBody')
			->willReturn('This is a test message');
		$imapMessage->expects(self::once())
			->method('isEncrypted')->willReturn(false);

		$this->taskProcessingManager->expects(self::once())
			->method('getAvailableTaskTypes')
			->willReturn(['core:text2text' => $this->taskProcessingProvider]);
		$this->taskProcessingManager->expects(self::once())
			->method('scheduleTask');

		$this->clientFactory->expects(self::once())
			->method('getClient');

		$this->mailManager->expects(self::once())
			->method('getMailbox')
			->with(
				$account->getUserId(),
				$message->getMailboxId()
			)
			->willReturn($mailBox);
		$this->mailManager->expects(self::once())
			->method('getImapMessage')
			->with(
				$imapClient,
				$account,
				$mailBox,
				$message->getUid(),
				true
			)
			->willReturn($imapMessage);

		$this->aiIntegrationsService->summarizeMessages($account, [$message]);
	}

	public function testRequiresTranslationNoBackend(): void {
		$account = new Account(new MailAccount());
		$mailbox = new Mailbox();
		$message = new Message();

		$this->textProcessingManager
			->method('getAvailableTaskTypes')
			->willReturn([]);
		$result = $this->aiIntegrationsService->requiresTranslation($account, $mailbox, $message, '');
		$this->assertNull($result);

	}

	public function testRequiresTranslation(): void {
		$account = new Account(new MailAccount());
		$mailbox = new Mailbox();
		$message = new Message();
		$imapMessage = $this->createMock(IMAPMessage::class);
		$message->setUid(1);
		$currentUserId = 'user';
		$this->textProcessingManager
			->method('getAvailableTaskTypes')
			->willReturn([FreePromptTaskType::class]);
		$this->cache->method('getValue')->willReturn(false);
		$this->clientFactory->method('getClient')->with($account)->willReturn($this->createMock(Horde_Imap_Client_Socket::class));
		$this->mailManager->method('getImapMessage')->willReturn($imapMessage);
		$imapMessage->method('isOneClickUnsubscribe')->willReturn(false);
		$imapMessage->method('getUnsubscribeUrl')->willReturn(null);
		$fromList = new AddressList([ Address::fromRaw('personal@example.com', 'personal@example.com')]);
		$imapMessage->method('getFrom')->willReturn($fromList);
		$imapMessage->method('getPlainBody')->willReturn('Ceci n\'est pas un message');

		$this->textProcessingManager->expects($this->once())
			->method('runTask')
			->will($this->returnCallback(function (TextProcessingTask $task) {
				$task->setOutput('{"needsTranslation": true}, the message is in French that is the value returned is true ');
				return '';
			}));

		$result = $this->aiIntegrationsService->requiresTranslation($account, $mailbox, $message, $currentUserId);

		$this->assertTrue($result);
	}

}
