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
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Exception\SmimeEncryptException;
use OCA\Mail\Exception\SmimeSignException;
use OCA\Mail\Service\Attachment\AttachmentService;
use OCP\AppFramework\Db\DoesNotExistException;
use Psr\Log\LoggerInterface;

class TransmissionService {

	public function __construct(private GroupsIntegration $groupsIntegration,
		private AttachmentService $attachmentService,
		private LoggerInterface $logger,
		private SmimeService $smimeService,
	) {
	}

	/**
	 * @param LocalMessage $message
	 * @param int $type
	 * @return AddressList
	 */
	public function getAddressList(LocalMessage $message, int $type): AddressList {
		return new AddressList(
			array_map(
				static function ($recipient) use ($type) {
					return Address::fromRaw($recipient->getLabel() ?? $recipient->getEmail(), $recipient->getEmail());
				},
				$this->groupsIntegration->expand(
					array_filter($message->getRecipients(), static function (Recipient $recipient) use ($type) {
						return $recipient->getType() === $type;
					})
				)
			)
		);
	}

	/**
	 * @param LocalMessage $message
	 * @return array|array[]
	 */
	public function getAttachments(LocalMessage $message): array {
		if(empty($message->getAttachments())) {
			return [];
		}
		return array_map(static function (LocalAttachment $attachment) {
			// Convert to the untyped nested array used in \OCA\Mail\Controller\AccountsController::send
			return [
				'type' => 'local',
				'id' => $attachment->getId(),
			];
		}, $message->getAttachments());
	}

	/**
	 * @param Account $account
	 * @param array $attachment
	 * @return \Horde_Mime_Part|null
	 */
	public function handleAttachment(Account $account, array $attachment): ?\Horde_Mime_Part {
		if (!isset($attachment['id'])) {
			$this->logger->warning('ignoring local attachment because its id is unknown');
			return null;
		}

		try {
			[$localAttachment, $file] = $this->attachmentService->getAttachment($account->getMailAccount()->getUserId(), (int)$attachment['id']);
			$part = new \Horde_Mime_Part();
			$part->setCharset('us-ascii');
			$part->setDisposition('attachment');
			$part->setName($localAttachment->getFileName());
			$part->setContents($file->getContent());

			/*
			 * Horde_Mime_Part.setType takes the mimetype (e.g. text/calendar)
			 * and discards additional parameters (like method=REQUEST).
			 *
			 * $part->setType('text/calendar; method=REQUEST')
			 * $part->getType() => text/calendar
			 */
			$contentTypeHeader = \Horde_Mime_Headers_ContentParam_ContentType::create();
			$contentTypeHeader->decode($localAttachment->getMimeType());

			$part->setType($contentTypeHeader->value);
			foreach($contentTypeHeader->params as $label => $data) {
				$part->setContentTypeParameter($label, $data);
			}

			return $part;
		} catch (AttachmentNotFoundException $e) {
			$this->logger->warning('Ignoring local attachment because it does not exist', ['exception' => $e]);
			return null;
		}
	}

	/**
	 * @param LocalMessage $localMessage
	 * @param Account $account
	 * @param \Horde_Mime_Part $mimePart
	 * @return \Horde_Mime_Part
	 * @throws ServiceException
	 */
	public function getSignMimePart(LocalMessage $localMessage, Account $account, \Horde_Mime_Part $mimePart): \Horde_Mime_Part {
		if ($localMessage->getSmimeSign()) {
			if ($localMessage->getSmimeCertificateId() === null) {
				$localMessage->setStatus(LocalMessage::STATUS_SMIME_SIGN_NO_CERT_ID);
				throw new ServiceException('Could not send message: Requested S/MIME signature without certificate id');
			}

			try {
				$certificate = $this->smimeService->findCertificate(
					$localMessage->getSmimeCertificateId(),
					$account->getUserId(),
				);
				$mimePart = $this->smimeService->signMimePart($mimePart, $certificate);
			} catch (DoesNotExistException $e) {
				$localMessage->setStatus(LocalMessage::STATUS_SMIME_SIGN_CERT);
				throw new ServiceException(
					'Could not send message: Certificate does not exist: ' . $e->getMessage(),
					$e->getCode(),
					$e,
				);
			} catch (SmimeSignException|ServiceException $e) {
				$localMessage->setStatus(LocalMessage::STATUS_SMIME_SIGN_FAIL);
				throw new ServiceException(
					'Could not send message: Failed to sign MIME part: ' . $e->getMessage(),
					$e->getCode(),
					$e,
				);
			}
		}
		return $mimePart;
	}

	/**
	 * @param LocalMessage $localMessage
	 * @param AddressList $to
	 * @param AddressList $cc
	 * @param AddressList $bcc
	 * @param Account $account
	 * @param \Horde_Mime_Part $mimePart
	 * @return \Horde_Mime_Part
	 * @throws ServiceException
	 */
	public function getEncryptMimePart(LocalMessage $localMessage, AddressList $to, AddressList $cc, AddressList $bcc, Account $account, \Horde_Mime_Part $mimePart): \Horde_Mime_Part {
		if ($localMessage->getSmimeEncrypt()) {
			if ($localMessage->getSmimeCertificateId() === null) {
				$localMessage->setStatus(LocalMessage::STATUS_SMIME_ENCRYPT_NO_CERT_ID);
				throw new ServiceException('Could not send message: Requested S/MIME signature without certificate id');
			}

			try {
				$addressList = $to
					->merge($cc)
					->merge($bcc);
				$certificates = $this->smimeService->findCertificatesByAddressList($addressList, $account->getUserId());

				$senderCertificate = $this->smimeService->findCertificate($localMessage->getSmimeCertificateId(), $account->getUserId());
				$certificates[] = $senderCertificate;

				$mimePart = $this->smimeService->encryptMimePart($mimePart, $certificates);
			} catch (DoesNotExistException $e) {
				$localMessage->setStatus(LocalMessage::STATUS_SMIME_ENCRYPT_CERT);
				throw new ServiceException(
					'Could not send message: Certificate does not exist: ' . $e->getMessage(),
					$e->getCode(),
					$e,
				);
			} catch (SmimeEncryptException|ServiceException $e) {
				$localMessage->setStatus(LocalMessage::STATUS_SMIME_ENCRYT_FAIL);
				throw new ServiceException(
					'Could not send message: Failed to encrypt MIME part: ' . $e->getMessage(),
					$e->getCode(),
					$e,
				);
			}
		}
		return $mimePart;
	}

}
