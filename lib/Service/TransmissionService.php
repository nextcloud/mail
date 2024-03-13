<?php

declare(strict_types=1);
/**
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
use OCA\Mail\Address;
use OCA\Mail\AddressList;
use OCA\Mail\Db\LocalAttachment;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\Recipient;
use OCA\Mail\Exception\AttachmentNotFoundException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Exception\SmimeEncryptException;
use OCA\Mail\Exception\SmimeSignException;
use OCA\Mail\Model\IMessage;
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
	 * @param IMessage $message
	 */
	public function handleAttachment(Account $account, array $attachment, IMessage $message): void {
		if (!isset($attachment['id'])) {
			$this->logger->warning('ignoring local attachment because its id is unknown');
			return;
		}

		$id = (int)$attachment['id'];

		try {
			[$localAttachment, $file] = $this->attachmentService->getAttachment($account->getMailAccount()->getUserId(), $id);
			$message->addLocalAttachment($localAttachment, $file);
		} catch (AttachmentNotFoundException $ex) {
			$this->logger->warning('ignoring local attachment because it does not exist');
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
