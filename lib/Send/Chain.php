<?php

declare(strict_types=1);
/**
 * @copyright 2024 Anna Larch <anna.larch@gmx.net>
 *
 * @author Anna Larch <anna.larch@gmx.net>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\Mail\Send;

use OCA\Mail\Account;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\LocalMessageMapper;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Service\Attachment\AttachmentService;
use OCP\DB\Exception;

class Chain {
	public function __construct(private SentMailboxHandler $sentMailboxHandler,
		private AntiAbuseHandler $antiAbuseHandler,
		private SendHandler $sendHandler,
		private CopySentMessageHandler $copySentMessageHandler,
		private FlagRepliedMessageHandler $flagRepliedMessageHandler,
		private AttachmentService $attachmentService,
		private LocalMessageMapper $localMessageMapper,
	) {
	}

	/**
	 * @throws \Throwable
	 * @throws Exception
	 * @throws ServiceException
	 */
	public function process(Account $account, LocalMessage $localMessage): void {
		$handlers = $this->sentMailboxHandler;
		$handlers->setNext($this->antiAbuseHandler)
			->setNext($this->sendHandler)
			->setNext($this->copySentMessageHandler)
			->setNext($this->flagRepliedMessageHandler);

		/**
		 * Skip all messages that errored out indeterminedly in the SMTP send.
		 * @see \Horde_Smtp_Exception  for the error codes that are inderminate
		 * They might or might not have been sent already.
		 */
		if ($localMessage->getStatus() === LocalMessage::STATUS_ERROR) {
			throw new ServiceException('Could not send message because a previous send operation produced an unclear sent state.');
		}

		$result = $handlers->process($account, $localMessage);
		if ($result->getStatus() === LocalMessage::STATUS_PROCESSED) {
			$this->attachmentService->deleteLocalMessageAttachments($account->getUserId(), $result->getId());
			$this->localMessageMapper->deleteWithRecipients($result);
			return;
		}
		$this->localMessageMapper->update($result);
	}
}
