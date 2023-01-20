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

namespace OCA\Mail\IMAP\Search;

use Horde_Imap_Client_Exception;
use Horde_Imap_Client_Search_Query;
use OCA\Mail\Account;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\Service\Search\SearchQuery;
use function array_reduce;

class Provider {
	/** @var IMAPClientFactory */
	private $clientFactory;

	public function __construct(IMAPClientFactory $clientFactory) {
		$this->clientFactory = $clientFactory;
	}

	/**
	 * @return int[]
	 * @throws ServiceException
	 */
	public function findMatches(Account $account,
								Mailbox $mailbox,
								SearchQuery $searchQuery): array {
		$client = $this->clientFactory->getClient($account);
		try {
			$fetchResult = $client->search(
				$mailbox->getName(),
				$this->convertMailQueryToHordeQuery($searchQuery)
			);
		} catch (Horde_Imap_Client_Exception $e) {
			throw new ServiceException('Could not get message IDs: ' . $e->getMessage(), 0, $e);
		} finally {
			$client->logout();
		}

		return $fetchResult['match']->ids;
	}

	/**
	 * @param SearchQuery $searchQuery
	 *
	 * @todo possible optimization: filter flags here as well as it might speed up IMAP search
	 *
	 * @return Horde_Imap_Client_Search_Query
	 */
	private function convertMailQueryToHordeQuery(SearchQuery $searchQuery): Horde_Imap_Client_Search_Query {
		return array_reduce(
			$searchQuery->getTextTokens(),
			static function (Horde_Imap_Client_Search_Query $query, string $textToken) {
				$query->text($textToken, false);
				return $query;
			},
			new Horde_Imap_Client_Search_Query()
		);
	}
}
