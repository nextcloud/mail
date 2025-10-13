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
use OCP\IL10N;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\TaskProcessing\IManager as TaskProcessingManager;
use OCP\TaskProcessing\Task as TaskProcessingTask;
use OCP\TaskProcessing\TaskTypes\TextToText;
use OCP\TextProcessing\FreePromptTaskType;
use OCP\TextProcessing\IManager as TextProcessingManager;
use OCP\TextProcessing\SummaryTaskType;
use OCP\TextProcessing\Task as TextProcessingTask;
use Psr\Log\LoggerInterface;

use function array_map;
use function implode;
use function in_array;
use function json_decode;

class AiIntegrationsService {

	private const EVENT_DATA_PROMPT_PREAMBLE = <<<PROMPT
I am scheduling an event based on an email thread and need an event title and agenda. Provide the result as JSON with keys for "title" and "agenda". For example ```{ "title": "Project kick-off meeting", "agenda": "* Introduction\\n* Project goals\\n* Next steps" }```.

The email contents are:

PROMPT;

	public function __construct(
		private LoggerInterface $logger,
		private IConfig $config,
		private Cache $cache,
		private IMAPClientFactory $clientFactory,
		private IMailManager $mailManager,
		private TaskProcessingManager $taskProcessingManager,
		private TextProcessingManager $textProcessingManager,
		private IL10N $l,
		private IFactory $l10nFactory,
		private IUserManager $userManager,
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
				$prompt = "You are tasked with formulating a helpful summary of a email message. \r\n"
						  . 'The summary should be in the language of this language code ' . $language . ". \r\n"
						  . "The summary should be less than 160 characters. \r\n"
						  . "Output *ONLY* the summary itself, leave out any introduction. \r\n"
						  . "Here is the ***E-MAIL*** for which you must generate a helpful summary: \r\n"
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
		if (in_array(SummaryTaskType::class, $this->textProcessingManager->getAvailableTaskTypes(), true)) {
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
			$summaryTask = new TextProcessingTask(SummaryTaskType::class, $taskPrompt, 'mail', $currentUserId, $threadId);
			$this->textProcessingManager->runTask($summaryTask);
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
		if (!in_array(FreePromptTaskType::class, $this->textProcessingManager->getAvailableTaskTypes(), true)) {
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

		$task = new TextProcessingTask(
			FreePromptTaskType::class,
			self::EVENT_DATA_PROMPT_PREAMBLE . implode("\n\n---\n\n", $messageBodies),
			'mail',
			$currentUserId,
			"event_data_$threadId",
		);
		$result = $this->textProcessingManager->runTask($task);
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
		if (in_array(FreePromptTaskType::class, $this->textProcessingManager->getAvailableTaskTypes(), true)) {
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
			$prompt = "You are tasked with formulating helpful replies or reply templates to e-mails provided that have been sent to me. If you don't know some relevant information for answering the e-mails (like my schedule) leave blanks in the text that can later be filled by me. You must write the replies from my point of view as replies to the original sender of the provided e-mail!

			Formulate two extremely succinct reply suggestions to the provided ***E-MAIL***. Please, do not invent any context for the replies but, rather, leave blanks for me to fill in with relevant information where necessary. Provide the output formatted as valid JSON with the keys 'reply1' and 'reply2' for the reply suggestions.

			Each suggestion must be of 25 characters or less.

			Here is the ***E-MAIL*** for which you must suggest the replies to:

			***START_OF_E-MAIL***" . $messageBody . "

			***END_OF_E-MAIL***

			Please, output *ONLY* a valid JSON string with the keys 'reply1' and 'reply2' for the reply suggestions. Leave out any other text besides the JSON! Be extremely succinct and write the replies from my point of view.
			 ";
			$task = new TextProcessingTask(FreePromptTaskType::class, $prompt, 'mail,', $currentUserId);
			$this->textProcessingManager->runTask($task);
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
		if (!in_array(FreePromptTaskType::class, $this->textProcessingManager->getAvailableTaskTypes(), true)) {
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

		$prompt = "Consider the following TypeScript function prototype:
---
/**
 * This function takes in an email text and returns a boolean indicating whether the email author expects a response.
 *
 * @param emailText - string with the email text
 * @returns boolean true if the email expects a reply, false if not
 */
declare function doesEmailExpectReply(emailText: string): Promise<boolean>;
---
Tell me what the function outputs for the following parameters.

emailText: \"$messageBody\"
The JSON output should be in the form: {\"expectsReply\": true}
Never return null or undefined.";
		$task = new TextProcessingTask(FreePromptTaskType::class, $prompt, Application::APP_ID, $currentUserId);

		$this->textProcessingManager->runTask($task);

		// Can't use json_decode() here because the output contains additional garbage
		return preg_match('/{\s*"expectsReply"\s*:\s*true\s*}/i', $task->getOutput()) === 1;
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
		if (!in_array(FreePromptTaskType::class, $this->textProcessingManager->getAvailableTaskTypes(), true)) {
			$this->logger->info('No language model available for checking translation needs');
			return null;
		}

		$language = explode('_', $this->l->getLanguageCode())[0];
		$cachedValue = $this->cache->getValue('needsTranslation_' . $language . $message->getId());
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

		$prompt = "Consider the following TypeScript function prototype:
---
/**
 * This function takes in an email text and returns a boolean indicating whether the email needs translation from a specific language.
 *
 * @param emailText - string with the email text
 * @param language - the language code to check against (e.g., 'en', 'de', etc.)
 * @returns boolean true if the email is written in a different language than the one specified and needs translation, false if it is written in the specified language.
 * only return true if whole sentences are written in a different language, not just a word or two.
 */
declare function isEmailWrittenInLanguage(emailText: string, language: string): Promise<boolean>;
---
Tell me what the function outputs for the following parameters.

emailText: \"$messageBody\"
language: \"$language\"
The JSON output should be in the form: {\"needsTranslation\": true}
Never return null or undefined.";
		$task = new TextProcessingTask(FreePromptTaskType::class, $prompt, Application::APP_ID, $currentUserId);

		$this->textProcessingManager->runTask($task);
		$output = $task->getOutput();
		if ($output === null) {
			throw new ServiceException('Task output is null, possibly due to an error in the task processing', [
				'messageId' => $message->getId(),
				'language' => $language,
				'output' => $output,
			]);
		}
		// Can't use json_decode() here because the output contains additional garbage
		$result = preg_match('/{\s*"needsTranslation"\s*:\s*true\s*}/i', $output) === 1;
		$this->cache->addValue('needsTranslation_' . $language . $message->getId(), $result ? 'true' : 'false');
		return $result;
	}

	public function isLlmAvailable(string $taskType): bool {
		return in_array($taskType, $this->textProcessingManager->getAvailableTaskTypes(), true);
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
