<?php

declare(strict_types=1);

/**
 * @copyright 2019 Pierre Gordon <pierregordon@protonmail.com>
 *
 * @author 2019 Pierre Gordon <pierregordon@protonmail.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail\Service\Sieve;

use Sieve\SieveException;
use Sieve\SieveParser;

class Script
{

	public const SCRIPT_NAME = 'nextcloud';
	public const SCRIPT_CUSTOM_NAME = 'nextcloud.custom';
	public const TYPE_CUSTOM = 'custom';
	public const TYPE_SIMPLE = 'simple';

	/** @var string */
	private $name;

	/** @var string */
	private $script;

	/** @var string */
	private $parseError;

	public function __construct(string $name, string $script)
	{
		$this->name = $name;
		$this->script = $script;
		$this->parseError = '';
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getScript(): string
	{
		return $this->script;
	}

	/**
	 * @return string
	 */
	public function getParseError(): string
	{
		return $this->parseError;
	}

	/**
	 * @return int
	 */
	public function getSize(): int
	{
		return strlen($this->script);
	}

	public function isValid(): bool
	{
		$parser = new SieveParser();

		try {
			$parser->parse($this->script);
			return true;
		} catch (SieveException $e) {
			$this->parseError = $e->getMessage();
			return false;
		}
	}
}
