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
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Service\Attachment\AttachmentService;

class Chain {
	public function __construct(private SentMailboxHandler $sentMailboxHandler,
		private AntiAbuseHandler $antiAbuseHandler,
		private SendHandler $sendHandler,
		private CopySentMessageHandler $copySentMessageHandler,
		private FlagRepliedMessageHandler $flagRepliedMessageHandler,
		private MessageMapper $messageMapper,
		private AttachmentService $attachmentService,
		private LocalMessageMapper $localMessageMapper,
	) {
	}

	public function process(Account $account, LocalMessage $localMessage): void {
		$handlers = $this->sentMailboxHandler;
		$handlers->setNext($this->antiAbuseHandler)
			->setNext($this->sendHandler)
			->setNext($this->copySentMessageHandler)
			->setNext($this->flagRepliedMessageHandler);

		$result = $handlers->process($account, $localMessage);
		if ($result->getStatus() === LocalMessage::STATUS_PROCESSED) {
			$this->attachmentService->deleteLocalMessageAttachments($account->getUserId(), $result->getId());
			$this->localMessageMapper->deleteWithRecipients($result);
			return;
		}
		$this->messageMapper->update($result);
	}
}
