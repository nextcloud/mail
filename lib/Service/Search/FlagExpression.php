<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\Search;

class FlagExpression {
	/**
	 * @param "and"|"or" $operator
	 * @param (Flag|FlagExpression)[] $operands
	 */
	private function __construct(
		private readonly string $operator,
		private readonly array $operands,
	) {
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

	/**
	 * @return "and"|"or" $operator
	 */
	public function getOperator(): string {
		return $this->operator;
	}

	/**
	 * @psalm-return (Flag|FlagExpression)[]
	 */
	public function getOperands(): array {
		return $this->operands;
	}
}
