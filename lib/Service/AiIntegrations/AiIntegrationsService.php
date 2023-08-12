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

use OCA\Mail\Exception\ServiceException;
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


	public function __construct(ContainerInterface $container, Cache $cache) {
		$this->container = $container;
		$this->cache = $cache;
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
	public function summarizeThread(string $threadId, array $messages, string $currentUserId): null|string {
		try {
			$manager = $this->container->get(IManager::class);
		} catch (\Throwable $e) {
			throw new ServiceException('Text processing is not available in your current Nextcloud version', $e);
		}
		if(in_array(SummaryTaskType::class, $manager->getAvailableTaskTypes(), true)) {
			$messagesBodies = array_map(function ($message) {
				return $message->getPreviewText();
			}, $messages);

			$messageIds = array_map(function ($message) {
				return $message->getMessageId();
			}, $messages);
			$cachedSummary = $this->cache->getSummary($messageIds);
			if($cachedSummary) {
				return $cachedSummary;
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
