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
use OCA\Mail\Service\AiIntegrations\DefaultPrompts;
use OCP\IAppConfig;
use OCP\IL10N;
use OCP\IUser;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\TaskProcessing\Exception\ValidationException;
use OCP\TaskProcessing\IManager as TaskProcessingManager;
use OCP\TaskProcessing\IProvider as TaskProcessingProvider;
use OCP\TaskProcessing\Task as TaskProcessingTask;
use OCP\TaskProcessing\TaskTypes\TextToText;
use OCP\TaskProcessing\TaskTypes\TextToTextSummary;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;

class AiIntegrationsServiceTest extends TestCase {

	private IAppConfig|MockObject $appConfig;
	private NullLogger|MockObject $logger;
	private AiIntegrationsService $aiIntegrationsService;
	private Cache|MockObject $cache;
	private IMAPClientFactory|MockObject $clientFactory;
	private IMailManager|MockObject $mailManager;
	private TaskProcessingManager|MockObject $taskProcessingManager;
	private TaskProcessingProvider|MockObject $taskProcessingProvider;
	private IL10N|MockObject $l10n;
	private IFactory|MockObject $l10nFactory;
	private IUserManager|MockObject $userManager;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(NullLogger::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->cache = $this->createMock(Cache::class);
		$this->clientFactory = $this->createMock(IMAPClientFactory::class);
		$this->mailManager = $this->createMock(IMailManager::class);
		$this->taskProcessingManager = $this->createMock(TaskProcessingManager::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->l10nFactory = $this->createMock(IFactory::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->aiIntegrationsService = new AiIntegrationsService(
			$this->logger,
			$this->cache,
			$this->clientFactory,
			$this->mailManager,
			$this->taskProcessingManager,
			$this->l10n,
			$this->l10nFactory,
			$this->userManager,
			$this->appConfig,
		);

		$this->taskProcessingProvider = $this->createMock(TaskProcessingProvider::class);
	}

	public function testSummarizeThreadNoBackend(): void {
		$account = new Account(new MailAccount());
		$this->taskProcessingManager->method('getAvailableTaskTypes')->willReturn([]);
		$this->expectException(ServiceException::class);
		$this->expectExceptionMessage('No language model available for summary');
		$this->aiIntegrationsService->summarizeThread($account, '', [], '');
	}
	public function testTaskProcessingExceptionIsMappedToServiceException(): void {
		$account = new Account(new MailAccount());
		$taskProcessingException = new ValidationException('Invalid task input');
		$this->taskProcessingManager
			->method('getAvailableTaskTypes')
			->willReturn([TextToTextSummary::ID => $this->taskProcessingProvider]);
		$this->cache->method('getValue')->willReturn(false);
		$this->clientFactory
			->method('getClient')
			->with($account)
			->willReturn($this->createMock(Horde_Imap_Client_Socket::class));
		$this->taskProcessingManager
			->method('runTask')
			->willThrowException($taskProcessingException);

		try {
			$this->aiIntegrationsService->summarizeThread($account, 'thread-id', [], 'user');
			self::fail('Expected task processing exception to be mapped');
		} catch (ServiceException $e) {
			self::assertSame('AI task processing failed', $e->getMessage());
			self::assertSame($taskProcessingException, $e->getPrevious());
		}
	}

	public function testSmartReplyNoBackend(): void {
		$account = new Account(new MailAccount());
		$mailbox = new Mailbox();
		$message = new Message();
		$this->taskProcessingManager
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
		$this->taskProcessingManager
			->method('getAvailableTaskTypes')
			->willReturn([TextToText::ID => $this->taskProcessingProvider]);
		$this->cache->method('getValue')->willReturn(false);
		$this->clientFactory->method('getClient')->with($account)->willReturn($this->createMock(Horde_Imap_Client_Socket::class));
		$this->mailManager->method('getImapMessage')->willReturn($imapMessage);
		$imapMessage->method('isOneClickUnsubscribe')->willReturn(false);
		$imapMessage->method('getUnsubscribeUrl')->willReturn(null);
		$fromList = new AddressList([ Address::fromRaw('personal@example.com', 'personal@example.com')]);
		$imapMessage->method('getFrom')->willReturn($fromList);
		$imapMessage->method('getPlainBody')->willReturn('This is a test message');

		$this->taskProcessingManager->expects($this->once())
			->method('runTask')
			->will($this->returnCallback(function (TaskProcessingTask $task) {
				$task->setOutput(['output' => '{"reply1":"reply1","reply2":"reply2"}']);
				return $task;
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
		$message->setId(42);
		$currentUserId = 'user';
		$cachedReplies = false;
		$cacheKey = 'smartReplies_42';
		$this->taskProcessingManager
			->method('getAvailableTaskTypes')
			->willReturn([TextToText::ID => $this->taskProcessingProvider]);
		$this->cache->expects(self::exactly(2))
			->method('getValue')
			->with($cacheKey)
			->willReturnCallback(function () use (&$cachedReplies) {
				return $cachedReplies;
			});
		$this->cache->expects(self::once())
			->method('addValue')
			->with($cacheKey, '{"reply1":"reply1","reply2":"reply2"}')
			->willReturnCallback(function (string $_key, ?string $value) use (&$cachedReplies): void {
				$cachedReplies = $value;
			});
		$this->clientFactory->method('getClient')->with($account)->willReturn($this->createMock(Horde_Imap_Client_Socket::class));
		$this->mailManager->method('getImapMessage')->willReturn($imapMessage);
		$imapMessage->method('isOneClickUnsubscribe')->willReturn(false);
		$imapMessage->method('getUnsubscribeUrl')->willReturn(null);
		$fromList = new AddressList([ Address::fromRaw('personal@example.com', 'personal@example.com')]);
		$imapMessage->method('getFrom')->willReturn($fromList);
		$imapMessage->method('getPlainBody')->willReturn('This is a test message');

		$this->taskProcessingManager->expects($this->once())
			->method('runTask')
			->will($this->returnCallback(function (TaskProcessingTask $task) {
				$task->setOutput(['output' => '```json{"reply1":"reply1","reply2":"reply2"}```']);
				return $task;
			}));

		$firstResult = $this->aiIntegrationsService->getSmartReply($account, $mailbox, $message, $currentUserId);
		$cachedResult = $this->aiIntegrationsService->getSmartReply($account, $mailbox, $message, $currentUserId);
		$expected = [
			'reply1' => 'reply1',
			'reply2' => 'reply2',
		];

		$this->assertSame($expected, $firstResult);
		$this->assertSame($expected, $cachedResult);
	}

	public function testGeneratedMessage(): void {
		$account = new Account(new MailAccount());
		$mailbox = new Mailbox();
		$message = new Message();
		$message->setUid(1);
		$imapMessage = $this->createMock(IMAPMessage::class);
		$this->mailManager->method('getImapMessage')->willReturn($imapMessage);
		$this->taskProcessingManager
			->method('getAvailableTaskTypes')
			->willReturn([TextToText::ID => $this->taskProcessingProvider]);
		$imapMessage->method('isOneClickUnsubscribe')->willReturn(true);
		$replies = $this->aiIntegrationsService->getSmartReply($account, $mailbox, $message, '');
		$this->assertEquals($replies, []);
		$imapMessage->method('isOneClickUnsubscribe')->willReturn(false);
		$imapMessage->method('getUnsubscribeUrl')->willReturn('iAmAnUnsubscribeUrl');
		$replies = $this->aiIntegrationsService->getSmartReply($account, $mailbox, $message, '');
		$this->assertEquals($replies, []);
		$imapMessage->method('isOneClickUnsubscribe')->willReturn(false);
		$imapMessage->method('getUnsubscribeUrl')->willReturn(null);
		$replies = $this->aiIntegrationsService->getSmartReply($account, $mailbox, $message, '');
		$this->assertEquals($replies, []);
	}

	public function testLlmAvailable(): void {
		$this->taskProcessingManager
			->method('getAvailableTaskTypes')
			->willReturn([
				TextToTextSummary::ID => $this->taskProcessingProvider,
				TextToText::ID => $this->taskProcessingProvider,
			]);
		$isAvailable = $this->aiIntegrationsService->isLlmAvailable(TextToTextSummary::ID);
		$this->assertTrue($isAvailable);

	}

	public function testLlmUnavailable(): void {
		$this->taskProcessingManager
			->method('getAvailableTaskTypes')
			->willReturn([TextToText::ID => $this->taskProcessingProvider]);
		$isAvailable = $this->aiIntegrationsService->isLlmAvailable(TextToTextSummary::ID);
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
		$this->appConfig->expects(self::once())
			->method('getValueString')
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
		$this->taskProcessingManager
			->method('getAvailableTaskTypes')
			->willReturn([TextToTextSummary::ID => $this->taskProcessingProvider]);

		$messageIds = [ $message1->getMessageId(),$message2->getMessageId(),$message3->getMessageId()];
		$key = $this->cache->buildUrlKey($messageIds);
		$this->cache
			->method('getValue')
			->with($key)
			->willReturn('this is a cached summary');

		$this->assertEquals('this is a cached summary', $this->aiIntegrationsService->summarizeThread($account, 'some-thread-root-id-1', $messages, 'admin'));
	}

	public function testGenerateEventDataFreePromptUnavailable(): void {
		$account = $this->createStub(Account::class);
		$message1 = new Message();
		$message2 = new Message();
		$this->taskProcessingManager->expects(self::once())
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

	public static function provideInvalidEventDataOutput(): array {
		return [
			'invalid JSON' => ['Jason'],
			'missing title' => ['{"agenda":"* Q&A"}'],
			'missing agenda' => ['{"title":"Meeting"}'],
			'null title' => ['{"title":null,"agenda":"* Q&A"}'],
			'numeric agenda' => ['{"title":"Meeting","agenda":123}'],
			'list' => ['[]'],
			'scalar' => ['"Meeting"'],
		];
	}

	/**
	 * @dataProvider provideInvalidEventDataOutput
	 */
	public function testGenerateEventDataInvalidJson(string $output): void {

		$account = $this->createStub(Account::class);
		$message1 = new Message();
		$message1->setUid(1);
		$message1->setMailboxId(123);
		$message2 = new Message();
		$message2->setUid(2);
		$message2->setMailboxId(456);
		$this->taskProcessingManager->expects(self::once())
			->method('getAvailableTaskTypes')
			->willReturn([TextToText::ID => $this->taskProcessingProvider]);
		$imapMessage = $this->createMock(IMAPMessage::class);
		$this->mailManager->expects(self::exactly(2))
			->method('getImapMessage')
			->willReturn($imapMessage);
		$imapMessage->expects(self::exactly(2))
			->method('getPlainBody')
			->willReturn('plain');
		$this->taskProcessingManager->expects(self::once())
			->method('runTask')
			->willReturnCallback(function (TaskProcessingTask $task) use ($output) {
				$task->setOutput(['output' => $output]);
				return $task;
			});

		$result = $this->aiIntegrationsService->generateEventData(
			$account,
			'thread1',
			[$message1, $message2],
			'user123',
		);

		self::assertNull($result);
	}

	public function testGenerateEventData(): void {

		$account = $this->createStub(Account::class);
		$message1 = new Message();
		$message1->setUid(1);
		$message1->setMailboxId(123);
		$message2 = new Message();
		$message2->setUid(2);
		$message2->setMailboxId(456);
		$this->taskProcessingManager->expects(self::once())
			->method('getAvailableTaskTypes')
			->willReturn([TextToText::ID => $this->taskProcessingProvider]);
		$imapMessage = $this->createMock(IMAPMessage::class);
		$this->mailManager->expects(self::exactly(2))
			->method('getImapMessage')
			->willReturn($imapMessage);
		$imapMessage->expects(self::exactly(2))
			->method('getPlainBody')
			->willReturn('plain');
		$this->taskProcessingManager->expects(self::once())
			->method('runTask')
			->willReturnCallback(function (TaskProcessingTask $task) {
				$task->setOutput(['output' => '{"title":"Meeting", "agenda":"* Q&A"}']);
				return $task;
			});

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
		$mailAccount->setUserId('user');
		$mailAccount->setEmail('user@domain.tld');
		$mailAccount->setInboundHost('127.0.0.1');
		$mailAccount->setInboundPort(993);
		$mailAccount->setInboundSslMode('ssl');
		$mailAccount->setInboundUser('user@domain.tld');
		$mailAccount->setInboundPassword('encrypted');
		$account = new Account($mailAccount);
		$message = new Message();
		$message->setSummary('Test Summary');
		$user = $this->createStub(IUser::class);

		$this->userManager->expects(self::once())
			->method('get')
			->with($account->getUserId())
			->willReturn($user);

		$this->l10nFactory->expects(self::once())
			->method('getUserLanguage')
			->with($user)
			->willReturn('en');

		$this->taskProcessingManager->expects(self::once())
			->method('getAvailableTaskTypes')
			->willReturn([TextToText::ID => $this->taskProcessingProvider]);
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
		$user = $this->createStub(IUser::class);

		$this->userManager->expects(self::once())
			->method('get')
			->with($account->getUserId())
			->willReturn($user);

		$this->l10nFactory->expects(self::once())
			->method('getUserLanguage')
			->with($user)
			->willReturn('en');

		$imapClient = $this->clientFactory->getClient($account);

		$imapMessage = $this->createMock(IMAPMessage::class);
		$imapMessage->expects(self::never())
			->method('getPlainBody')
			->willReturn('This is a test message');
		$imapMessage->expects(self::once())
			->method('isEncrypted')->willReturn(true);

		$this->taskProcessingManager->expects(self::once())
			->method('getAvailableTaskTypes')
			->willReturn([TextToText::ID => $this->taskProcessingProvider]);
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
		$user = $this->createStub(IUser::class);

		$this->userManager->expects(self::once())
			->method('get')
			->with($account->getUserId())
			->willReturn($user);

		$this->l10nFactory->expects(self::once())
			->method('getUserLanguage')
			->with($user)
			->willReturn('de_DE');

		$imapClient = $this->clientFactory->getClient($account);

		$imapMessage = $this->createMock(IMAPMessage::class);
		$imapMessage->expects(self::atMost(2))
			->method('getPlainBody')
			->willReturn('This is a test message');
		$imapMessage->expects(self::once())
			->method('isEncrypted')->willReturn(false);

		$this->taskProcessingManager->expects(self::once())
			->method('getAvailableTaskTypes')
			->willReturn([TextToText::ID => $this->taskProcessingProvider]);
		$this->taskProcessingManager->expects(self::once())
			->method('scheduleTask')
			->willReturnCallback(function (TaskProcessingTask $task): void {
				self::assertSame(TextToText::ID, $task->getTaskTypeId());
				self::assertSame(1024, $task->getInput()['max_tokens']);
				self::assertSame(
					sprintf(DefaultPrompts::SUMMARIZE_MESSAGE, 'de', 'This is a test message'),
					$task->getInput()['input'],
				);
			});

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

		$this->taskProcessingManager
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
		$this->taskProcessingManager
			->method('getAvailableTaskTypes')
			->willReturn([TextToText::ID => $this->taskProcessingProvider]);
		$this->cache->method('getValue')->willReturn(false);
		$this->clientFactory->method('getClient')->with($account)->willReturn($this->createMock(Horde_Imap_Client_Socket::class));
		$this->mailManager->method('getImapMessage')->willReturn($imapMessage);
		$imapMessage->method('isOneClickUnsubscribe')->willReturn(false);
		$imapMessage->method('getUnsubscribeUrl')->willReturn(null);
		$fromList = new AddressList([ Address::fromRaw('personal@example.com', 'personal@example.com')]);
		$imapMessage->method('getFrom')->willReturn($fromList);
		$imapMessage->method('getPlainBody')->willReturn('Ceci n\'est pas un message');

		$this->taskProcessingManager->expects($this->once())
			->method('runTask')
			->will($this->returnCallback(function (TaskProcessingTask $task) {
				$task->setOutput(['output' => '{"needsTranslation": true}, the message is in French that is the value returned is true ']);
				return $task;
			}));

		$result = $this->aiIntegrationsService->requiresTranslation($account, $mailbox, $message, $currentUserId);

		$this->assertTrue($result);
	}

	public static function provideUnusableTranslationOutput(): array {
		return [
			'missing output' => [null],
			'empty output' => [''],
			'whitespace-only output' => ['   '],
		];
	}

	/**
	 * @dataProvider provideUnusableTranslationOutput
	 */
	public function testRequiresTranslationRejectsUnusableOutput(?string $output): void {
		$account = new Account(new MailAccount());
		$mailbox = new Mailbox();
		$message = new Message();
		$message->setId(123);
		$message->setUid(1);
		$currentUserId = 'user';
		$imapMessage = $this->createMock(IMAPMessage::class);

		$this->taskProcessingManager
			->method('getAvailableTaskTypes')
			->willReturn([TextToText::ID => $this->taskProcessingProvider]);
		$this->cache->method('getValue')->willReturn(false);
		$this->cache->expects(self::never())->method('addValue');
		$this->l10n->method('getLanguageCode')->willReturn('en_US');
		$this->clientFactory->method('getClient')->with($account)->willReturn($this->createMock(Horde_Imap_Client_Socket::class));
		$this->mailManager->method('getImapMessage')->willReturn($imapMessage);
		$imapMessage->method('isOneClickUnsubscribe')->willReturn(false);
		$imapMessage->method('getUnsubscribeUrl')->willReturn(null);
		$imapMessage->method('getFrom')->willReturn(new AddressList([
			Address::fromRaw('personal@example.com', 'personal@example.com'),
		]));
		$imapMessage->method('getPlainBody')->willReturn('Ceci n\'est pas un message');

		$this->taskProcessingManager->expects(self::once())
			->method('runTask')
			->willReturnCallback(function (TaskProcessingTask $task) use ($output) {
				$task->setOutput($output === null ? null : ['output' => $output]);
				$task->setStatus($output === null ? TaskProcessingTask::STATUS_FAILED : TaskProcessingTask::STATUS_SUCCESSFUL);
				$task->setErrorMessage($output === null ? 'Provider timeout' : null);
				return $task;
			});
		$this->logger->expects(self::once())
			->method('warning')
			->with(
				'Translation check task returned no usable output',
				self::arrayHasKey('status'),
			);

		$this->expectException(ServiceException::class);
		$this->expectExceptionMessage('Translation check task returned no usable output');

		$this->aiIntegrationsService->requiresTranslation($account, $mailbox, $message, $currentUserId);
	}

	public function testRequiresFollowUp(): void {
		$account = new Account(new MailAccount());
		$mailbox = new Mailbox();
		$message = new Message();
		$message->setUid(1);
		$currentUserId = 'user';
		$imapMessage = $this->createMock(IMAPMessage::class);

		$this->taskProcessingManager
			->method('getAvailableTaskTypes')
			->willReturn([TextToText::ID => $this->taskProcessingProvider]);
		$this->clientFactory->method('getClient')->with($account)->willReturn($this->createMock(Horde_Imap_Client_Socket::class));
		$this->mailManager->method('getImapMessage')->willReturn($imapMessage);
		$imapMessage->method('isOneClickUnsubscribe')->willReturn(false);
		$imapMessage->method('getUnsubscribeUrl')->willReturn(null);
		$imapMessage->method('getFrom')->willReturn(new AddressList([
			Address::fromRaw('personal@example.com', 'personal@example.com'),
		]));
		$imapMessage->method('getPlainBody')->willReturn('Can you send the report?');
		$this->taskProcessingManager->expects(self::once())
			->method('runTask')
			->willReturnCallback(function (TaskProcessingTask $task) {
				$task->setOutput(['output' => 'The result is {"expectsReply": true}']);
				return $task;
			});

		$result = $this->aiIntegrationsService->requiresFollowUp($account, $mailbox, $message, $currentUserId);

		$this->assertTrue($result);
	}

	public static function provideUnusableFollowUpOutput(): array {
		return [
			'failed task' => ['{"expectsReply": false}', TaskProcessingTask::STATUS_FAILED],
			'missing output' => [null, TaskProcessingTask::STATUS_SUCCESSFUL],
			'empty output' => ['', TaskProcessingTask::STATUS_SUCCESSFUL],
			'whitespace-only output' => ['   ', TaskProcessingTask::STATUS_SUCCESSFUL],
		];
	}

	/**
	 * @dataProvider provideUnusableFollowUpOutput
	 */
	public function testRequiresFollowUpRejectsUnusableOutput(?string $output, int $status): void {
		$account = new Account(new MailAccount());
		$mailbox = new Mailbox();
		$message = new Message();
		$message->setUid(1);
		$currentUserId = 'user';
		$imapMessage = $this->createMock(IMAPMessage::class);

		$this->taskProcessingManager
			->method('getAvailableTaskTypes')
			->willReturn([TextToText::ID => $this->taskProcessingProvider]);
		$this->clientFactory->method('getClient')->with($account)->willReturn($this->createMock(Horde_Imap_Client_Socket::class));
		$this->mailManager->method('getImapMessage')->willReturn($imapMessage);
		$imapMessage->method('isOneClickUnsubscribe')->willReturn(false);
		$imapMessage->method('getUnsubscribeUrl')->willReturn(null);
		$imapMessage->method('getFrom')->willReturn(new AddressList([
			Address::fromRaw('personal@example.com', 'personal@example.com'),
		]));
		$imapMessage->method('getPlainBody')->willReturn('Can you send the report?');
		$this->taskProcessingManager->expects(self::once())
			->method('runTask')
			->willReturnCallback(function (TaskProcessingTask $task) use ($output, $status) {
				$task->setOutput($output === null ? null : ['output' => $output]);
				$task->setStatus($status);
				$task->setErrorMessage($status === TaskProcessingTask::STATUS_FAILED ? 'Provider timeout' : null);
				return $task;
			});
		$this->logger->expects(self::once())
			->method('warning')
			->with(
				'Follow-up classification task returned no usable output',
				self::arrayHasKey('status'),
			);

		$this->expectException(ServiceException::class);
		$this->expectExceptionMessage('Follow-up classification task returned no usable output');

		$this->aiIntegrationsService->requiresFollowUp($account, $mailbox, $message, $currentUserId);
	}

}
