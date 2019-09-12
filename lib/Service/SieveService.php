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
use OCA\Mail\Service\Sieve\Script;
use OCA\Mail\Service\Sieve\ScriptFactory;

class SieveService
{

	/** @var ManageSieve */
	private $sieveClient;

	/** @var ScriptFactory */
	private $scriptFactory;

	/**
	 * FiltersService constructor.
	 * @param ScriptFactory $scriptFactory
	 */
	public function __construct(ScriptFactory $scriptFactory)
	{
		$this->scriptFactory = $scriptFactory;
	}

	/**
	 * @param Account $account
	 * @return $this
	 * @throws ManageSieve\Exception
	 */
	public function setAccount(Account $account): SieveService
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
	 * @return array
	 * @throws ManageSieve\Exception
	 */
	public function setActiveScript(string $scriptName): array
	{
		$isActive = false;
		$scriptNames = $this->sieveClient->listScripts();
		$activeScriptName = $this->sieveClient->getActive();

		if ($scriptName !== $activeScriptName && in_array($scriptName, $scriptNames)) {
			$this->sieveClient->setActive($scriptName);
			$isActive = true;
		}

		return compact('isActive');
	}

	/**
	 * @param string $scriptName
	 * @return array
	 * @throws ManageSieve\Exception
	 */
	public function getScript(string $scriptName): array
	{
		return ['script' => $this->sieveClient->getScript($scriptName)];
	}

	/**
	 * @param string $filter
	 * @param string $type
	 * @return array
	 * @throws ManageSieve\Exception
	 */
	public function createScript(string $filter, string $type): array
	{
		switch ($type) {
			case Script::TYPE_CUSTOM:
				$script = $this->scriptFactory->createCustom($filter);
				return $this->installRawScript($script);
			default:
				return ['status' => 'error', 'message' => 'Not implemented'];
		}
	}

	/**
	 * @param Script $script
	 * @return array
	 * @throws ManageSieve\Exception
	 */
	private function installRawScript(Script $script): array
	{
		if ($script->isValid()) {
			$this->installScript($script);
			return ['status' => 'success'];
		} else {
			return ['status' => 'error', 'message' => $script->getParseError()];
		}
	}

	/**
	 * @param Script $script
	 * @throws ManageSieve\Exception
	 */
	private function installScript(Script $script)
	{
		if ($this->sieveClient->hasSpace($script->getName(), $script->getSize())) {
			$this->sieveClient->installScript($script->getName(), $script->getScript(), true);
		}
	}
}
