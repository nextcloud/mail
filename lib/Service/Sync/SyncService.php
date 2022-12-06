<?php

declare(strict_types=1);

/**
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\Mail\Service\Sync;

use OCA\Mail\Account;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\MailboxLockedException;
use OCA\Mail\Exception\MailboxNotCachedException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\IMAP\MailboxSync;
use OCA\Mail\IMAP\PreviewEnhancer;
use OCA\Mail\IMAP\Sync\Response;
use OCA\Mail\Service\Search\FilterStringParser;
use OCA\Mail\Service\Search\SearchQuery;
use Psr\Log\LoggerInterface;
use function array_diff;
use function array_map;

class SyncService {
	/** @var ImapToDbSynchronizer */
	private $synchronizer;

	/** @var FilterStringParser */
	private $filterStringParser;

	/** @var MailboxMapper */
	private $mailboxMapper;

	/** @var MessageMapper */
	private $messageMapper;

	/** @var PreviewEnhancer */
	private $previewEnhancer;

	/** @var LoggerInterface */
	private $logger;

	/** @var MailboxSync */
	private $mailboxSync;

	public function __construct(ImapToDbSynchronizer $synchronizer,
								FilterStringParser $filterStringParser,
								MailboxMapper $mailboxMapper,
								MessageMapper $messageMapper,
								PreviewEnhancer $previewEnhancer,
								LoggerInterface $logger,
								MailboxSync $mailboxSync) {
		$this->synchronizer = $synchronizer;
		$this->filterStringParser = $filterStringParser;
		$this->mailboxMapper = $mailboxMapper;
		$this->messageMapper = $messageMapper;
		$this->previewEnhancer = $previewEnhancer;
		$this->logger = $logger;
		$this->mailboxSync = $mailboxSync;
	}

	/**
	 * @param Account $account
	 * @param Mailbox $mailbox
	 *
	 * @throws MailboxLockedException
	 * @throws ServiceException
	 */
	public function clearCache(Account $account,
							   Mailbox $mailbox): void {
		$this->synchronizer->clearCache($account, $mailbox);
	}

	/**
	 * @param Account $account
	 * @param Mailbox $mailbox
	 * @param int $criteria
	 * @param int[] $knownIds
	 * @param bool $partialOnly
	 *
	 * @param string|null $filter
	 *
	 * @return Response
	 * @throws ClientException
	 * @throws MailboxNotCachedException
	 * @throws ServiceException
	 */
	public function syncMailbox(Account $account,
								Mailbox $mailbox,
								int $criteria,
								array $knownIds = null,
								bool $partialOnly,
								string $filter = null): Response {
		if ($partialOnly && !$mailbox->isCached()) {
			throw MailboxNotCachedException::from($mailbox);
		}

		$this->synchronizer->sync(
			$account,
			$mailbox,
			$this->logger,
			$criteria,
			$knownIds === null ? null : $this->messageMapper->findUidsForIds($mailbox, $knownIds),
			!$partialOnly
		);

		$this->mailboxSync->syncStats($account, $mailbox);

		$query = $filter === null ? null : $this->filterStringParser->parse($filter);
		return $this->getDatabaseSyncChanges(
			$account,
			$mailbox,
			$knownIds ?? [],
			$query
		);
	}

	/**
	 * @param Account $account
	 * @param Mailbox $mailbox
	 * @param int[] $knownIds
	 * @param SearchQuery $query
	 *
	 * @return Response
	 * @todo does not work with text token search queries
	 *
	 */
	private function getDatabaseSyncChanges(Account $account,
											Mailbox $mailbox,
											array $knownIds,
											?SearchQuery $query): Response {
		if (empty($knownIds)) {
			$newIds = $this->messageMapper->findAllIds($mailbox);
		} else {
			$newIds = $this->messageMapper->findNewIds($mailbox, $knownIds);
		}

		if ($query !== null) {
			// Filter new messages to those that also match the current filter
			$newUids = $this->messageMapper->findUidsForIds($mailbox, $newIds);
			$newIds = $this->messageMapper->findIdsByQuery($mailbox, $query, null, $newUids);
		}
		$new = $this->messageMapper->findByMailboxAndIds($mailbox, $account->getUserId(), $newIds);

		// TODO: $changed = $this->messageMapper->findChanged($account, $mailbox, $uids);
		if ($query !== null) {
			$changedUids = $this->messageMapper->findUidsForIds($mailbox, $knownIds);
			$changedIds = $this->messageMapper->findIdsByQuery($mailbox, $query, null, $changedUids);
		} else {
			$changedIds = $knownIds;
		}
		$changed = $this->messageMapper->findByMailboxAndIds($mailbox, $account->getUserId(), $changedIds);

		$stillKnownIds = array_map(static function (Message $msg) {
			return $msg->getId();
		}, $changed);
		$vanished = array_values(array_diff($knownIds, $stillKnownIds));

		return new Response(
			$this->previewEnhancer->process($account, $mailbox, $new),
			$changed,
			$vanished,
			$mailbox->getStats()
		);
	}
}
