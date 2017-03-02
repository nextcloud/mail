<?php

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
use Horde_Imap_Client_Ids;
use Horde_Imap_Client_Mailbox;
use OCA\Mail\IMAP\MessageMapper;
use OCA\Mail\IMAP\Sync\Request;
use OCA\Mail\IMAP\Sync\Response;

class Synchronizer {

	/** @var MessageMapper */
	private $messageMapper;

	/**
	 * @param MessageMapper $messageMapper
	 */
	public function __construct(MessageMapper $messageMapper) {
		$this->messageMapper = $messageMapper;
	}

	/**
	 * @param Horde_Imap_Client_Base $imapClient
	 * @param Request $request
	 * @return Response
	 */
	public function sync(Horde_Imap_Client_Base $imapClient, Request $request) {
		$mailbox = new Horde_Imap_Client_Mailbox($request->getMailbox());
		$ids = new Horde_Imap_Client_Ids($request->getUids());
		$hordeSync = $imapClient->sync($mailbox, $request->getToken(), [
			'ids' => $ids
		]);

		$newMessages = $this->messageMapper->findByIds($imapClient, $request->getMailbox(), $hordeSync->newmsgsuids->ids);
		$changedMessages = $this->messageMapper->findByIds($imapClient, $request->getMailbox(), $hordeSync->flagsuids->ids);
		$vanishedMessages = $hordeSync->vanisheduids->ids;

		$newSyncToken = $imapClient->getSyncToken($request->getMailbox());
		return new Response($newSyncToken, $newMessages, $changedMessages, $vanishedMessages);
	}

}
