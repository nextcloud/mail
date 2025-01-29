<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Mail\Tests\Unit\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
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
use OCP\AppFramework\QueryException;
use OCP\IConfig;
use OCP\TaskProcessing\IManager as TaskProcessingManager;
use OCP\TaskProcessing\IProvider as TaskProcessingProvider;
use OCP\TextProcessing\FreePromptTaskType;
use OCP\TextProcessing\IManager;
use OCP\TextProcessing\SummaryTaskType;
use OCP\TextProcessing\TopicsTaskType;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\UnknownTypeException;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;

use function interface_exists;

class AiIntegrationsServiceTest extends TestCase {

	private ContainerInterface|MockObject $container;
	private IManager|MockObject $manager;
	private IConfig|MockObject $config;
	private NullLogger|MockObject $logger;
	private AiIntegrationsService $aiIntegrationsService;
	private Cache|MockObject $cache;
	private IMAPClientFactory|MockObject $clientFactory;
	private IMailManager|MockObject $mailManager;
	private TaskProcessingManager|MockObject $taskProcessingManager;
	private TaskProcessingProvider|MockObject $taskProcessingProvider;

	protected function setUp(): void {
		parent::setUp();
		$this->container = $this->createMock(ContainerInterface::class);
		try {
			$this->manager = $this->createMock(IManager::class);
		} catch (UnknownTypeException $e) {
			$this->manager = null;
		}

		$this->logger = $this->createMock(NullLogger::class);
		$this->config = $this->createMock(IConfig::class);
		$this->cache = $this->createMock(Cache::class);
		$this->clientFactory = $this->createMock(IMAPClientFactory::class);
		$this->mailManager = $this->createMock(IMailManager::class);
		$this->taskProcessingManager = $this->createMock(TaskProcessingManager::class);
		$this->aiIntegrationsService = new AiIntegrationsService(
			$this->container,
			$this->logger,
			$this->config,
			$this->cache,
			$this->clientFactory,
			$this->mailManager,
			$this->taskProcessingManager,
		);

		$this->taskProcessingProvider = $this->createMock(TaskProcessingProvider::class);
	}

	public function testSummarizeThreadNoBackend() {
		$account = new Account(new MailAccount());
		$mailbox = new Mailbox();
		if ($this->manager !== null) {
			$this->container->method('get')->willReturn($this->manager);
			$this->manager
				->method('getAvailableTaskTypes')
				->willReturn([]);
			$this->expectException(ServiceException::class);
			$this->expectExceptionMessage('No language model available for summary');
			$this->aiIntegrationsService->summarizeThread($account, '', [], '');
		}
		$this->container->method('get')->willThrowException(new ServiceException());
		$this->expectException(ServiceException::class);
		$this->expectExceptionMessage('Text processing is not available in your current Nextcloud version');
		$this->aiIntegrationsService->summarizeThread($account, '', [], '');

	}


	public function testSmartReplyNoBackend() {
		$account = new Account(new MailAccount());
		$mailbox = new Mailbox();
		$message = new Message();
		if ($this->manager !== null) {
			$this->container->method('get')->willReturn($this->manager);
			$this->manager
				->method('getAvailableTaskTypes')
				->willReturn([]);
			$this->expectException(ServiceException::class);
			$this->expectExceptionMessage('No language model available for smart replies');
			$this->aiIntegrationsService->getSmartReply($account, $mailbox, $message, '');
		}
		$this->container->method('get')->willThrowException(new ServiceException());
		$this->expectException(ServiceException::class);
		$this->expectExceptionMessage('Text processing is not available in your current Nextcloud version');
		$this->aiIntegrationsService->getSmartReply($account, $mailbox, $message, '');

	}

	public function testGeneratedMessage() {
		$account = new Account(new MailAccount());
		$mailbox = new Mailbox();
		$message = new Message();
		$message->setUid(1);
		$imapMessage = $this->createMock(IMAPMessage::class);
		$addessList = $this->createMock(AddressList::class);
		$addessList->method('first')->willreturn('normal@email.com');
		$this->mailManager->method('getImapMessage')->willReturn($imapMessage);
		if ($this->manager !== null) {
			$this->container->method('get')->willReturn($this->manager);
			$this->manager
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
		} else {
			$this->container->method('get')->willThrowException(new ServiceException());
			$this->expectException(ServiceException::class);
			$this->expectExceptionMessage('Text processing is not available in your current Nextcloud version');
			$this->aiIntegrationsService->getSmartReply($account, $mailbox, $message, '');
		}
	}

	public function testLlmAvailable() {
		if ($this->manager !== null) {
			$this->container->method('get')->willReturn($this->manager);
			$this->manager
				->method('getAvailableTaskTypes')
				->willReturn([SummaryTaskType::class, TopicsTaskType::class, FreePromptTaskType::class]);
			$isAvailable = $this->aiIntegrationsService->isLlmAvailable(SummaryTaskType::class);
			$this->assertTrue($isAvailable);
		} else {
			$this->container->method('get')->willThrowException(new Exception());
			$isAvailable = $this->aiIntegrationsService->isLlmAvailable(SummaryTaskType::class);
			$this->assertFalse($isAvailable);
		}

	}

