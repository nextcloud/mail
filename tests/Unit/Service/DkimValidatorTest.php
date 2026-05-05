<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service;

use OCA\Mail\Service\DkimValidator;
use PHPUnit\Framework\TestCase;

final class DkimValidatorTest extends TestCase {
	private DkimValidator $validator;

	protected function setUp(): void {
		$this->validator = new DkimValidator();
	}

	public function testValidateRetursFalseForEmptyString(): void {
		$result = $this->validator->validate('');

		$this->assertFalse($result);
	}
}
