<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Mail\Provider\Command;

use OCA\Mail\Db\LocalAttachment;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\UploadException;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\Attachment\AttachmentService;
use OCA\Mail\Service\OutboxService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Mail\Provider\Exception\SendException;
use OCP\Mail\Provider\IAddress;
use OCP\Mail\Provider\IMessage;

class MessageSend {

	public function __construct(
		protected ITimeFactory $time,
		protected AccountService $accountService,
		protected OutboxService $outboxService,
		protected AttachmentService $attachmentService,
	) {
	}

	/**
	 * Performs send operation
	 *
	 * @since 4.0.0
	 *
	 * @param string $userId system user id
	 * @param string $serviceId mail account id
	 * @param IMessage $message mail message object with all required parameters to send a message
	 * @param array $options array of options reserved for future use
	 *
	 * @return LocalMessage
	 *
	 * @throws SendException on failure, check message for reason
	 *
	 */
	public function perform(string $userId, string $serviceId, IMessage $message, array $options = []): LocalMessage {
		// validate that at least one To address is present
		if (count($message->getTo()) === 0) {
			throw new SendException('Invalid Message Parameter: MUST contain at least one TO address with a valid address');
		}
		// validate that all To, CC and BCC have email address
		$entries = array_merge($message->getTo(), $message->getCc(), $message->getBcc());
		array_walk($entries, function ($entry) {
			if (empty($entry->getAddress())) {
				throw new SendException('Invalid Message Parameter: All TO, CC and BCC addresses MUST contain at least an email address');
			}
		});
		// validate that all attachments have a name, type, and contents
		$entries = $message->getAttachments();
		array_walk($entries, function ($entry) {
			if (empty($entry->getType()) || empty($entry->getContents())) {
				throw new SendException('Invalid Attachment Parameter: MUST contain values for Type and Contents');
			}
		});
		// retrieve user mail account details
		try {
			$account = $this->accountService->find($userId, (int)$serviceId);
		} catch (ClientException $e) {
			throw new SendException('Error: occurred while retrieving mail account details', 0, $e);
		}
		// convert mail provider message to mail app message
		$localMessage = new LocalMessage();
		$localMessage->setType($localMessage::TYPE_OUTGOING);
		$localMessage->setAccountId($account->getId());
		$localMessage->setSubject((string)$message->getSubject());
		$localMessage->setBodyPlain($message->getBodyPlain());
		$localMessage->setBodyHtml($message->getBodyHtml());
		if (!empty($message->getBodyHtml())) {
			$localMessage->setHtml(true);
		} else {
			$localMessage->setHtml(false);
		}
		$localMessage->setSendAt($this->time->getTime());
		// convert mail provider addresses to recipient addresses
		$to = $this->convertAddressArray($message->getTo());
		$cc = $this->convertAddressArray($message->getCc());
		$bcc = $this->convertAddressArray($message->getBcc());
		// save attachments
		$attachments = [];
		try {
			foreach ($message->getAttachments() as $entry) {
				$attachments[] = $this->attachmentService->addFileFromString(
					$userId,
					(string)$entry->getName(),
					(string)$entry->getType(),
					(string)$entry->getContents()
				);
			}
		} catch (UploadException $e) {
			$this->purgeSavedAttachments($attachments);
			throw new SendException('Error: occurred while saving mail message attachment', 0, $e);
		}
		// save message
		$localMessage = $this->outboxService->saveMessage(
			$account,
			$localMessage,
			$to,
			$cc,
			$bcc,
			array_map(static fn (LocalAttachment $attachment) => $attachment->jsonSerialize(), $attachments)
		);
		// send message
		try {
			$localMessage = $this->outboxService->sendMessage($localMessage, $account);
		} catch (\Throwable $e) {
			throw new SendException('Error: occurred while sending mail message', 0, $e);
		}

		return $localMessage;
	}

	/**
	 * Converts IAddress objects collection to plain array
	 *
	 * @since 4.0.0
	 *
	 * @param array<int,IAddress> $addresses collection of IAddress objects
	 *
	 * @return array<int, array{email: string, label?: string}> collection of addresses and labels
	 */
	protected function convertAddressArray(array $addresses): array {
		return array_map(static function (IAddress $address) {
			return !empty($address->getLabel())
				? ['email' => (string)$address->getAddress(), 'label' => (string)$address->getLabel()]
				: ['email' => (string)$address->getAddress()];
		}, $addresses);
	}

	/**
	 * Removes attachments from data store
	 *
	 * @since 4.0.0
	 *
	 * @param array<int, LocalAttachment> $attachments collection of local attachment objects
	 */
	protected function purgeSavedAttachments(array $attachments): void {
		foreach ($attachments as $attachment) {
			$this->attachmentService->deleteAttachment($attachment->getUserId(), $attachment->getId());
		}
	}

}
