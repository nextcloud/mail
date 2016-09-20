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
use OCA\Mail\Message;

class UnifiedMailbox implements IMailBox {

	/**
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
		// TODO: Implement getFolderId() method.
	}

	/**
	 * @param int $from
	 * @param int $count
	 * @param string|\Horde_Imap_Client_Search_Query $filter
	 * @return array
	 */
	public function getMessages($from, $count, $filter) {
		$allAccounts = $this->accountService->findByUserId($this->userId);
		$allMessages = array_map(function($account) use ($from, $count, $filter) {
			/** @var IAccount $account */
			if ($account->getId() === UnifiedAccount::ID) {
				return [];
			}
			$inbox = $account->getInbox();
			if (is_null($inbox)) {
				return [];
			}

			$messages = $inbox->getMessages($from, $count, $filter);
			$messages = array_map(function($message) use ($account) {
				$message['id'] = base64_encode(json_encode([$account->getId(), $message['id']]));
				$message['accountMail'] = $account->getEmail();
				return $message;
			}, $messages);

			return $messages;
		}, $allAccounts);

		$allMessages = array_reduce($allMessages, function($a, $b) {
			if (is_null($a)) {
				return $b;
			}
			if (is_null($b)) {
				return $a;
			}
			return array_merge($a, $b);
		});

		// sort by time
		usort($allMessages, function($a, $b) {
			return $a['dateInt'] < $b['dateInt'];
		});

		if ($count >= 0) {
			$allMessages = array_slice($allMessages, 0, $count);
		}

		return $allMessages;
	}

	/**
	 * @return string
	 */
	public function getSpecialRole() {
		return 'inbox';
	}

	/**
	 * @param string $messageId
	 * @return Message
	 */
	public function getMessage($messageId, $loadHtmlMessageBody = false) {
		/** @var IMailBox $inbox */
		/** @var IAccount $account */
		list($inbox, $messageId, $account) = $this->resolve($messageId);
		/** @var Message $message */
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

	public function getSyncToken() {
		throw 'Unified inbox sucks';
	}

	public function sync($syncToken, array $ids = []) {
		throw 'Unified inbox sucks';
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
