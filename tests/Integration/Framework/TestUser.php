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

namespace OCA\Mail\Tests\Integration\Framework;

use OC;
use OCP\IUser;
use OCP\IUserManager;
use SebastianBergmann\RecursionContext\Exception;

trait TestUser {

	/** \OCP\IUserManager */
	private $userManager;

	/**
	 * @return IUserManager
	 */
	private function getUserManager() {
		if (is_null($this->userManager)) {
			$this->userManager = OC::$server->getUserManager();
		}
		return $this->userManager;
	}

	private function getRandomUid() {
		return 'testuser' . rand(0, PHP_INT_MAX);
	}

	/**
	 * @return IUser
	 * @throws Exception
	 */
	protected function createTestUser() {
		$userManager = $this->getUserManager();
		$uid = $this->getRandomUid();
		while ($userManager->userExists($uid)) {
			$uid = $this->getRandomUid();
		}

		$user = $userManager->createUser($uid, 'password');
		if ($user === false) {
			throw new Exception('could not create test user');
		}

		return $user;
	}

}
