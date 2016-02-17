<?php
/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * ownCloud - Mail
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

use OCP\IL10N;
use OCA\Mail\Model\IMessage;

class UnifiedAccount implements IAccount {

	const ID = -1;
	const INBOX_ID = 'all-inboxes';

	/** @var AccountService */
	private $accountService;

	/** @var string */
	private $userId;

	/** @var IL10N */
	private $l10n;

	/** @var Horde_Mail_Rfc822_List */
	private $email;

	/**
	 * @param AccountService $accountService
	 * @param string $userId
	 * @param IL10N $l10n
	 */
	public function __construct(AccountService $accountService, $userId, IL10N $l10n) {
		$this->accountService = $accountService;
		$this->userId = $userId;
		$this->l10n = $l10n;
	}

	/**
	 * @return array
	 */
	public function getConfiguration() {
		return [
			'accountId' => UnifiedAccount::ID,
		];
	}

	/**
	 * @return array
	 * TODO: function name is :hankey:
	 */
	public function getListArray() {
		$inbox = $this->buildInbox();
		return [
			'id'             => UnifiedAccount::ID,
			'email'          => '',
			'folders'        => [$inbox],
			'specialFolders' => [],
			'delimiter' => '.',
		];
	}

	private function buildInbox() {
		$displayName = (string)$this->l10n->t('All inboxes');

		$allAccounts = $this->accountService->findByUserId($this->userId);

		$uidValidity = [];
		$uidNext = [];
		$unseen = 0;

		foreach($allAccounts as $account) {
			/** @var IAccount $account */
			$inbox = $account->getInbox();
			if (is_null($inbox)) {
				continue;
			}

			$status = $inbox->getStatus();
			$unseen += isset($status['unseen']) ? $status['unseen'] : 0;
			$uidValidity[$account->getId()] = isset($status['uidvalidity']) ? $status['uidvalidity'] : 0;
			$uidNext[$account->getId()] = isset($status['uidnext']) ? $status['uidnext'] : 0;
		}

		return [
			'id' => base64_encode(self::INBOX_ID),
			'parent' => null,
			'name' => $displayName,
			'specialRole' => 'inbox',
			'unseen' => $unseen,
			'total' => 100,
			'isEmpty' => false,
			'accountId' => UnifiedAccount::ID,
			'noSelect' => false,
			'uidvalidity' => $uidValidity,
			'uidnext' => $uidNext,
			'delimiter' => '.'
		];
	}

	/**
	 * @param $folderId
	 * @return IMailBox
	 */
	public function getMailbox($folderId) {
		return new UnifiedMailbox($this->accountService, $this->userId);
	}

	/**
	 * @return string
	 */
	public function getEmail() {
		if ($this->email === null) {
			$allAccounts = $this->accountService->findByUserId($this->userId);
			$addressesList = new \Horde_Mail_Rfc822_List();
			foreach ($allAccounts as $account) {
				$inbox = $account->getInbox();
				if (is_null($inbox)) {
					continue;
				}
				$addressesList->add($account->getEmail());
			}
			$this->email = $addressesList;
		}
		return $this->email;
	}

	/**
	 * @param IMessage $message
	 * @param int|null $draftUID
	 */
	public function sendMessage(IMessage $message, $draftUID) {
		throw new Exception('Not implemented');
	}

	/**
	 * @param IMessage $message
	 * @param int|null $previousUID
	 * @return int
	 */
	public function saveDraft(IMessage $message, $previousUID) {
		throw new Exception('Not implemented');
	}

	/**
	 * @param string $folderId
	 * @param string $messageId
	 */
	public function deleteMessage($folderId, $messageId) {
		$data = json_decode(base64_decode($messageId), true);
		$account = $this->accountService->find($this->userId, $data[0]);
		$inbox = $account->getInbox();
		$messageId = $data[1];

		$account->deleteMessage($inbox->getFolderId(), $messageId);
	}

	/**
	 * @param string[] $query
	 * @return array
	 */
	public function getChangedMailboxes($query) {
		$accounts = $this->accountService->findByUserId($this->userId);
		$changedBoxes = [];

		foreach($accounts as $account) {
			/** @var IAccount $account */
			if ($account->getId() === UnifiedAccount::ID) {
				continue;
			}
			$inbox = $account->getInbox();
			$inboxName = $inbox->getFolderId();
			$changes = $account->getChangedMailboxes([$inboxName => [
				'uidvalidity' => $query[self::INBOX_ID]['uidvalidity'][$account->getId()],
				'uidnext' => $query[self::INBOX_ID]['uidnext'][$account->getId()],
			]]);
			if (!isset($changes[$inboxName])) {
				continue;
			}
			if (!isset($changedBoxes[self::INBOX_ID])) {
				$changedBoxes[self::INBOX_ID] = $this->buildInbox();
				$changedBoxes[self::INBOX_ID]['messages'] = [];
				$changedBoxes[self::INBOX_ID]['newUnReadCounter'] = 0;
			}
			// Create special unified inbox message IDs
			foreach ($changes[$inboxName]['messages'] as &$message) {
				$id = base64_encode(json_encode([$account->getId(), $message['id']]));
				$message['id'] = $id;
			}
			$changedBoxes[self::INBOX_ID]['messages'] = array_merge($changedBoxes[self::INBOX_ID]['messages'], $changes[$inboxName]['messages']);
			$changedBoxes[self::INBOX_ID]['newUnReadCounter'] += $changes[$inboxName]['newUnReadCounter'];
		}
		return $changedBoxes;
	}

	/**
	 * @return IMailBox
	 */
	public function getInbox() {
		return null;
	}

	/**
	 * @return int
	 */
	public function getId() {
		return UnifiedAccount::ID;
	}

	/**
	 * @param string $messageId
	 * @return array
	 */
	public function resolve($messageId) {
		$data = json_decode(base64_decode($messageId), true);
		$account = $this->accountService->find($this->userId, $data[0]);
		$inbox = $account->getInbox();
		$messageId = $data[1];

		return [$account, base64_encode($inbox->getFolderId()), $messageId];
	}
}
