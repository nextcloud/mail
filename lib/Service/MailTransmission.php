<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Mail\Service;

use Exception;
use Horde_Exception;
use Horde_Imap_Client;
use OC\Files\Node\File;
use OCA\Mail\Account;
use OCA\Mail\Contracts\IAttachmentService;
use OCA\Mail\Contracts\IMailTransmission;
use OCA\Mail\Db\Alias;
use OCA\Mail\Exception\AttachmentNotFoundException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Model\IMessage;
use OCA\Mail\Model\NewMessageData;
use OCA\Mail\Model\RepliedMessageData;
use OCA\Mail\Service\AutoCompletion\AddressCollector;
use OCP\Files\Folder;

class MailTransmission implements IMailTransmission {

	/** @var AddressCollector  */
	private $addressCollector;

	/** @var Folder */
	private $userFolder;

	/** @var IAttachmentService */
	private $attachmentService;

	/** @var Logger */
	private $logger;

	/**
	 * @param AddressCollector $addressCollector
	 * @param Folder $userFolder
	 * @param IAttachmentService $attachmentService
	 * @param Logger $logger
	 */
	public function __construct(AddressCollector $addressCollector, $userFolder, IAttachmentService $attachmentService,
		Logger $logger) {
		$this->addressCollector = $addressCollector;
		$this->userFolder = $userFolder;
		$this->attachmentService = $attachmentService;
		$this->logger = $logger;
	}

	/**
	 * Send a new message or reply to an existing one
	 *
	 * @param string $userId
	 * @param NewMessageData $messageData
	 * @param RepliedMessageData $replyData
	 * @param Alias|null $alias
	 * @param int|null $draftUID
	 */
	public function sendMessage($userId, NewMessageData $messageData, RepliedMessageData $replyData, Alias $alias = null,
		$draftUID = null) {
		$account = $messageData->getAccount();

		if ($replyData->isReply()) {
			$message = $this->buildReplyMessage($account, $messageData, $replyData);
		} else {
			$message = $this->buildNewMessage($account, $messageData);
		}

		$account->setAlias($alias);
		$message->setFrom($alias ? $alias->alias : $account->getEMailAddress());
		$message->setCC($messageData->getCc());
		$message->setBcc($messageData->getBcc());
		$message->setContent($messageData->getBody());
		$this->handleAttachments($userId, $messageData, $message);

		$account->sendMessage($message, $draftUID);

		if ($replyData->isReply()) {
			$this->flagRepliedMessage($account, $replyData);
		}

		$this->collectMailAddresses($message);
	}

	/**
	 * @param NewMessageData $message
	 * @param type $draftUID
	 * @return type
	 * @throws ServiceException
	 */
	public function saveDraft(NewMessageData $message, $draftUID = null) {
		$account = $message->getAccount();
		$imapMessage = $account->newMessage();
		$imapMessage->setTo($message->getTo());
		$imapMessage->setSubject($message->getSubject() ?: '');
		$imapMessage->setFrom($account->getEMailAddress());
		$imapMessage->setCC($message->getCc());
		$imapMessage->setBcc($message->getBcc());
		$imapMessage->setContent($message->getBody());

		// create transport and save message
		try {
			return $account->saveDraft($imapMessage, $draftUID);
		} catch (Horde_Exception $ex) {
			throw new ServiceException('Could not save draft message', 0, $ex);
		}
	}

	/**
	 * @param Account $account
	 * @param NewMessageData $messageData
	 * @param RepliedMessageData $replyData
	 * @return IMessage
	 */
	private function buildReplyMessage(Account $account, NewMessageData $messageData, RepliedMessageData $replyData) {
		// Reply
		$message = $account->newReplyMessage();

		$mailbox = $account->getMailbox(base64_decode($replyData->getFolderId()));
		$repliedMessage = $mailbox->getMessage($replyData->getId());

		if (is_null($messageData->getSubject())) {
			// No subject set – use the original one
			$message->setSubject($repliedMessage->getSubject());
		} else {
			$message->setSubject($messageData->getSubject());
		}

		// TODO: old code used
		// $message->setTo(Message::parseAddressList($repliedMessage->getToList()));
		// when $to was null. Needs investigation whether that is needed or even makes sense.
		$message->setTo($messageData->getTo());
		$message->setRepliedMessage($repliedMessage);

		return $message;
	}

	/**
	 * @param Account $account
	 * @param NewMessageData $messageData
	 * @return IMessage
	 */
	private function buildNewMessage(Account $account, NewMessageData $messageData) {
		// New message
		$message = $account->newMessage();
		$message->setTo($messageData->getTo());
		$message->setSubject($messageData->getSubject() ?: '');

		return $message;
	}

	/**
	 * @param Account $account
	 * @param RepliedMessageData $replyData
	 */
	private function flagRepliedMessage(Account $account, RepliedMessageData $replyData) {
		$mailbox = $account->getMailbox(base64_decode($replyData->getFolderId()));
		$mailbox->setMessageFlag($replyData->getId(), Horde_Imap_Client::FLAG_ANSWERED, true);
	}

	/**
	 * @param string $userId
	 * @param NewMessageData $messageData
	 * @param IMessage $message
	 */
	private function handleAttachments($userId, NewMessageData $messageData, IMessage $message) {
		foreach ($messageData->getAttachments() as $attachment) {
			if (isset($attachment['isLocal']) && $attachment['isLocal'] === 'true') {
				$this->handleLocalAttachment($userId, $attachment, $message);
			} else {
				$this->handleCloudAttachment($attachment, $message);
			}
		}
	}

	/**
	 * @param string $userId
	 * @param array $attachment
	 * @param IMessage $message
	 * @return int|null
	 */
	private function handleLocalAttachment($userId, array $attachment, IMessage $message) {
		if (!isset($attachment['id'])) {
			$this->logger->warning('ignoring local attachment because its id is unknown');
			return null;
		}

		$id = $attachment['id'];

		try {
			list($localAttachment, $file) = $this->attachmentService->getAttachment($userId, $id);
			$message->addLocalAttachment($localAttachment, $file);
		} catch (AttachmentNotFoundException $ex) {
			$this->logger->warning('ignoring local attachment because it does not exist');
			// TODO: rethrow?
			return null;
		}
	}

	/**
	 * @param array $attachment
	 * @param IMessage $message
	 * @return File|null
	 */
	private function handleCloudAttachment(array $attachment, IMessage $message) {
		if (!isset($attachment['fileName'])) {
			$this->logger->warning('ignoring cloud attachment because its fileName is unknown');
			return null;
		}

		$fileName = $attachment['fileName'];
		if (!$this->userFolder->nodeExists($fileName)) {
			$this->logger->warning('ignoring cloud attachment because the node does not exist');
			return null;
		}

		$file = $this->userFolder->get($fileName);
		if (!$file instanceof File) {
			$this->logger->warning('ignoring cloud attachment because the node is not a file');
			return null;
		}

		if (!is_null($file)) {
			$message->addAttachmentFromFiles($file);
		}
	}

	/**
	 * @param IMessage $message
	 */
	private function collectMailAddresses($message) {
		try {
			$addresses = array_merge($message->getToList(), $message->getCCList(), $message->getBCCList());
			$this->addressCollector->addAddresses($addresses);
		} catch (Exception $e) {
			$this->logger->error("Error while collecting mail addresses: " . $e->getMessage());
		}
	}

}
