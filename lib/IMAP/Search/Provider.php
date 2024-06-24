<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
			$searchQuery->getBodies(),
			static function (Horde_Imap_Client_Search_Query $query, string $textToken) {
				$query->text($textToken, true);
				return $query;
			},
			new Horde_Imap_Client_Search_Query()
		);
	}
}
