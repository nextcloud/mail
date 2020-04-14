<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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

namespace OCA\Mail\IMAP\Sync;

use Horde_Imap_Client;
use Horde_Imap_Client_Base;
use Horde_Imap_Client_Exception;
use Horde_Imap_Client_Exception_Sync;
use Horde_Imap_Client_Ids;
use Horde_Imap_Client_Mailbox;
use OCA\Mail\IMAP\MessageMapper;
use OCA\mail\lib\Exception\UidValidityChangedException;

class Synchronizer {

	/** @var MessageMapper */
	private $messageMapper;

	public function __construct(MessageMapper $messageMapper) {
		$this->messageMapper = $messageMapper;
	}

	/**
	 * @param Horde_Imap_Client_Base $imapClient
	 * @param Request $request
	 * @param int $criteria
	 *
	 * @return Response
	 * @throws Horde_Imap_Client_Exception
	 * @throws Horde_Imap_Client_Exception_Sync
	 * @throws UidValidityChangedException
	 */
	public function sync(Horde_Imap_Client_Base $imapClient,
						 Request $request,
						 int $criteria = Horde_Imap_Client::SYNC_NEWMSGSUIDS|Horde_Imap_Client::SYNC_FLAGSUIDS|Horde_Imap_Client::SYNC_VANISHEDUIDS): Response {
		$mailbox = new Horde_Imap_Client_Mailbox($request->getMailbox());
		$ids = new Horde_Imap_Client_Ids($request->getUids());
		try {
			$hordeSync = $imapClient->sync($mailbox, $request->getToken(), [
				'criteria' => $criteria,
				'ids' => $ids
			]);
		} catch (Horde_Imap_Client_Exception_Sync $e) {
			if ($e->getCode() === Horde_Imap_Client_Exception_Sync::UIDVALIDITY_CHANGED) {
				throw new UidValidityChangedException();
			}
			throw $e;
		}

		$newMessages = $this->messageMapper->findByIds($imapClient, $request->getMailbox(), $hordeSync->newmsgsuids->ids);
		$changedMessages = $this->messageMapper->findByIds($imapClient, $request->getMailbox(), $hordeSync->flagsuids->ids);
		$vanishedMessageUids = $hordeSync->vanisheduids->ids;

		return new Response($newMessages, $changedMessages, $vanishedMessageUids);
	}
}
