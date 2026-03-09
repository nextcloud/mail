<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Db;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\LocalAttachment;

class LocalAttachmentTest extends TestCase {
	public function testIsDispositionAttachmentOrInlineWithAttachment(): void {
		$attachment = new LocalAttachment();
		$attachment->setDisposition(LocalAttachment::DISPOSITION_ATTACHMENT);

		$this->assertTrue($attachment->isDispositionAttachmentOrInline());
	}

	public function testIsDispositionAttachmentOrInlineWithInline(): void {
		$attachment = new LocalAttachment();
		$attachment->setDisposition(LocalAttachment::DISPOSITION_INLINE);

		$this->assertTrue($attachment->isDispositionAttachmentOrInline());
	}

	public function testIsDispositionAttachmentOrInlineWithNull(): void {
		$attachment = new LocalAttachment();
		$attachment->setDisposition(LocalAttachment::DISPOSITION_OMIT);

		$this->assertFalse($attachment->isDispositionAttachmentOrInline());
	}

	public function testIsDispositionAttachmentOrInlineWithNoDispositionSet(): void {
		$attachment = new LocalAttachment();

		$this->assertFalse($attachment->isDispositionAttachmentOrInline());
	}
}
