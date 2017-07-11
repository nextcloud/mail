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

namespace OCA\Mail\Model;

use OCA\Mail\Service\IAccount;

/**
 * An immutable DTO that holds information about a message that is replied to
 */
class RepliedMessageData {

	/** @var IAccount */
	private $account;

	/** @var string|null */
	private $folderId;

	/** @var int|null */
	private $id;

	/**
	 * @param IAccount $account
	 * @param string|null $folderId
	 * @param int|null $id
	 */
	public function __construct(IAccount $account, $folderId, $id) {
		$this->account = $account;
		$this->folderId = $folderId;
		$this->id = $id;
	}

	/**
	 * @return IAccount
	 */
	public function getAccount() {
		return $this->account;
	}

	/**
	 * @return string|null
	 */
	public function getFolderId() {
		return $this->folderId;
	}

	/**
	 * @return int|null
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @return bool
	 */
	public function isReply() {
		return !is_null($this->folderId) && !is_null($this->id);
	}

}
