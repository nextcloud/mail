<?php
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

namespace OCA\Mail\Service;


use DateTime;
use Horde_Imap_Client;
use Horde_Imap_Client_Exception;
use Horde_Imap_Client_Exception_NoSupportExtension;
use Horde_Imap_Client_Fetch_Query;
use Horde_Imap_Client_Ids;
use Horde_Imap_Client_Search_Query;
use Horde_Imap_Client_Socket;
use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailSearch;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\Search\SearchStrategyFactory;
use OCA\Mail\Model\IMAPMessage;
use OCA\Mail\IMAP\Search\SearchFilterStringParser;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\ILogger;
use function array_keys;
use function array_reverse;
use function in_array;
use function uasort;

class MailSearch implements IMailSearch {

	/** @var IMAPClientFactory */
	private $clientFactory;

	/** @var SearchStrategyFactory */
	private $searchStrategyFactory;

	/** @var SearchFilterStringParser */
	private $filterStringParser;

	/** @var MailboxMapper */
	private $mailboxMapper;

	/** @var ILogger */
	private $logger;

	public function __construct(IMAPClientFactory $clientFactory,
								SearchStrategyFactory $searchStrategyFactory,
								SearchFilterStringParser $filterStringParser,
								MailboxMapper $mailboxMapper,
								ILogger $logger) {
		$this->clientFactory = $clientFactory;
		$this->searchStrategyFactory = $searchStrategyFactory;
		$this->filterStringParser = $filterStringParser;
		$this->mailboxMapper = $mailboxMapper;
		$this->logger = $logger;
	}

	/**
	 * @param Account $account
	 * @param string $mailboxName
	 * @param string|null $filter
	 * @param string|null $cursor
	 *
	 * @return IMAPMessage[]
	 * @throws ServiceException
	 */
	public function findMessages(Account $account, string $mailboxName, ?string $filter, ?int $cursor): array {
		$client = $this->clientFactory->getClient($account);
		try {
			$mailbox = $this->mailboxMapper->find($account, $mailboxName);
		} catch (DoesNotExistException $e) {
			throw new ServiceException('Mailbox does not exist', 0, $e);
		}

		try {
			$query = $this->filterStringParser->parse($filter);

			// In flagged we don't want anything but flagged messages
			if ($mailbox->isSpecialUse(Horde_Imap_Client::SPECIALUSE_FLAGGED)) {
				$query->flag(Horde_Imap_Client::FLAG_FLAGGED);
			}

			// Don't show deleted messages unless for folders
			if (!$mailbox->isSpecialUse(Horde_Imap_Client::SPECIALUSE_TRASH)) {
				$query->flag(Horde_Imap_Client::FLAG_DELETED, false);
			}

			$ids = $this->searchStrategyFactory
				->getStrategy($client, $mailbox->getMailbox(), $query, $cursor)
				->getIds(20);
		} catch (Horde_Imap_Client_Exception $e) {
			throw new ServiceException('Could not get message IDs: ' . $e->getMessage(), 0, $e);
		}

		try {
			$fetchQuery = new Horde_Imap_Client_Fetch_Query();
			$fetchQuery->envelope();
			$fetchQuery->flags();
			$fetchQuery->size();
			$fetchQuery->uid();
			$fetchQuery->imapDate();
			$fetchQuery->structure();
			$fetchQuery->headers(
				'imp',
				[
					'importance',
					'list-post',
					'x-priority',
					'content-type',
				],
				[
					'cache' => true,
					'peek' => true
				]
			);

			$fetchResult = $client->fetch($mailbox->getMailbox(), $fetchQuery, ['ids' => $ids]);
		} catch (Horde_Imap_Client_Exception $e) {
			throw new ServiceException('Could not fetch messages', 0, $e);
		}

		// TODO: do we still need this fix?
		ob_start(); // fix for Horde warnings
		$messages = array_map(function (int $messageId) use ($mailbox, $client, $fetchResult) {
			$header = $fetchResult[$messageId];
			return new IMAPMessage($client, $mailbox->getMailbox(), $messageId, $header);
		}, $fetchResult->ids());
		ob_get_clean();

		return $messages;
	}
}
