<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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

namespace OCA\Mail\Service\DefaultAccount;

use OCP\IUser;

class Config {

	private $data;

	/**
	 * @param array $data
	 */
	public function __construct($data) {
		$this->data = $data;
	}

	/**
	 * @param IUser $user
	 * @return string
	 */
	public function buildEmail(IUser $user) {
		return $this->buildUserEmail($this->data['email'], $user);
	}

	/**
	 * @return string
	 */
	public function getImapHost() {
		return $this->data['imapHost'];
	}

	/**
	 * @return string|int
	 */
	public function getImapPort() {
		return $this->data['imapPort'];
	}

	/**
	 * @return string
	 */
	public function buildImapUser(IUser $user) {
		if (isset($this->data['imapUser'])) {
			return $this->buildUserEmail($this->data['imapUser'], $user);
		}
		return $this->buildEmail($user);
	}

	/**
	 * @return string
	 */
	public function getImapSslMode() {
		return $this->data['imapSslMode'];
	}

	/**
	 * @return string
	 */
	public function getSmtpHost() {
		return $this->data['smtpHost'];
	}

	/**
	 * @return string|int
	 */
	public function getSmtpPort() {
		return $this->data['smtpPort'];
	}

	/**
	 * @param IUser $user
	 * @return string
	 */
	public function buildSmtpUser(IUser $user) {
		if (isset($this->data['smtpUser'])) {
			return $this->buildUserEmail($this->data['smtpUser'], $user);
		}
		return $this->buildEmail($user);
	}

	/**
	 * @return string
	 */
	public function getSmtpSslMode() {
		return $this->data['smtpSslMode'];
	}

	/**
	 * Replace %USERID% and %EMAIL% to allow special configurations
	 *
	 * @param string $original
	 * @param IUser $user
	 * @return string
	 */
	private function buildUserEmail($original, IUser $user) {
		$original = str_replace('%USERID%', $user->getUID(), $original);
		if (!is_null($user->getEMailAddress())) {
			$original = str_replace('%EMAIL%', $user->getEMailAddress(), $original);
		}

		return $original;
	}

}
