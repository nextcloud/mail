<?php declare(strict_types=1);

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

use DateTime;
use Horde_Imap_Client;
use Horde_Imap_Client_Exception;
use Horde_Imap_Client_Ids;
use Horde_Imap_Client_Search_Query;
use Horde_Imap_Client_Socket;
use OCA\Mail\IMAP\Search\SearchFilterStringParser;
use function array_reverse;
use function array_slice;

class ImapSortSearchStrategy implements ISearchStrategy {

	/** @var Horde_Imap_Client_Socket */
	private $client;

	/** @var string */
	private $mailbox;

	/** @var Horde_Imap_Client_Search_Query */
	private $query;

	/** @var int|null */
	private $cursor;

	/** @var ISearchStrategy */
	private $fallback;

	public function __construct(Horde_Imap_Client_Socket $client,
								string $mailbox,
								Horde_Imap_Client_Search_Query $query,
								?int $cursor,
								ISearchStrategy $fallback) {
		$this->client = $client;
		$this->mailbox = $mailbox;
		$this->query = $query;
		$this->cursor = $cursor;
		$this->fallback = $fallback;
	}

	/**
	 * @param int $maxResults
	 * @param array $flags
	 *
	 * @return Horde_Imap_Client_Ids
	 * @throws Horde_Imap_Client_Exception
	 */
	public function getIds(int $maxResults, array $flags = []): Horde_Imap_Client_Ids {
		$query = clone $this->query;

		if ($this->cursor !== null) {
			$query->dateTimeSearch(
				DateTime::createFromFormat("U", (string) $this->cursor),
				Horde_Imap_Client_Search_Query::DATE_BEFORE
			);
		}

		try {
			$result = $this->client->search(
				$this->mailbox,
				$query,
				[
					'sort' => [
						Horde_Imap_Client::SORT_REVERSE,
						Horde_Imap_Client::SORT_DATE
					],
				]
			);
		} catch (Horde_Imap_Client_Exception $e) {
			// maybe the server's advertisement of SORT was a fake
			// see https://github.com/nextcloud/mail/issues/50
			// try again without SORT
			return $this->fallback->getIds($maxResults, $flags);
		}

		return new Horde_Imap_Client_Ids(
			array_slice(
				$result['match']->ids,
				0,
				$maxResults
			)
		);
	}

}
