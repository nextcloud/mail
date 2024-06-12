<?php

declare(strict_types=1);

/**
 * @author Hamza Mahjoubi <hamzamahjoubi22@proton.me>
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

namespace OCA\Mail\Service\AiIntegrations;

use JsonException;
use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\Message;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\Model\EventData;
use OCA\Mail\Model\IMAPMessage;
use OCP\TextProcessing\FreePromptTaskType;
use OCP\TextProcessing\IManager;
use OCP\TextProcessing\SummaryTaskType;
use OCP\TextProcessing\Task;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use function array_map;
use function implode;
use function in_array;
use function json_decode;

class AiIntegrationsService {

	/** @var ContainerInterface */
	private ContainerInterface $container;

	/** @var Cache */
	private Cache $cache;

	/** @var IMAPClientFactory */
	private IMAPClientFactory $clientFactory;

	/** @var IMailManager  */
	private IMailManager $mailManager;

	private const EVENT_DATA_PROMPT_PREAMBLE = <<<PROMPT
I am scheduling an event based on an email thread and need an event title and agenda. Provide the result as JSON with keys for "title" and "agenda". For example ```{ "title": "Project kick-off meeting", "agenda": "* Introduction\\n* Project goals\\n* Next steps" }```.

The email contents are:

PROMPT;

	public function __construct(ContainerInterface $container, Cache $cache, IMAPClientFactory $clientFactory, IMailManager $mailManager) {
		$this->container = $container;
		$this->cache = $cache;
		$this->clientFactory = $clientFactory;
		$this->mailManager = $mailManager;
	}
	/**
	 * @param Account $account
	 * @param string $threadId
	 * @param array $messages
	 * @param string $currentUserId
	 *
	 * @return  null|string
	 *
	 * @throws ServiceException
	 */
	public function summarizeThread(Account $account, string $threadId, array $messages, string $currentUserId): null|string {
		try {
			$manager = $this->container->get(IManager::class);
		} catch (\Throwable $e) {
			throw new ServiceException('Text processing is not available in your current Nextcloud version', 0, $e);
		}
		if(in_array(SummaryTaskType::class, $manager->getAvailableTaskTypes(), true)) {
			$messageIds = array_map(function ($message) {
				return $message->getMessageId();
			}, $messages);
			$cachedSummary = $this->cache->getValue($this->cache->buildUrlKey($messageIds));
			if($cachedSummary) {
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
			$summaryTask = new Task(SummaryTaskType::class, $taskPrompt, "mail", $currentUserId, $threadId);
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
			self::EVENT_DATA_PROMPT_PREAMBLE . implode("\n\n---\n\n", $messageBodies),
			"mail",
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
			$cachedReplies = $this->cache->getValue('smartReplies_'.$message->getId());
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

			***START_OF_E-MAIL***".$messageBody."

			***END_OF_E-MAIL***

			Please, output *ONLY* a valid JSON string with the keys 'reply1' and 'reply2' for the reply suggestions. Leave out any other text besides the JSON! Be extremely succinct and write the replies from my point of view.
			 ";
			$task = new Task(FreePromptTaskType::class, $prompt, 'mail,', $currentUserId);
			$manager->runTask($task);
			$replies = $task->getOutput();
			try {
				$decoded = json_decode($replies, true, 512, JSON_THROW_ON_ERROR);
				$this->cache->addValue('smartReplies_'.$message->getId(), $replies);
				return $decoded;
			} catch (JsonException $e) {
				throw new ServiceException('Failed to decode smart replies JSON output', $e);
			}
		} else {
			throw new ServiceException('No language model available for smart replies');
		}

	}

	public function isLlmAvailable(string $taskType): bool {
		try {
			$manager = $this->container->get(IManager::class);
		} catch (\Throwable $e) {
			return false;
		}
		return in_array($taskType, $manager->getAvailableTaskTypes(), true);
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

		if($senderAddress !== null) {
			foreach ($commonPatterns as $pattern) {
				if (stripos($senderAddress, $pattern) !== false) {
					return false;
				}
			}
		}
		return true;
	}


}
