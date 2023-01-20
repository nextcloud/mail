<?php

declare(strict_types=1);
/*
 * *
 *  * {$app} App
 *  *
 *  * @copyright 2022 Anna Larch <anna.larch@gmx.net>
 *  *
 *  * @author Anna Larch <anna.larch@gmx.net>
 *  *
 *  * This library is free software; you can redistribute it and/or
 *  * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 *  * License as published by the Free Software Foundation; either
 *  * version 3 of the License, or any later version.
 *  *
 *  * This library is distributed in the hope that it will be useful,
 *  * but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *  *
 *  * You should have received a copy of the GNU Affero General Public
 *  * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *  *
 *
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
		PreviewEnhancer $previewEnhancer
	) {
		$this->messageMapper = $messageMapper;
		$this->logger = $logger;
		$this->mailboxMapper = $mailboxMapper;
		$this->previewEnhancer = $previewEnhancer;
	}

	public function process(int $limitTimestamp, Account $account): void {
		$mailboxes = $this->mailboxMapper->findAll($account);
		if (empty($mailboxes)) {
			$this->logger->debug('No mailboxes found.');
			return;
		}
		$mailboxIds = array_unique(array_map(static function (Mailbox $mailbox) {
			return $mailbox->getId();
		}, $mailboxes));


		$messages = $this->messageMapper->getUnanalyzed($limitTimestamp, $mailboxIds);
		if (empty($messages)) {
			$this->logger->debug('No structure data to analyse.');
			return;
		}

		foreach ($mailboxes as $mailbox) {
			$filteredMessages = array_filter($messages, static function ($message) use ($mailbox) {
				return $message->getMailboxId() === $mailbox->getId();
			});

			if (empty($filteredMessages)) {
				continue;
			}

			$processedMessages = $this->previewEnhancer->process($account, $mailbox, $filteredMessages);
			$this->logger->debug('Processed ' . count($processedMessages) . ' messages for structure data for mailbox ' . $mailbox->getId());
		}
	}
}