	public function testLlmUnavailable() {
		if ($this->manager !== null) {
			$this->container->method('get')->willReturn($this->manager);
			$this->manager
				->method('getAvailableTaskTypes')
				->willReturn([TopicsTaskType::class, FreePromptTaskType::class]);
			$isAvailable = $this->aiIntegrationsService->isLlmAvailable(SummaryTaskType::class);
			$this->assertFalse($isAvailable);
		} else {
			$this->container->method('get')->willThrowException(new Exception());
			$isAvailable = $this->aiIntegrationsService->isLlmAvailable(SummaryTaskType::class);
			$this->assertFalse($isAvailable);
		}

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

	public function testCached() {
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
		if ($this->manager !== null) {
			$this->container->method('get')->willReturn($this->manager);
			$this->manager
				->method('getAvailableTaskTypes')
				->willReturn([SummaryTaskType::class]);

			$messageIds = [ $message1->getMessageId(),$message2->getMessageId(),$message3->getMessageId()];
			$key = $this->cache->buildUrlKey($messageIds);
			$this->cache
				->method('getValue')
				->with($key)
				->willReturn('this is a cached summary');

			$this->assertEquals('this is a cached summary', $this->aiIntegrationsService->summarizeThread($account, 'some-thread-root-id-1', $messages, 'admin'));
		} else {
			$this->container->method('get')->willThrowException(new ServiceException());
			$this->expectException(ServiceException::class);
			$this->expectExceptionMessage('Text processing is not available in your current Nextcloud version');
			$this->aiIntegrationsService->summarizeThread($account, 'some-thread-root-id-1', $messages, 'admin');
		}
	}

	public function testGenerateEventDataLlmUnavailable(): void {
		if (!interface_exists(IManager::class)) {
			$this->markTestSkipped('Text processing APIs require Nextcloud 27+');
		}

		$account = $this->createMock(Account::class);
		$message1 = new Message();
		$message2 = new Message();
		$this->container->expects(self::once())
			->method('get')
			->willThrowException($this->createMock(QueryException::class));

		$result = $this->aiIntegrationsService->generateEventData(
			$account,
			'thread1',
			[$message1, $message2],
			'user123',
		);

		self::assertNull($result);
	}

	public function testGenerateEventDataFreePromptUnavailable(): void {
		if (!interface_exists(IManager::class)) {
			$this->markTestSkipped('Text processing APIs require Nextcloud 27+');
		}

		$account = $this->createMock(Account::class);
		$message1 = new Message();
		$message2 = new Message();
		$manager = $this->createMock(IManager::class);
		$this->container->expects(self::once())
			->method('get')
			->willReturn($manager);
		$manager->expects(self::once())
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
		if (!interface_exists(IManager::class)) {
			$this->markTestSkipped('Text processing APIs require Nextcloud 27+');
		}

		$account = $this->createMock(Account::class);
		$message1 = new Message();
		$message1->setUid(1);
		$message1->setMailboxId(123);
		$message2 = new Message();
		$message2->setUid(2);
		$message2->setMailboxId(456);
		$manager = $this->createMock(IManager::class);
		$this->container->expects(self::once())
			->method('get')
			->willReturn($manager);
		$manager->expects(self::once())
			->method('getAvailableTaskTypes')
			->willReturn([FreePromptTaskType::class]);
		$imapMessage = $this->createMock(IMAPMessage::class);
		$this->mailManager->expects(self::exactly(2))
			->method('getImapMessage')
			->willReturn($imapMessage);
		$imapMessage->expects(self::exactly(2))
			->method('getPlainBody')
			->willReturn('plain');
		$manager->expects(self::once())
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
		if (!interface_exists(IManager::class)) {
			$this->markTestSkipped('Text processing APIs require Nextcloud 27+');
		}

		$account = $this->createMock(Account::class);
		$message1 = new Message();
		$message1->setUid(1);
		$message1->setMailboxId(123);
		$message2 = new Message();
		$message2->setUid(2);
		$message2->setMailboxId(456);
		$manager = $this->createMock(IManager::class);
		$this->container->expects(self::once())
			->method('get')
			->willReturn($manager);
		$manager->expects(self::once())
			->method('getAvailableTaskTypes')
			->willReturn([FreePromptTaskType::class]);
		$imapMessage = $this->createMock(IMAPMessage::class);
		$this->mailManager->expects(self::exactly(2))
			->method('getImapMessage')
			->willReturn($imapMessage);
		$imapMessage->expects(self::exactly(2))
			->method('getPlainBody')
			->willReturn('plain');
		$manager->expects(self::once())
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

	public function testSummarizeMessagesNoProvider() {
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

	public function testSummarizeMessagesContainsSummary() {
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

	public function testSummarizeMessagesIsEncrypted() {
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

	public function testSummarizeMessages() {
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
		$imapMessage->expects(self::once())
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

}
