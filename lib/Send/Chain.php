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
use OCA\Mail\Exception\SendInProgressException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Service\Attachment\AttachmentService;
use OCP\DB\Exception;
use OCP\ICacheFactory;
use OCP\IMemcache;

class Chain {
	/**
	 * Lifetime of the per-message send lock in seconds. It only matters when a
	 * process dies in the middle of a send: until the lock expires the
	 * OutboxWorkerJob will not pick the message up again.
	 */
	public const SEND_LOCK_TTL = 15 * 60;

	private const SEND_LOCK_PREFIX = 'mail_outbox_send_lock';

	/**
	 * Distributed cache used as lock registry. Null when the instance has no
	 * distributed memcache: locking is then not possible and sending behaves as
	 * before.
	 */
	private ?IMemcache $sendLocks;

	public function __construct(
		private AntiAbuseHandler $antiAbuseHandler,
		private SendHandler $sendHandler,
		private FlagRepliedMessageHandler $flagRepliedMessageHandler,
		private AttachmentService $attachmentService,
		private LocalMessageMapper $localMessageMapper,
		ICacheFactory $cacheFactory,
	) {
		$cache = $cacheFactory->createDistributed(self::SEND_LOCK_PREFIX);
		$this->sendLocks = $cache instanceof IMemcache ? $cache : null;
	}

	/**
	 * Whether another process currently holds the send lock of this message
	 */
	public function isSending(LocalMessage $localMessage): bool {
		if ($this->sendLocks === null) {
			return false;
		}
		return $this->sendLocks->get((string)$localMessage->getId()) !== null;
	}

	/**
	 * @throws \Throwable
	 * @throws Exception
	 * @throws ServiceException
	 * @throws SendInProgressException if another process is sending this message right now
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

		/**
		 * An outbox message is sent from two angles: the send request the web
		 * client issues once the undo period is over and the OutboxWorkerJob that
		 * flushes every due message. Without a lock both can pick up the same row
		 * while the other one is still talking to the SMTP server and the message
		 * goes out twice. The lock is an atomic add on the distributed cache.
		 * @see https://github.com/nextcloud/mail/issues/6194
		 */
		$lockKey = (string)$localMessage->getId();
		if ($this->sendLocks !== null && !$this->sendLocks->add($lockKey, 1, self::SEND_LOCK_TTL)) {
			throw new SendInProgressException('Outbox message ' . $lockKey . ' is already being sent by another process');
		}

		try {
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
		} finally {
			$this->sendLocks?->remove($lockKey);
		}
	}
}
