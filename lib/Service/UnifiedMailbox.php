<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

use OCA\Mail\Attachment;
use OCA\Mail\Model\IMessage;

class UnifiedMailbox implements IMailBox {

	/** @var AccountService */
	protected $accountService;
	/** @var string */
	protected $userId;

	/**
	 * @param AccountService $accountService
	 * @param string $userId
	 */
	public function __construct(AccountService $accountService, $userId) {
		$this->accountService = $accountService;
		$this->userId = $userId;
	}

	/**
	 * @return string
	 */
	public function getFolderId() {
		return null;
	}

	/**
	 * @param string|Horde_Imap_Client_Search_Query $filter
	 * @param int $cursor time stamp of the oldest message on the client
	 * @return array
	 */
	public function getMessages($filter = null, $cursor = null) {
		$allAccounts = $this->accountService->findByUserId($this->userId);
		$allMessages = array_map(function($account) use ($cursor, $filter) {
			/** @var IAccount $account */
			if ($account->getId() === UnifiedAccount::ID) {
				return [];
			}
			$inbox = $account->getInbox();
			if (is_null($inbox)) {
				return [];
			}

			$messages = $inbox->getMessages($cursor, $filter);
			$messages = array_map(function($message) use ($account) {
				$message['id'] = base64_encode(json_encode([$account->getId(), $message['id']]));
				$message['accountMail'] = $account->getEmail();
				return $message;
			}, $messages);

			return $messages;
		}, $allAccounts);

		return array_reduce($allMessages, function($a, $b) {
			if (is_null($a)) {
				return $b;
			}
			if (is_null($b)) {
				return $a;
			}
			return array_merge($a, $b);
		});
	}

	/**
	 * @return string
	 */
	public function getSpecialRole() {
		return 'inbox';
	}

	/**
	 * @param string $messageId
	 * @return IMessage
	 */
	public function getMessage($messageId, $loadHtmlMessageBody = false) {
		/** @var IMailBox $inbox */
		/** @var IAccount $account */
		list($inbox, $messageId, $account) = $this->resolve($messageId);
		/** @var IMessage $message */
		$message = $inbox->getMessage($messageId, $loadHtmlMessageBody);
		$message->setUid(base64_encode(json_encode([$account->getId(), $message->getUid()])));

		return $message;
	}

	/**
	 * @param string $messageId
	 * @param string $attachmentId
	 * @return Attachment
	 */
	public function getAttachment($messageId, $attachmentId) {
		/** @var IMailBox $inbox */
		list($inbox, $messageId) = $this->resolve($messageId);
		return $inbox->getAttachment($messageId, $attachmentId);
	}

	/**
	 * @param string $messageId
	 * @param string $flag
	 * @param mixed $value
	 */
	public function setMessageFlag($messageId, $flag, $value) {
		/** @var IMailBox $inbox */
		list($inbox, $messageId) = $this->resolve($messageId);
		return $inbox->setMessageFlag($messageId, $flag, $value);
	}

	/**
	 * @return array
	 */
	public function getStatus() {
		return [];
	}

	/**
	 * @param string $messageId
	 * @return array
	 */
	private function resolve($messageId) {
		$data = json_decode(base64_decode($messageId), true);
		$account = $this->accountService->find($this->userId, $data[0]);
		$inbox = $account->getInbox();
		$messageId = $data[1];
		return array($inbox, $messageId, $account);
	}

}
