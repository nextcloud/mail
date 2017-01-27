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
use OCA\Mail\Folder;
use OCA\Mail\Service\IMAP\IMAPClientFactory;

class MailManager implements IMailManager {

	/** @var IMAPClientFactory */
	private $imapClientFactory;

	/** @var FolderMapper */
	private $folderMapper;

	/** @var FolderNameTranslator */
	private $folderNameTranslator;

	/**
	 * @param IMAPClientFactory $imapClientFactory
	 * @param FolderMapper $folderMapper
	 * @param FolderNameTranslator
	 */
	public function __construct(IMAPClientFactory $imapClientFactory,
		FolderMapper $folderMapper, FolderNameTranslator $folderNameTranslator) {
		$this->imapClientFactory = $imapClientFactory;
		$this->folderMapper = $folderMapper;
		$this->folderNameTranslator = $folderNameTranslator;
	}

	/**
	 * @param Account $account
	 * @return Folder[]
	 */
	public function getFolders(Account $account) {
		$client = $this->imapClientFactory->getClient($account);

		$folders = $this->folderMapper->getFolders($account, $client);
		$this->folderMapper->getFoldersStatus($folders, $client);
		$this->folderMapper->detectFolderSpecialUse($folders);
		$folders = $this->folderMapper->sortFolders($folders);
		$this->folderNameTranslator->translateAll($folders);
		return $this->folderMapper->buildFolderHierarchy($folders);
	}

}
