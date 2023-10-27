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

use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCP\TextProcessing\IManager;
use OCP\TextProcessing\SummaryTaskType;
use OCP\TextProcessing\Task;
use Psr\Container\ContainerInterface;
use function array_map;

class AiIntegrationsService {

	/** @var ContainerInterface */
	private ContainerInterface $container;

	/** @var Cache */
	private Cache $cache;

	/** @var IMAPClientFactory */
	private IMAPClientFactory $clientFactory;

	/** @var IMailManager  */
	private IMailManager $mailManager;


	public function __construct(ContainerInterface $container, Cache $cache, IMAPClientFactory $clientFactory, IMailManager $mailManager) {
		$this->container = $container;
		$this->cache = $cache;
		$this->clientFactory = $clientFactory;
		$this->mailManager = $mailManager;
	}
	/**
	 * @param string $threadId
	 * @param array $messages
	 * @param string $currentUserId
	 *
	 * @return  null|string
	 *
	 * @throws ServiceException
	 */
	public function summarizeThread(Account $account, Mailbox $mailbox, $threadId, array $messages, string $currentUserId): null|string {
		try {
			$manager = $this->container->get(IManager::class);
		} catch (\Throwable $e) {
			throw new ServiceException('Text processing is not available in your current Nextcloud version', 0, $e);
		}
		if(in_array(SummaryTaskType::class, $manager->getAvailableTaskTypes(), true)) {
			$messageIds = array_map(function ($message) {
				return $message->getMessageId();
			}, $messages);
			$cachedSummary = $this->cache->getSummary($messageIds);
			if($cachedSummary) {
				return $cachedSummary;
			}
			$client = $this->clientFactory->getClient($account);
			try {
				$messagesBodies = array_map(function ($message) use ($client, $account, $mailbox) {
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

			$this->cache->addSummary($messageIds, $summary);

			return $summary;
		} else {
			throw new ServiceException('No language model available for summary');
		}
	}

	public function isLlmAvailable(): bool {
		try {
			$manager = $this->container->get(IManager::class);
		} catch (\Throwable $e) {
			return false;
		}
		return in_array(SummaryTaskType::class, $manager->getAvailableTaskTypes(), true);
	}
}
