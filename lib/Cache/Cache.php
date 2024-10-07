<?php

declare(strict_types=1);

/**
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author 2024 Richard Steinmetz <richard@steinmetz.cloud>
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

namespace OCA\Mail\Cache;

use Horde_Imap_Client_Cache_Backend;
use OCA\Mail\Account;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\MessageMapper;

/**
 * This class passes the minimum amount of data from the db cache to Horde to make QRESYNC work
 * reliably.
 */
class Cache extends Horde_Imap_Client_Cache_Backend {
	/** @var CachedMailbox[] */
	private array $cachedMailboxes = [];

	public function __construct(
		private MessageMapper $dbMessageMapper,
		private MailboxMapper $mailboxMapper,
		private HordeSyncTokenParser $syncTokenParser,
		private Account $account,
	) {
		parent::__construct();
	}

	public function get($mailbox, $uids, $fields, $uidvalid) {
		// Don't forward any data related to individual messages
		return [];
	}

	private function getOrInsertCachedMailbox(string $mailbox): CachedMailbox {
		if (!isset($this->cachedMailboxes[$mailbox])) {
			$this->cachedMailboxes[$mailbox] = new CachedMailbox();
		}

		return $this->cachedMailboxes[$mailbox];
	}

	public function getCachedUids($mailbox, $uidvalid) {
		$cachedMailbox = $this->getOrInsertCachedMailbox($mailbox);

		// Delete cached data of mailbox if uidvalid has changed
		$cachedUidvalid = $cachedMailbox->getUidValidity();
		if ($uidvalid !== null
			&& $cachedUidvalid !== null
			&& $cachedUidvalid !== (int)$uidvalid
		) {
			$this->deleteMailbox($mailbox);
			$cachedMailbox = $this->getOrInsertCachedMailbox($mailbox);
		}

		// Refresh cached uids lazily
		$cachedUids = $cachedMailbox->getUids();
		if ($cachedUids === null) {
			$mailboxEntity = $this->mailboxMapper->find($this->account, $mailbox);
			$cachedUids = $this->dbMessageMapper->findAllUids($mailboxEntity);
			$cachedMailbox->setUids($cachedUids);
		}

		// Copy the array because we don't know whether horde will mutate it
		return array_merge([], $cachedUids);
	}

	public function set($mailbox, $data, $uidvalid) {
		// Don't mutate any data related to individual messages
	}

	public function getMetaData($mailbox, $uidvalid, $entries) {
		$cachedMailbox = $this->getOrInsertCachedMailbox($mailbox);

		// Ensure that uidvalid is always set (see the default null cache implementation)
		$md = ['uidvalid' => 0];

		// Lazily load uidvalid and highestmodseq values from the database
		if ($cachedMailbox->getUidValidity() === null || $cachedMailbox->getHighestModSeq() === null) {
			$mailboxEntity = $this->mailboxMapper->find($this->account, $mailbox);
			$syncToken = $mailboxEntity->getSyncNewToken();
			if ($syncToken !== null) {
				$parsedToken = $this->syncTokenParser->parseSyncToken($syncToken);
				if ($parsedToken->getUidValidity()) {
					$cachedMailbox->setUidValidity($parsedToken->getUidValidity());
				}
				if ($parsedToken->getHighestModSeq()) {
					$cachedMailbox->setHighestModSeq($parsedToken->getHighestModSeq());
				}
			}
		}

		if ($cachedMailbox->getUidValidity() !== null) {
			$md['uidvalid'] = $cachedMailbox->getUidValidity();
		}

		if ($cachedMailbox->getHighestModSeq() !== null) {
			$md['_m'] = $cachedMailbox->getHighestModSeq();
		}

		return $md;
	}

	public function setMetaData($mailbox, $data) {
		// Don't mutate any metadata.
		// The data will be refreshed once the new sync token is written to the db.
	}

	public function deleteMsgs($mailbox, $uids) {
		$mailboxEntity = $this->mailboxMapper->find($this->account, $mailbox);
		$this->dbMessageMapper->deleteByUid($mailboxEntity, ...$uids);

		if (!isset($this->cachedMailboxes[$mailbox])) {
			return;
		}

		$cachedMailbox = $this->cachedMailboxes[$mailbox];
		$cachedUids = $cachedMailbox->getUids();
		if ($cachedUids === null) {
			return;
		}

		$cachedMailbox->setUids(array_diff($cachedUids, $uids));
	}

	public function deleteMailbox($mailbox) {
		unset($this->cachedMailboxes[$mailbox]);
	}

	public function clear($lifetime) {
		$this->cachedMailboxes = [];
	}
}
