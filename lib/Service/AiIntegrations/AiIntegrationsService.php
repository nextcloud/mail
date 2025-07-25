<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Service\AiIntegrations;

use JsonException;
use OCA\Mail\Account;
use OCA\Mail\AppInfo\Application;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\Message;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\Model\EventData;
use OCA\Mail\Model\IMAPMessage;
use OCP\IConfig;
use OCP\TaskProcessing\IManager as TaskProcessingManager;
use OCP\TaskProcessing\Task as TaskProcessingTask;
use OCP\TaskProcessing\TaskTypes\TextToText;
use OCP\TextProcessing\FreePromptTaskType;
use OCP\TextProcessing\IManager;
use OCP\TextProcessing\SummaryTaskType;
use OCP\TextProcessing\Task;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

use function array_map;
use function implode;
use function in_array;
use function json_decode;

class AiIntegrationsService {

	public function __construct(
		private ContainerInterface $container,
		private LoggerInterface $logger,
		private IConfig $config,
		private Cache $cache,
		private IMAPClientFactory $clientFactory,
		private IMailManager $mailManager,
		private TaskProcessingManager $taskProcessingManager,
		private AiIntegrationsPromptsService $promptsService,
	) {
	}

	/**
	 * generates summary for each message
	 *
	 * @param Account $account
	 * @param array<Message> $messages
	 *
	 * @return void
	 */
	public function summarizeMessages(Account $account, array $messages): void {
		$availableTaskTypes = $this->taskProcessingManager->getAvailableTaskTypes();
		if (!isset($availableTaskTypes[TextToText::ID])) {
			$this->logger->info('No text summary provider available');
			return;
		}

		$client = $this->clientFactory->getClient($account);
		try {
			foreach ($messages as $entry) {
				if (mb_strlen((string)$entry->getSummary()) !== 0) {
					continue;
				}
				// retrieve full message from server
				$userId = $account->getUserId();
				$mailboxId = $entry->getMailboxId();
				$messageLocalId = $entry->getId();
				$messageRemoteId = $entry->getUid();
				$mailbox = $this->mailManager->getMailbox($userId, $mailboxId);
				$message = $this->mailManager->getImapMessage(
					$client,
					$account,
					$mailbox,
					$messageRemoteId,
					true
				);
				// skip message if it is encrypted or empty
				if ($message->isEncrypted() || empty(trim($message->getPlainBody()))) {
					continue;
				}
				// construct prompt and task
				$messageBody = $message->getPlainBody();
				$prompt = $this->promptsService->getSummarizeEmailPrompt()
						  . "***START_OF_E-MAIL***\r\n$messageBody\r\n***END_OF_E-MAIL***\r\n";
				$task = new TaskProcessingTask(
					TextToText::ID,
					[
						'max_tokens' => 1024,
						'input' => $prompt,
					],
					Application::APP_ID,
					$userId,
					'message:' . (string)$messageLocalId
				);
				$this->taskProcessingManager->scheduleTask($task);
			}
		} finally {
			$client->logout();
		}
	}

	/**
	 * @param Account $account
	 * @param string $threadId
	 * @param array $messages
	 * @param string $currentUserId
	 *
	 * @return null|string
	 *
	 * @throws ServiceException
	 */
	public function summarizeThread(Account $account, string $threadId, array $messages, string $currentUserId): ?string {
		try {
			$manager = $this->container->get(IManager::class);
		} catch (\Throwable $e) {
			throw new ServiceException('Text processing is not available in your current Nextcloud version', 0, $e);
		}
		if (in_array(SummaryTaskType::class, $manager->getAvailableTaskTypes(), true)) {
			$messageIds = array_map(function ($message) {
				return $message->getMessageId();
			}, $messages);
			$cachedSummary = $this->cache->getValue($this->cache->buildUrlKey($messageIds));
			if ($cachedSummary) {
				return $cachedSummary;
			}
			$client = $this->clientFactory->getClient($account);
			try {
				$messagesBodies = array_map(function ($message) use ($client, $account, $currentUserId) {
					$mailbox = $this->mailManager->getMailbox($currentUserId, $message->getMailboxId());
					$imapMessage = $this->mailManager->getImapMessage(
						$client,
						$account,
						$mailbox,
						$message->getUid(), true
					);
					return $imapMessage->getPlainBody();
				}, $messages);

			} finally {
				$client->logout();
			}

			$taskPrompt = implode("\n", $messagesBodies);
			$summaryTask = new Task(SummaryTaskType::class, $taskPrompt, 'mail', $currentUserId, $threadId);
			$manager->runTask($summaryTask);
			$summary = $summaryTask->getOutput();

			$this->cache->addValue($this->cache->buildUrlKey($messageIds), $summary);

			return $summary;
		} else {
			throw new ServiceException('No language model available for summary');
		}
	}

	/**
	 * @param Message[] $messages
	 */
	public function generateEventData(Account $account, string $threadId, array $messages, string $currentUserId): ?EventData {
		try {
			/** @var IManager $manager */
			$manager = $this->container->get(IManager::class);
		} catch (ContainerExceptionInterface $e) {
			return null;
		}
		if (!in_array(FreePromptTaskType::class, $manager->getAvailableTaskTypes(), true)) {
			return null;
		}
		$client = $this->clientFactory->getClient($account);
		try {
			$messageBodies = array_map(function ($message) use ($client, $account, $currentUserId) {
				$mailbox = $this->mailManager->getMailbox($currentUserId, $message->getMailboxId());
				$imapMessage = $this->mailManager->getImapMessage(
					$client,
					$account,
					$mailbox,
					$message->getUid(), true
				);
				return $imapMessage->getPlainBody();
			}, $messages);
		} finally {
			$client->logout();
		}

		$task = new Task(
			FreePromptTaskType::class,
			$this->promptsService->getEventDataPromptPreamble() . implode("\n\n---\n\n", $messageBodies),
			'mail',
			$currentUserId,
			"event_data_$threadId",
		);
		$result = $manager->runTask($task);
		try {
			$decoded = json_decode($result, true, 512, JSON_THROW_ON_ERROR);
			return new EventData($decoded['title'], $decoded['agenda']);
		} catch (JsonException $e) {
			return null;
		}
	}

