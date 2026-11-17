<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service;

use OCA\Mail\Service\DkimValidator;
use PHPMailer\DKIMValidator\Validator;
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

	public function testValidateReturnsFalseWhenLibraryThrowsTypeError(): void {
		$libraryValidator = $this->createMock(Validator::class);
		$libraryValidator->method('validate')
			->willThrowException(new \TypeError('Argument #1 ($publicKey) must be of type string, null given'));
		$validator = new class($libraryValidator) extends DkimValidator {
			public function __construct(
				private Validator $libraryValidator,
			) {
			}

			protected function createValidator(string $rawMessage): Validator {
				return $this->libraryValidator;
			}
		};

		$result = $validator->validate('raw message');

		$this->assertFalse($result);
	}
}
