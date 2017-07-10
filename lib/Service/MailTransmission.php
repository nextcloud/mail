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
use Horde_Imap_Client;
use OC\Files\Node\File;
use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailTransmission;
use OCA\Mail\Db\Alias;
use OCA\Mail\Model\IMessage;
use OCA\Mail\Model\NewMessageData;
use OCA\Mail\Model\RepliedMessageData;
use OCA\Mail\Service\AutoCompletion\AddressCollector;
use OCP\Files\Folder;

class MailTransmission implements IMailTransmission {

	/** @var Logger */
	private $logger;

	/** @var AddressCollector  */
	private $addressCollector;

	/** @var Folder */
	private $userFolder;

	public function __construct(AddressCollector $addressCollector, $userFolder, Logger $logger) {
		$this->addressCollector = $addressCollector;
		$this->userFolder = $userFolder;
		$this->logger = $logger;
	}

	public function sendMessage(NewMessageData $messageData, RepliedMessageData $replyData, Alias $alias = null,
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
		$this->handleAttachments($messageData, $message);

		$account->sendMessage($message, $draftUID);

		if ($replyData->isReply()) {
			$this->flagRepliedMessage($account, $replyData);
		}

		$this->collectMailAddresses($message);
	}

	/**
	 * @param Account $account
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
	 * @param NewMessageData $messageData
	 * @param IMessage $message
	 */
	private function handleAttachments(NewMessageData $messageData, IMessage $message) {
		foreach ($messageData->getAttachments() as $attachment) {
			$file = $this->handleCloudAttachment($attachment);
			if (!is_null($file)) {
				$message->addAttachmentFromFiles($file);
			}
		}
	}

	/**
	 * @param array $attachment
	 * @return File|null
	 */
	private function handleCloudAttachment(array $attachment) {
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

		return $file;
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
