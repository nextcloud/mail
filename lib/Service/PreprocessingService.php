<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service;

use OCA\Mail\Account;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\IMAP\PreviewEnhancer;
use Psr\Log\LoggerInterface;
use function count;

class PreprocessingService {
	public const BATCH_SIZE = PreviewEnhancer::CHUNK_SIZE;

	public function __construct(
		private MessageMapper $messageMapper,
		private LoggerInterface $logger,
		private MailboxMapper $mailboxMapper,
		private PreviewEnhancer $previewEnhancer,
	) {
	}

	public function process(int $limitTimestamp, Account $account): void {
		$mailboxes = $this->mailboxMapper->findAll($account);
		if ($mailboxes === []) {
			$this->logger->debug('No mailboxes found.');
			return;
		}

		$nTotal = 0;
		foreach ($mailboxes as $mailbox) {
			$mailboxId = $mailbox->getId();
			$afterUid = 0;
			do {
				$messages = $this->messageMapper->getUnanalyzed($limitTimestamp, $mailboxId, $afterUid, self::BATCH_SIZE);
				$nMessages = count($messages);
				if ($nMessages === 0) {
					break;
				}

				$this->previewEnhancer->process($account, $mailbox, $messages);
				$nTotal += $nMessages;
				$this->logger->debug("Processed $nMessages messages for structure data for mailbox $mailboxId");

				$afterUid = $messages[$nMessages - 1]->getUid();
			} while ($nMessages === self::BATCH_SIZE);
		}

		if ($nTotal === 0) {
			$this->logger->debug('No structure data to analyse.');
		}
	}
}
