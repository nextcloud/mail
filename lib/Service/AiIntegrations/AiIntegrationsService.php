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
use OCP\IAppConfig;
use OCP\IL10N;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\TaskProcessing\IManager as TaskProcessingManager;
use OCP\TaskProcessing\Task as TaskProcessingTask;
use OCP\TaskProcessing\TaskTypes\TextToText;
use OCP\TaskProcessing\TaskTypes\TextToTextSummary;
use Psr\Log\LoggerInterface;

use function array_map;
use function implode;
use function json_decode;

class AiIntegrationsService {

	public function __construct(
		private LoggerInterface $logger,
		private Cache $cache,
		private IMAPClientFactory $clientFactory,
		private IMailManager $mailManager,
		private TaskProcessingManager $taskProcessingManager,
		private IL10N $l,
		private IFactory $l10nFactory,
		private IUserManager $userManager,
		private IAppConfig $appConfig,
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
		$user = $this->userManager->get($account->getUserId());
		$language = explode('_', $this->l10nFactory->getUserLanguage($user))[0];
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
				$prompt = sprintf(DefaultPrompts::SUMMARIZE_MESSAGE, $language, $messageBody);
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
		if (isset($this->taskProcessingManager->getAvailableTaskTypes()[TextToTextSummary::ID])) {
			$messageIds = array_map(fn ($message) => $message->getMessageId(), $messages);
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
			$summaryTask = new TaskProcessingTask(
				TextToTextSummary::ID,
				['input' => $taskPrompt],
				Application::APP_ID,
				$currentUserId,
				$threadId,
			);
			$this->taskProcessingManager->runTask($summaryTask);
			$output = $summaryTask->getOutput()['output'] ?? null;
			$summary = $output !== null ? (string)$output : null;

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
		if (!isset($this->taskProcessingManager->getAvailableTaskTypes()[TextToText::ID])) {
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

		$task = new TaskProcessingTask(
			TextToText::ID,
			['input' => DefaultPrompts::EVENT_DATA_PREAMBLE . implode("\n\n---\n\n", $messageBodies)],
			Application::APP_ID,
			$currentUserId,
			"event_data_$threadId",
		);
		$this->taskProcessingManager->runTask($task);
		$result = (string)($task->getOutput()['output'] ?? '');
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
		if (isset($this->taskProcessingManager->getAvailableTaskTypes()[TextToText::ID])) {
			$cachedReplies = $this->cache->getValue("smartReplies_{$message->getId()}");
			if ($cachedReplies) {
				try {
					return json_decode($cachedReplies, true, 512, JSON_THROW_ON_ERROR);
				} catch (JsonException $e) {
					$this->cache->remove('smartReplies_' . $message->getId());
					throw new ServiceException('Failed to decode smart replies JSON output', previous: $e);
				}
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
			$prompt = sprintf(DefaultPrompts::SMART_REPLY, $messageBody);
			$task = new TaskProcessingTask(TextToText::ID, ['input' => $prompt], Application::APP_ID, $currentUserId);
			$this->taskProcessingManager->runTask($task);
			$replies = (string)($task->getOutput()['output'] ?? '');
			try {
				$cleaned = preg_replace('/^```json\s*|\s*```$/', '', trim($replies));
				$decoded = json_decode($cleaned, true, 512, JSON_THROW_ON_ERROR);
				$this->cache->addValue("smartReplies_{$message->getId()}", $replies);
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
		if (!isset($this->taskProcessingManager->getAvailableTaskTypes()[TextToText::ID])) {
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

		$prompt = sprintf(DefaultPrompts::REQUIRES_FOLLOW_UP, $messageBody);
		$task = new TaskProcessingTask(TextToText::ID, ['input' => $prompt], Application::APP_ID, $currentUserId);

		$this->taskProcessingManager->runTask($task);

		// Can't use json_decode() here because the output contains additional garbage
		return preg_match('/{\s*"expectsReply"\s*:\s*true\s*}/i', (string)($task->getOutput()['output'] ?? '')) === 1;
	}

	/**
	 * Analyze whether an email is written in a specific language.
	 *
	 * @throws ServiceException
	 */
	public function requiresTranslation(
		Account $account,
		Mailbox $mailbox,
		Message $message,
		string $currentUserId,
	): ?bool {
		if (!isset($this->taskProcessingManager->getAvailableTaskTypes()[TextToText::ID])) {
			$this->logger->info('No language model available for checking translation needs');
			return null;
		}

		$language = explode('_', $this->l->getLanguageCode())[0];
		$messageId = $message->getId();
		$cachedValue = $this->cache->getValue("needsTranslation_{$language}{$messageId}");
		if ($cachedValue) {
			return  $cachedValue === 'true' ? true : false;
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

		$prompt = sprintf(DefaultPrompts::REQUIRES_TRANSLATION, $messageBody, $language);
		$task = new TaskProcessingTask(TextToText::ID, ['input' => $prompt], Application::APP_ID, $currentUserId);

		$this->taskProcessingManager->runTask($task);
		$output = $task->getOutput()['output'] ?? null;
		$output = $output !== null ? (string)$output : null;
		if ($output === null) {
			throw new ServiceException('Task output is null, possibly due to an error in the task processing', [
				'messageId' => $message->getId(),
				'language' => $language,
				'output' => $output,
			]);
		}
		// Can't use json_decode() here because the output contains additional garbage
		$result = preg_match('/{\s*"needsTranslation"\s*:\s*true\s*}/i', $output) === 1;
		$this->cache->addValue("needsTranslation_{$language}{$messageId}", $result ? 'true' : 'false');
		return $result;
	}

	public function isLlmAvailable(string $taskType): bool {
		return array_key_exists($taskType, $this->taskProcessingManager->getAvailableTaskTypes());
	}

	public function isTaskAvailable(string $taskName): bool {
		$availableTasks = $this->taskProcessingManager->getAvailableTaskTypes();
		return array_key_exists($taskName, $availableTasks);
	}

	/**
	 * Whether the llm_processing admin setting is enabled globally on this instance.
	 */
	public function isLlmProcessingEnabled(): bool {
		return $this->appConfig->getValueString(Application::APP_ID, 'llm_processing', 'no') === 'yes';
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
