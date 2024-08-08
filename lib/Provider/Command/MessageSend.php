<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Mail\Provider\Command;

use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\Attachment\AttachmentService;
use OCA\Mail\Service\OutboxService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use OCP\Mail\Provider\Exception\SendException;
use OCP\Mail\Provider\IAddress;
use OCP\Mail\Provider\IMessage;

class MessageSend {

	public function __construct(
		protected IConfig $config,
		protected ITimeFactory $time,
		protected AccountService $accountService,
		protected OutboxService $outboxService,
		protected AttachmentService $attachmentService
	) {
	}

	/**
	 * performs send operation
	 *
	 * @since 4.0.0
	 *
	 * @param string $userId			system user id
	 * @param string $serviceId			mail account id
	 * @param IMessage $message			mail message object with all required parameters to send a message
	 * @param array $options			array of options reserved for future use
	 *
	 * @return LocalMessage
	 */
	public function perform(string $userId, string $serviceId, IMessage $message, array $option = []): LocalMessage {
		// find user mail account details
		$account = $this->accountService->find($userId, (int) $serviceId);
		// convert mail provider message to mail app message
		$localMessage = new LocalMessage();
		$localMessage->setType($localMessage::TYPE_OUTGOING);
		$localMessage->setAccountId($account->getId());
		$localMessage->setSubject($message->getSubject());
		$localMessage->setBody($message->getBody());
		$localMessage->setHtml(true);
		$localMessage->setSendAt($this->time->getTime());
		
		// convert all mail provider attachments to local attachments
		$attachments = [];
		if (count($message->getAttachments()) > 0) {
			// iterate attachments and save them
			foreach ($message->getAttachments() as $entry) {
				// determine if required parameters are set
				if (empty($entry->getName()) || empty($entry->getType()) || empty($entry->getContents())) {
					throw new SendException("Invalid Attachment Parameter: MUST contain values for Name, Type and Contents");
				}
				// convert mail provider attachment to mail app attachment
				$attachments[] = $this->attachmentService->addFileFromString(
					$userId,
					$entry->getName(),
					$entry->getType(),
					$entry->getContents()
				)->jsonSerialize();
			}
		}
		// determine if required To address is set
		if (empty($message->getTo()) || empty($message->getTo()[0]->getAddress())) {
			throw new SendException("Invalid Message Parameter: MUST contain at least one TO address with a valid address");
		}
		// convert recipiant addresses
		$to = $this->convertAddressArray($message->getTo());
		$cc = $this->convertAddressArray($message->getCc());
		$bcc = $this->convertAddressArray($message->getBcc());
		// save message for sending
		$localMessage = $this->outboxService->saveMessage(
			$account,
			$localMessage,
			$to,
			$cc,
			$bcc,
			$attachments
		);

		// evaluate if job scheduler is NOT cron, send message right away otherwise let cron job handle it
		if ($this->config->getAppValue('core', 'backgroundjobs_mode', 'ajax') !== 'cron') {
			$localMessage = $this->outboxService->sendMessage($localMessage, $account);
		}

		return $localMessage;

	}

	/**
	 * converts IAddess objects collection to plain array
	 *
	 * @since 4.0.0
	 *
	 * @param array<int,IAddress> $in	collection of IAddress objects
	 *
	 * @return array<int,array>			returns [['email' => 'test@example.com', 'label' => 'Test User']]
	 */
	protected function convertAddressArray(array|null $in): array {
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
