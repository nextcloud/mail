<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service;

use OCA\Mail\Account;
use OCA\Mail\Address;
use OCA\Mail\AddressList;
use OCA\Mail\Db\LocalAttachment;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\Recipient;
use OCA\Mail\Exception\AttachmentNotFoundException;
use OCA\Mail\Service\Attachment\AttachmentService;
use Psr\Log\LoggerInterface;

class TransmissionService {

	public function __construct(
		private GroupsIntegration $groupsIntegration,
		private AttachmentService $attachmentService,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * @param LocalMessage $message
	 * @param int $type
	 * @return AddressList
	 */
	public function getAddressList(LocalMessage $message, int $type): AddressList {
		$recipientsForType = array_filter($message->getRecipients(), static fn (Recipient $recipient) => $recipient->getType() === $type);
		$expandedRecipients = array_values($this->groupsIntegration->expand($recipientsForType));
		$addresses = array_map(static fn (Recipient $recipient) => Address::fromRaw($recipient->getLabel() ?? $recipient->getEmail(), $recipient->getEmail()), $expandedRecipients);
		return new AddressList($addresses);
	}

	/**
	 * @param LocalMessage $message
	 * @return array|array[]
	 */
	public function getAttachments(LocalMessage $message): array {
		if (empty($message->getAttachments())) {
			return [];
		}
		return array_map(static fn (LocalAttachment $attachment)
			// Convert to the untyped nested array used in \OCA\Mail\Controller\AccountsController::send
			=> [
				'type' => 'local',
				'id' => $attachment->getId(),
			], $message->getAttachments());
	}

	/**
	 * @return array{content: string, type: string, name: string, disposition: ?string, contentId: ?string}|null
	 */
	public function getAttachmentContent(Account $account, array $attachmentRef): ?array {
		if (!isset($attachmentRef['id'])) {
			$this->logger->warning('Ignoring local attachment reference without an id', ['ref' => $attachmentRef]);
			return null;
		}

		try {
			[$attachment, $file] = $this->attachmentService->getAttachment($account->getMailAccount()->getUserId(), (int)$attachmentRef['id']);
		} catch (AttachmentNotFoundException $e) {
			$this->logger->warning('Ignoring local attachment because it does not exist', ['exception' => $e]);
			return null;
		}

		return [
			'content' => $file->getContent(),
			'type' => $attachment->getMimeType(),
			'name' => $attachment->getFileName(),
			'disposition' => $attachment->getDisposition(),
			'contentId' => $attachment->getContentId(),
		];
	}

}
