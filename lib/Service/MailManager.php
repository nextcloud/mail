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

use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Folder;
use OCA\Mail\IMAP\FolderMapper;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\MailboxPrefixDetector;
use OCA\Mail\IMAP\MessageMapper;
use OCA\Mail\IMAP\Sync\Request;
use OCA\Mail\IMAP\Sync\Response;
use OCA\Mail\IMAP\Sync\Synchronizer;
use OCA\Mail\Service\FolderNameTranslator;

class MailManager implements IMailManager {

	/** @var IMAPClientFactory */
	private $imapClientFactory;

	/** @var FolderMapper */
	private $folderMapper;

	/** @var MailboxPrefixDetector */
	private $prefixDetector;

	/** @var FolderNameTranslator */
	private $folderNameTranslator;

	/** @var Synchronizer */
	private $synchronizer;

	/** @var MessageMapper */
	private $messageMapper;

	/**
	 * @param IMAPClientFactory $imapClientFactory
	 * @param FolderMapper $folderMapper
	 * @param MailboxPrefixDetector $prefixDetector
	 * @param FolderNameTranslator $folderNameTranslator
	 * @param Synchronizer $synchronizer
	 * @param MessageMapper $messageMapper
	 */
	public function __construct(IMAPClientFactory $imapClientFactory,
		FolderMapper $folderMapper, MailboxPrefixDetector $prefixDetector,
		FolderNameTranslator $folderNameTranslator, Synchronizer $synchronizer,
		MessageMapper $messageMapper) {
		$this->imapClientFactory = $imapClientFactory;
		$this->folderMapper = $folderMapper;
		$this->prefixDetector = $prefixDetector;
		$this->folderNameTranslator = $folderNameTranslator;
		$this->synchronizer = $synchronizer;
		$this->messageMapper = $messageMapper;
	}

	/**
	 * @param Account $account
	 * @return Folder[]
	 */
	public function getFolders(Account $account) {
		$client = $this->imapClientFactory->getClient($account);

		$folders = $this->folderMapper->getFolders($account, $client);
		$havePrefix = $this->prefixDetector->havePrefix($folders);
		$this->folderMapper->getFoldersStatus($folders, $client);
		$this->folderMapper->detectFolderSpecialUse($folders);
		$this->folderMapper->sortFolders($folders);
		$this->folderNameTranslator->translateAll($folders, $havePrefix);
		return $this->folderMapper->buildFolderHierarchy($folders);
	}

	/**
	 * @param Account $account
	 * @param Request $syncRequest
	 * @return Response
	 */
	public function syncMessages(Account $account, Request $syncRequest) {
		$client = $this->imapClientFactory->getClient($account);

		return $this->synchronizer->sync($client, $syncRequest);
	}

	/**
	 * @param Account $sourceAccount
	 * @param string $sourceFolderId
	 * @param int $messageId
	 * @param Account $destinationAccount
	 * @param string $destFolderId
	 */
	public function moveMessage(Account $sourceAccount, $sourceFolderId,
		$messageId, Account $destinationAccount, $destFolderId) {

		if ($sourceAccount->getId() === $destinationAccount->getId()) {
			$this->moveMessageOnSameAccount($sourceAccount, $sourceFolderId,
				$destFolderId, $messageId);
		} else {
			throw new ServiceException('It is not possible to move across accounts yet');
		}
	}

	/**
	 * @param Account $account
	 * @param string $sourceFolderId
	 * @param string $destFolderId
	 * @param int $messageId
	 */
	private function moveMessageOnSameAccount(Account $account, $sourceFolderId,
		$destFolderId, $messageId) {
		$client = $this->imapClientFactory->getClient($account);

		$this->messageMapper->move($client, $sourceFolderId, $messageId, $destFolderId);
	}

}
