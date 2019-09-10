<?php

declare(strict_types=1);

/**
 * @author Pierre Gordon <pierregordon@protonmail.com>
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

use Horde\ManageSieve;
use OCA\Mail\Account;
use OCA\Mail\Sieve\Filter;
use OCA\Mail\Sieve\SieveScript;
use OCA\Mail\Sieve\SieveSerializer;

class FiltersService
{

	/** @var ManageSieve */
	private $sieveClient;

	/**
	 * @param Account $account
	 * @return $this
	 * @throws ManageSieve\Exception
	 */
	public function setAccount(Account $account): FiltersService
	{
		$this->sieveClient = $account->getSieveConnection();
		return $this;
	}

	/**
	 * @return array
	 */
	public function getScriptNames(): array
	{
		$scriptNames = $this->sieveClient->listScripts();
		$activeScriptName = $this->sieveClient->getActive();

		$scripts = array_fill_keys(['active', 'entries'], null);

		foreach ($scriptNames as $scriptName) {
			if ($scriptName === $activeScriptName) {
				$scripts['active'] = $scriptName;
			}

			$scripts['entries'][] = $scriptName;
		}

		return compact('scripts');
	}

	/**
	 * @param string $scriptName
	 * @return bool
	 * @throws ManageSieve\Exception
	 */
	public function setActiveScript(string $scriptName): bool
	{
		$scriptNames = $this->sieveClient->listScripts();
		$activeScriptName = $this->sieveClient->getActive();

		if ($scriptName !== $activeScriptName && in_array($scriptName, $scriptNames)) {
			$this->sieveClient->setActive($scriptName);
			return true;
		}

		return false;
	}
}
