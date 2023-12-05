<?php

declare(strict_types=1);

/*
 * @copyright 2021 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2021 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 */

namespace OCA\Mail\Service\Search;

class FlagExpression {
	/**
	 * @var string
	 * @psalm-var "and"|"or"
	 */
	private $operator;

	/**
	 * @var array
	 * @psalm-var (Flag|FlagExpression)[]
	 */
	private $operands;

	/**
	 * @psalm-param "and"|"or" $operator
	 * @param array $operands
	 */
	private function __construct(string $operator, array $operands) {
		$this->operator = $operator;
		$this->operands = $operands;
	}

	/**
	 * @param Flag|FlagExpression ...$operands
	 *
	 * @return static
	 */
	public static function and(...$operands): self {
		return new self("and", $operands);
	}

	/**
	 * @param Flag|FlagExpression ...$operands
	 *
	 * @return static
	 */
	public static function or(...$operands): self {
		return new self("or", $operands);
	}

	public function getOperator(): string {
		return $this->operator;
	}

	/**
	 * @return array
	 * @psalm-return (Flag|FlagExpression)[]
	 */
	public function getOperands(): array {
		return $this->operands;
	}
}
