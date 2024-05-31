<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024 Sebastian Krupinski <krupinski01@gmail.com>
 *
 * @author Sebastian Krupinski <krupinski01@gmail.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Mail\Provider\Command;

use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\Attachment\AttachmentService;
use OCA\Mail\Service\OutboxService;
use OCA\Mail\Service\SmimeService;
use OCP\Mail\Provider\IMessage;

class MessageSend {

	public function __construct(
		AccountService $accountService,
		OutboxService $outboxService,
		AttachmentService $attachmentService,
		SmimeService $smimeService
	) {
		$this->accountService = $accountService;
		$this->outboxService = $outboxService;
		$this->attachmentService = $attachmentService;
		$this->smimeService = $smimeService;
	}

	public function perform(string $userId, string $serviceId, IMessage $message, array $option = []): void {
		// find user mail account details
		$account = $this->accountService->find($userId, (int) $serviceId);
		// convert mail provider message to local message
		$lm = new LocalMessage();
		$lm->setType($lm::TYPE_OUTGOING);
		$lm->setAccountId($account->getId());
		$lm->setSubject($message->getSubject());
		$lm->setBody($message->getBody());
		//$lm->setEditorBody($editorBody);
		$lm->setHtml(true);
		//$lm->setInReplyToMessageId($inReplyToMessageId);
		$lm->setSendAt(time());
		//$lm->setSmimeSign($smimeSign);
		//$lm->setSmimeEncrypt($smimeEncrypt);

		/*
		if (!empty($smimeCertificateId)) {
			$smimeCertificate = $this->smimeService->findCertificate($smimeCertificateId, $this->userId);
			$lm->setSmimeCertificateId($smimeCertificate->getId());
		}
		*/

		// convert all mail provider attachments to local attachments
		$attachments = [];
		if (count($message->getAttachments()) > 0) {
			// iterate attachments and save them
			foreach ($message->getAttachments() as $entry) {
				$attachments[] = $this->attachmentService->addFileFromString(
					$userId,
					$entry->getName(),
					$entry->getType(),
					$entry->getContents()
				)->jsonSerialize();
			}
		}
		// convert recipiant addresses
		$to = $this->convertAddressArray($message->getTo());
		$cc = $this->convertAddressArray($message->getCc());
		$bcc = $this->convertAddressArray($message->getBcc());
		// save/send message
		$lm = $this->outboxService->saveMessage(
			$account,
			$lm,
			$to,
			$cc,
			$bcc,
			$attachments
		);

	}

	protected function convertAddressArray(array|null $in) {
		// construct place holder
		$out = [];
		// convert format
		foreach ($in as $entry) {
			$out[] = (!empty($entry->getLabel())) ? ['email' => $entry->getAddress(), 'label' => $entry->getLabel()] : ['email' => $entry->getAddress()];
		}
		// return converted addressess
		return $out;
	}

}
