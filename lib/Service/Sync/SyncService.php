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
use OCA\Mail\IMAP\PreviewEnhancer;
use OCA\Mail\IMAP\Sync\Response;
use OCA\Mail\Service\Search\FilterStringParser;
use OCA\Mail\Service\Search\SearchQuery;
use OCP\AppFramework\Db\DoesNotExistException;
use function array_diff;
use function array_map;
use function end;

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

	public function __construct(ImapToDbSynchronizer $synchronizer,
								FilterStringParser $filterStringParser,
								MailboxMapper $mailboxMapper,
								MessageMapper $messageMapper,
								PreviewEnhancer $previewEnhancer) {
		$this->synchronizer = $synchronizer;
		$this->filterStringParser = $filterStringParser;
		$this->mailboxMapper = $mailboxMapper;
		$this->messageMapper = $messageMapper;
		$this->previewEnhancer = $previewEnhancer;
	}

	/**
	 * @param Account $account
	 * @param string $mailboxId
	 *
	 * @throws MailboxLockedException
	 * @throws ServiceException
	 */
	public function clearCache(Account $account,
							   string $mailboxId): void {
		try {
			$mailbox = $this->mailboxMapper->find($account, $mailboxId);
		} catch (DoesNotExistException $e) {
			throw new ServiceException('Mailbox to sync does not exist in the database', 0, $e);
		}

		$this->synchronizer->clearCache($account, $mailbox);
	}

	/**
	 * @param Account $account
	 * @param string $mailboxId
	 * @param int $criteria
	 * @param array $knownUids
	 * @param bool $partialOnly
	 *
	 * @return Response
	 * @throws ClientException
	 * @throws MailboxNotCachedException
	 * @throws ServiceException
	 */
	public function syncMailbox(Account $account,
								string $mailboxId,
								int $criteria,
								array $knownUids,
								bool $partialOnly,
								string $filter = null): Response {
		try {
			$mailbox = $this->mailboxMapper->find($account, $mailboxId);
		} catch (DoesNotExistException $e) {
			throw new ServiceException('Mailbox to sync does not exist in the database', 0, $e);
		}

		if ($partialOnly && !$mailbox->isCached()) {
			throw MailboxNotCachedException::from($mailbox);
		}

		$this->synchronizer->sync(
			$account,
			$mailbox,
			$criteria,
			$knownUids,
			!$partialOnly
		);

		$query = $filter === null ? null : $this->filterStringParser->parse($filter);

		return $this->getDatabaseSyncChanges($account, $mailbox, $knownUids, $query);
	}

	/**
	 * @param Account $account
	 * @param Mailbox $mailbox
	 * @param array $knownUids
	 * @param SearchQuery $query
	 *
	 * @return Response
	 * @todo does not work with text token search queries
	 *
	 */
	private function getDatabaseSyncChanges(Account $account, Mailbox $mailbox, array $knownUids, ?SearchQuery $query): Response {
		if (empty($knownUids)) {
			$newUids = $this->messageMapper->findAllUids($mailbox);
		} else {
			sort($knownUids, SORT_NUMERIC);
			$last = end($knownUids);
			$newUids = $this->messageMapper->findNewUids($mailbox, $last);
		}

		if ($query !== null) {
			// Filter new messages to those that also match the current filter
			$newUids = $this->messageMapper->findUidsByQuery($mailbox, $query, null, $newUids);
		}
		$new = $this->messageMapper->findByUids($mailbox, $newUids);

		// TODO: $changed = $this->messageMapper->findChanged($mailbox, $uids);
		if ($query !== null) {
			$changedUids = $this->messageMapper->findUidsByQuery($mailbox, $query, null, $knownUids);
		} else {
			$changedUids = $knownUids;
		}
		$changed = $this->messageMapper->findByUids($mailbox, $changedUids);

		$stillKnownUids = array_map(static function (Message $msg) {
			return $msg->getUid();
		}, $changed);
		$vanished = array_values(array_diff($knownUids, $stillKnownUids));

		return new Response(
			$this->previewEnhancer->process($account, $mailbox, $new),
			$changed,
			$vanished
		);
	}
}
