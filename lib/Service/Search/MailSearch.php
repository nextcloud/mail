<?php

declare(strict_types=1);

/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
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

namespace OCA\Mail\Service\Search;

use Horde_Imap_Client;
use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailSearch;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\MailboxLockedException;
use OCA\Mail\Exception\MailboxNotCachedException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\IMAP\PreviewEnhancer;
use OCA\Mail\IMAP\Search\Provider as ImapSearchProvider;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\ILogger;

class MailSearch implements IMailSearch {

	/** @var FilterStringParser */
	private $filterStringParser;

	/** @var MailboxMapper */
	private $mailboxMapper;

	/** @var ImapSearchProvider */
	private $imapSearchProvider;

	/** @var MessageMapper */
	private $messageMapper;

	/** @var PreviewEnhancer */
	private $previewEnhancer;

	/** @var ILogger */
	private $logger;

	public function __construct(FilterStringParser $filterStringParser,
								MailboxMapper $mailboxMapper,
								ImapSearchProvider $imapSearchProvider,
								MessageMapper $messageMapper,
								PreviewEnhancer $previewEnhancer,
								ILogger $logger) {
		$this->filterStringParser = $filterStringParser;
		$this->mailboxMapper = $mailboxMapper;
		$this->imapSearchProvider = $imapSearchProvider;
		$this->messageMapper = $messageMapper;
		$this->previewEnhancer = $previewEnhancer;
		$this->logger = $logger;
	}

	public function findMessage(Account $account, string $mailboxName, int $uid): Message {
		try {
			$mailbox = $this->mailboxMapper->find($account, $mailboxName);
		} catch (DoesNotExistException $e) {
			throw new ServiceException('Mailbox does not exist', 0, $e);
		}

		$messages = $this->previewEnhancer->process(
			$account,
			$mailbox,
			$this->messageMapper->findByUids(
				$mailbox,
				[$uid]
			)
		);
		if (empty($messages)) {
			throw new DoesNotExistException("Message does not exist");
		}
		return $messages[0];
	}

	/**
	 * @param Account $account
	 * @param string $mailboxName
	 * @param string|null $filter
	 * @param int|null $cursor
	 *
	 * @return Message[]
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function findMessages(Account $account,
								 string $mailboxName,
								 ?string $filter,
								 ?int $cursor,
								 ?int $limit): array {
		try {
			$mailbox = $this->mailboxMapper->find($account, $mailboxName);
		} catch (DoesNotExistException $e) {
			throw new ServiceException('Mailbox does not exist', 0, $e);
		}

		if ($mailbox->hasLocks()) {
			throw MailboxLockedException::from($mailbox);
		}
		if (!$mailbox->isCached()) {
			throw MailboxNotCachedException::from($mailbox);
		}

		$query = $this->filterStringParser->parse($filter);
		if ($cursor !== null) {
			$query->setCursor($cursor);
		}
		// In flagged we don't want anything but flagged messages
		if ($mailbox->isSpecialUse(Horde_Imap_Client::SPECIALUSE_FLAGGED)) {
			$query->addFlag(Horde_Imap_Client::FLAG_FLAGGED);
		}
		// Don't show deleted messages except for trash folders
		if (!$mailbox->isSpecialUse(Horde_Imap_Client::SPECIALUSE_TRASH)) {
			$query->addFlag(Horde_Imap_Client::FLAG_DELETED, false);
		}

		return $this->previewEnhancer->process(
			$account,
			$mailbox,
			$this->messageMapper->findByUids(
				$mailbox,
				$this->getUids($account, $mailbox, $query, $limit)
			)
		);
	}

	/**
	 * We combine local flag and headers merge with UIDs that match the body search if necessary
	 *
	 * @throws ServiceException
	 */
	private function getUids(Account $account, Mailbox $mailbox, SearchQuery $query, ?int $limit): array {
		if (empty($query->getTextTokens())) {
			return $this->messageMapper->findUidsByQuery($mailbox, $query, $limit);
		}

		$fromImap = $this->imapSearchProvider->findMatches(
			$account,
			$mailbox,
			$query
		);
		return $this->messageMapper->findUidsByQuery($mailbox, $query, $limit, $fromImap);
	}
}
