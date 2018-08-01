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
use Horde_Imap_Client_Data_Sync;
use OCA\Mail\IMAP\MessageMapper;
use OCA\Mail\Model\IMAPMessage;

class SimpleMailboxSync implements ISyncStrategy {

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
	 * @param Request $syncRequest
	 * @param Horde_Imap_Client_Data_Sync $hordeSync
	 * @return IMAPMessage[]
	 */
	public function getNewMessages(Horde_Imap_Client_Base $imapClient,
								   Request $syncRequest, Horde_Imap_Client_Data_Sync $hordeSync): array {
		return $this->messageMapper->findByIds($imapClient, $syncRequest->getMailbox(), $hordeSync->newmsgsuids->ids);
	}

	/**
	 * @param Horde_Imap_Client_Base $imapClient
	 * @param Request $syncRequest
	 * @param Horde_Imap_Client_Data_Sync $hordeSync
	 * @return IMAPMessage[]
	 */
	public function getChangedMessages(Horde_Imap_Client_Base $imapClient,
									   Request $syncRequest, Horde_Imap_Client_Data_Sync $hordeSync): array {
		return $this->messageMapper->findByIds($imapClient, $syncRequest->getMailbox(), $hordeSync->flagsuids->ids);
	}

	/**
	 * @param Horde_Imap_Client_Base $imapClient
	 * @param Request $syncRequest
	 * @param Horde_Imap_Client_Data_Sync $hordeSync
	 * @return IMAPMessage[]
	 */
	public function getVanishedMessages(Horde_Imap_Client_Base $imapClient,
										Request $syncRequest, Horde_Imap_Client_Data_Sync $hordeSync): array {
		return $hordeSync->vanisheduids->ids;
	}

}
