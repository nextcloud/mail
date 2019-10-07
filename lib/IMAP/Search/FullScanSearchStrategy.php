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

use Horde_Imap_Client_Exception;
use Horde_Imap_Client_Fetch_Query;
use Horde_Imap_Client_Ids;
use Horde_Imap_Client_Socket;
use function array_keys;
use function array_slice;
use function uasort;

class FullScanSearchStrategy implements ISearchStrategy {

	/** @var Horde_Imap_Client_Socket */
	private $client;

	/** @var string */
	private $mailbox;

	/** @var int|null */
	private $cursor;

	public function __construct(Horde_Imap_Client_Socket $client,
								string $mailbox,
								?int $cursor) {
		$this->client = $client;
		$this->mailbox = $mailbox;
		$this->cursor = $cursor;
	}

	/**
	 * Scan all messages of a mailbox and filter out matching ones
	 *
	 * This is slow, but some IMAP server don't support the SORT capability.
	 *
	 * @throws Horde_Imap_Client_Exception
	 */
	public function getIds(int $maxResults, array $flags = []): Horde_Imap_Client_Ids {
		$query = new Horde_Imap_Client_Fetch_Query();
		$query->uid();
		$query->imapDate();

		$result = $this->client->fetch($this->mailbox, $query);
		$uidMap = [];
		foreach ($result as $r) {
			$ts = $r->getImapDate()->getTimeStamp();
			if ($this->cursor === null || $ts < $this->cursor) {
				$uidMap[$r->getUid()] = $ts;
			}
		}
		// sort by time
		uasort($uidMap, function ($a, $b) {
			return $a < $b;
		});
		return new Horde_Imap_Client_Ids(
			array_slice(
				array_keys($uidMap),
				0,
				$maxResults
			)
		);
	}

}