	/**
	 * @return ?string[]
	 * @throws ServiceException
	 */
	public function getSmartReply(Account $account, Mailbox $mailbox, Message $message, string $currentUserId): ?array {
		try {
			$manager = $this->container->get(IManager::class);
		} catch (\Throwable $e) {
			throw new ServiceException('Text processing is not available in your current Nextcloud version', 0, $e);
		}
		if (in_array(FreePromptTaskType::class, $manager->getAvailableTaskTypes(), true)) {
			$cachedReplies = $this->cache->getValue('smartReplies_' . $message->getId());
			if ($cachedReplies) {
				return json_decode($cachedReplies, true, 512);
			}
			$client = $this->clientFactory->getClient($account);
			try {
				$imapMessage = $this->mailManager->getImapMessage(
					$client,
					$account,
					$mailbox,
					$message->getUid(), true
				);
				if (!$this->isPersonalEmail($imapMessage)) {
					return [];
				}
				$messageBody = $imapMessage->getPlainBody();

			} finally {
				$client->logout();
			}
			$prompt = $this->promptsService->getSmartReplyPrompt($messageBody);
			$task = new Task(FreePromptTaskType::class, $prompt, 'mail,', $currentUserId);
			$manager->runTask($task);
			$replies = $task->getOutput();
			try {
				$cleaned = preg_replace('/^```json\s*|\s*```$/', '', trim($replies));
				$decoded = json_decode($cleaned, true, 512, JSON_THROW_ON_ERROR);
				$this->cache->addValue('smartReplies_' . $message->getId(), $replies);
				return $decoded;
			} catch (JsonException $e) {
				throw new ServiceException('Failed to decode smart replies JSON output', previous: $e);
			}
		} else {
			throw new ServiceException('No language model available for smart replies');
		}
	}

	/**
	 * Analyze whether a sender of an email expects a reply based on the email's body.
	 *
	 * @throws ServiceException
	 */
	public function requiresFollowUp(
		Account $account,
		Mailbox $mailbox,
		Message $message,
		string $currentUserId,
	): bool {
		try {
			$manager = $this->container->get(IManager::class);
		} catch (ContainerExceptionInterface $e) {
			throw new ServiceException(
				'Text processing is not available in your current Nextcloud version',
				0,
				$e,
			);
		}

		if (!in_array(FreePromptTaskType::class, $manager->getAvailableTaskTypes(), true)) {
			throw new ServiceException('No language model available for smart replies');
		}

		$client = $this->clientFactory->getClient($account);
		try {
			$imapMessage = $this->mailManager->getImapMessage(
				$client,
				$account,
				$mailbox,
				$message->getUid(),
				true,
			);
		} finally {
			$client->logout();
		}

		if (!$this->isPersonalEmail($imapMessage)) {
			return false;
		}

		$messageBody = $imapMessage->getPlainBody();
		$messageBody = str_replace('"', '\"', $messageBody);

		$prompt = $this->promptsService->getRequiresFollowupPrompt($messageBody);
		$task = new Task(FreePromptTaskType::class, $prompt, Application::APP_ID, $currentUserId);

		$manager->runTask($task);

		// Can't use json_decode() here because the output contains additional garbage
		return preg_match('/{\s*"expectsReply"\s*:\s*true\s*}/i', $task->getOutput()) === 1;
	}

	public function isLlmAvailable(string $taskType): bool {
		try {
			$manager = $this->container->get(IManager::class);
		} catch (\Throwable $e) {
			return false;
		}
		return in_array($taskType, $manager->getAvailableTaskTypes(), true);
	}

	public function isTaskAvailable(string $taskName): bool {
		$availableTasks = $this->taskProcessingManager->getAvailableTaskTypes();
		return array_key_exists($taskName, $availableTasks);
	}

	/**
	 * Whether the llm_processing admin setting is enabled globally on this instance.
	 */
	public function isLlmProcessingEnabled(): bool {
		return $this->config->getAppValue(Application::APP_ID, 'llm_processing', 'no') === 'yes';
	}

	private function isPersonalEmail(IMAPMessage $imapMessage): bool {

		if ($imapMessage->isOneClickUnsubscribe() || $imapMessage->getUnsubscribeUrl() !== null) {
			return false;
		}

		$commonPatterns = [
			'noreply', 'no-reply', 'notification', 'donotreply', 'donot-reply','noreply-', 'do-not-reply',
			'automated', 'donotreply-', 'noreply.', 'noreply_', 'do_not_reply', 'no_reply', 'no-reply',
			'automated-', 'do_not_reply', 'noreply+'
		];

		$senderAddress = $imapMessage->getFrom()->first()?->getEmail();

		if ($senderAddress !== null) {
			foreach ($commonPatterns as $pattern) {
				if (stripos($senderAddress, $pattern) !== false) {
					return false;
				}
			}
		}
		return true;
	}


}
