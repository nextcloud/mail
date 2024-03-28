<?php

declare(strict_types=1);
/*
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

namespace OCA\Mail\Service;

use OCA\Mail\Account;
use OCA\Mail\AppInfo\Application;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\Recipient;
use OCA\Mail\Exception\ServiceException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

class RecipientsService {

	public function __construct(
		private LoggerInterface $logger,
		private IConfig $config,
		private GroupsIntegration $groupsIntegration,
	) {
	}

	/**
	 * @param Account $account
	 * @param LocalMessage $message
	 * @return LocalMessage
	 * @throws DoesNotExistException
	 */
	public function checkNumberOfRecipients(Account $account, LocalMessage $message): void {
		if($message->getForce() === true) {
			return;
		}

		$numberOfRecipientsThreshold = (int)$this->config->getAppValue(
			Application::APP_ID,
			'abuse_number_of_recipients_per_message_threshold',
			'0',
		);
		if ($numberOfRecipientsThreshold <= 1) {
			return;
		}

		try {
			$recipients = $this->groupsIntegration->expand($message->getRecipients());
		} catch (ServiceException $e) {
			$recipients = $message->getRecipients();
		}
		$to = count(array_filter($recipients, function ($recipient) {
			return $recipient->getType() === Recipient::TYPE_TO;
		}));
		if ($to >= $numberOfRecipientsThreshold) {
			$message->setStatus(LocalMessage::STATUS_TOO_MANY_RECIPIENTS);
			$this->logger->alert('User {user} sends to a suspicious number of "TO" recipients. {expected} are allowed. {actual} are used', [
				'user' => $account->getUserId(),
				'expected' => $numberOfRecipientsThreshold,
				'actual' => $to,
			]);
			return;
		}

		$cc = count(array_filter($recipients, function ($recipient) {
			return $recipient->getType() === Recipient::TYPE_CC;
		}));
		if ($cc >= $numberOfRecipientsThreshold) {
			$message->setStatus(LocalMessage::STATUS_TOO_MANY_RECIPIENTS);
			$this->logger->alert('User {user} sends to a suspicious number of "CC" recipients. {expected} are allowed. {actual} are used', [
				'user' => $account->getUserId(),
				'expected' => $numberOfRecipientsThreshold,
				'actual' => $cc,
			]);
			return;
		}

		$bcc = count(array_filter($recipients, function ($recipient) {
			return $recipient->getType() === Recipient::TYPE_BCC;
		}));
		if ($bcc >= $numberOfRecipientsThreshold) {
			$message->setStatus(LocalMessage::STATUS_TOO_MANY_RECIPIENTS);
			$this->logger->alert('User {user} sends to a suspicious number of "BCC" recipients. {expected} are allowed. {actual} are used', [
				'user' => $account->getUserId(),
				'expected' => $numberOfRecipientsThreshold,
				'actual' => $bcc,
			]);
			return;
		}
	}
}
