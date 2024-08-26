<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
		return new self('and', $operands);
	}

	/**
	 * @param Flag|FlagExpression ...$operands
	 *
	 * @return static
	 */
	public static function or(...$operands): self {
		return new self('or', $operands);
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
