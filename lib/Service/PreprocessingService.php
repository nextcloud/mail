<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Mail\Service;

use OCA\Mail\Account;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\IMAP\PreviewEnhancer;
use Psr\Log\LoggerInterface;

class PreprocessingService {
	private MailboxMapper $mailboxMapper;
	private MessageMapper $messageMapper;
	private LoggerInterface $logger;
	private PreviewEnhancer $previewEnhancer;

	public function __construct(
		MessageMapper $messageMapper,
		LoggerInterface $logger,
		MailboxMapper $mailboxMapper,
		PreviewEnhancer $previewEnhancer,
	) {
		$this->messageMapper = $messageMapper;
		$this->logger = $logger;
		$this->mailboxMapper = $mailboxMapper;
		$this->previewEnhancer = $previewEnhancer;
	}

	public function process(int $limitTimestamp, Account $account): void {
		$mailboxes = $this->mailboxMapper->findAll($account);
		if ($mailboxes === []) {
			$this->logger->debug('No mailboxes found.');
			return;
		}
		$mailboxIds = array_unique(array_map(static function (Mailbox $mailbox) {
			return $mailbox->getId();
		}, $mailboxes));

		$messages = [];
		foreach (array_chunk($mailboxIds, 1000) as $chunk) {
			$messages = array_merge($messages, $this->messageMapper->getUnanalyzed($limitTimestamp, $chunk));
		}
		if ($messages === []) {
			$this->logger->debug('No structure data to analyse.');
			return;
		}

		foreach ($mailboxes as $mailbox) {
			$filteredMessages = array_filter($messages, static function ($message) use ($mailbox) {
				return $message->getMailboxId() === $mailbox->getId();
			});

			if ($filteredMessages === []) {
				continue;
			}

			$processedMessages = $this->previewEnhancer->process($account, $mailbox, $filteredMessages);
			$this->logger->debug('Processed ' . count($processedMessages) . ' messages for structure data for mailbox ' . $mailbox->getId());
		}
	}
}
