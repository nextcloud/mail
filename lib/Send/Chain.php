<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Mail\Send;

use OCA\Mail\Account;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\LocalMessageMapper;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Service\Attachment\AttachmentService;
use OCP\DB\Exception;

class Chain {
	public function __construct(
		private AntiAbuseHandler $antiAbuseHandler,
		private SendHandler $sendHandler,
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
	public function process(Account $account, LocalMessage $localMessage): LocalMessage {
		/**
		 * Skip all messages that errored out indeterminedly in the SMTP send.
		 * @see \Horde_Smtp_Exception  for the error codes that are inderminate
		 * They might or might not have been sent already.
		 */
		if ($localMessage->getStatus() === LocalMessage::STATUS_ERROR) {
			throw new ServiceException('Could not send message because a previous send operation produced an unclear sent state.');
		}

		$head = $this->antiAbuseHandler;
		$head->setNext($this->sendHandler)
			->setNext($this->flagRepliedMessageHandler);

		$result = $head->process($account, $localMessage);

		if ($result->getStatus() === LocalMessage::STATUS_PROCESSED) {
			$this->attachmentService->deleteLocalMessageAttachments($account->getUserId(), $result->getId());
			$this->localMessageMapper->deleteWithRecipients($result);
			return $localMessage;
		}

		return $this->localMessageMapper->update($result);
	}
}
