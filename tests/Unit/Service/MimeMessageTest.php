<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Unit\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Mime_Part;
use OCA\Mail\Account;
use OCA\Mail\AddressList;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Model\NewMessageData;
use OCA\Mail\Service\DataUri\DataUriParser;
use OCA\Mail\Service\MimeMessage;

class MimeMessageTest extends TestCase {
	private DataUriParser $uriParser;
	private MimeMessage $mimeMessage;
	private Account $account;

	protected function setUp(): void {
		parent::setUp();

		$this->uriParser = new DataUriParser();
		$this->mimeMessage = new MimeMessage($this->uriParser);
		$this->account = new Account(new MailAccount());
	}

	public function testTextPlain() {
		$messageData = new NewMessageData(
			$this->account,
			new AddressList(),
			new AddressList(),
			new AddressList(),
			'Text message',
			file_get_contents(__DIR__ . '/../../../tests/data/mime-text.txt'),
			[],
			false,
			false
		);

		$part = $this->mimeMessage->build(
			$messageData->getBody(),
			null,
			false,
			[],
		);

		$this->assertEquals('text/plain', $part->getType());
	}

	public function testMultipartAlternative() {
		$messageData = new NewMessageData(
			$this->account,
			new AddressList(),
			new AddressList(),
			new AddressList(),
			'Text and HTML message',
			file_get_contents(__DIR__ . '/../../../tests/data/mime-html.txt'),
			[],
			true,
			false
		);

		$part = $this->mimeMessage->build(
			$messageData->getBody(),
			$messageData->getBody(),
			false,
			[],
		);

		$this->assertEquals('multipart/alternative', $part->getType());

		/** @var Horde_Mime_Part[] $subParts */
		$subParts = $part->getParts();
		$this->assertCount(2, $subParts);
		$this->assertEquals('text/plain', $subParts[0]->getType());
		$this->assertEquals('text/html', $subParts[1]->getType());
	}

	public function testMultipartAlternativeEmptyContent() {
		$messageData = new NewMessageData(
			$this->account,
			new AddressList(),
			new AddressList(),
			new AddressList(),
			'Empty Text and HTML message',
			'',
			[],
			true,
			false
		);

		$part = $this->mimeMessage->build(
			$messageData->getBody(),
			$messageData->getBody(),
			false,
			[],
		);

		$this->assertEquals('multipart/alternative', $part->getType());

		/** @var Horde_Mime_Part[] $subParts */
		$subParts = $part->getParts();
		$this->assertCount(2, $subParts);
		$this->assertEquals('text/plain', $subParts[0]->getType());
		$this->assertEquals('text/html', $subParts[1]->getType());
	}

	public function testMultipartMixedAlternative() {
		$messageData = new NewMessageData(
			$this->account,
			new AddressList(),
			new AddressList(),
			new AddressList(),
			'Text, HTML and Attachment message',
			file_get_contents(__DIR__ . '/../../../tests/data/mime-html.txt'),
			[],
			true,
			false
		);

		$attachment1 = $this->createAttachmentPart(
			'nextcloud logo',
			file_get_contents(__DIR__ . '/../../../tests/data/nextcloud.png'),
			'image/png',
			'attachment',
		);

		$part = $this->mimeMessage->build(
			$messageData->getBody(),
			$messageData->getBody(),
			false,
			[$attachment1],
		);

		$this->assertEquals('multipart/mixed', $part->getType());

		/** @var Horde_Mime_Part[] $subParts */
		$subParts = $part->getParts();
		$this->assertCount(2, $subParts);

		$alternativePart = $subParts[0];
		$this->assertEquals('multipart/alternative', $alternativePart->getType());

		/** @var Horde_Mime_Part[] $alternativeSubParts */
		$alternativeSubParts = $alternativePart->getParts();
		$this->assertCount(2, $alternativeSubParts);
		$this->assertEquals('text/plain', $alternativeSubParts[0]->getType());
		$this->assertEquals('text/html', $alternativeSubParts[1]->getType());

		$attachmentPart = $subParts[1];
		$this->assertEquals('image/png', $attachmentPart->getType());
		$this->assertEquals('attachment', $attachmentPart->getDisposition());
	}

	public function testMultipartMixedRelated() {
		$messageData = new NewMessageData(
			$this->account,
			new AddressList(),
			new AddressList(),
			new AddressList(),
			'Text, HTML and Attachment message',
			file_get_contents(__DIR__ . '/../../../tests/data/mime-html-image.txt'),
			[],
			true,
			false
		);

		$attachment1 = $this->createAttachmentPart(
			'nextcloud logo',
			file_get_contents(__DIR__ . '/../../../tests/data/nextcloud.png'),
			'image/png',
			'attachment',
		);

		$attachment2 = $this->createAttachmentPart(
			'sensitive animals logo',
			file_get_contents(__DIR__ . '/../../../tests/data/test.txt'),
			'text/plain',
			'attachment',
		);

		$part = $this->mimeMessage->build(
			$messageData->getBody(),
			$messageData->getBody(),
			false,
			[$attachment1, $attachment2],
		);

		$this->assertEquals('multipart/mixed', $part->getType());

		/** @var Horde_Mime_Part[] $subParts */
		$subParts = $part->getParts();
		$this->assertCount(3, $subParts);

		$relatedPart = $subParts[0];
		$this->assertEquals('multipart/related', $relatedPart->getType());

		/** @var Horde_Mime_Part[] $relatedSubParts */
		$relatedSubParts = $relatedPart->getParts();
		$this->assertCount(2, $relatedSubParts);

		$alternativePart = $relatedSubParts[0];
		$this->assertEquals('multipart/alternative', $alternativePart->getType());

		/** @var Horde_Mime_Part[] $alternativeSubParts */
		$alternativeSubParts = $alternativePart->getParts();
		$this->assertCount(2, $alternativeSubParts);
		$this->assertEquals('text/plain', $alternativeSubParts[0]->getType());
		$this->assertEquals('text/html', $alternativeSubParts[1]->getType());

		$inlineImagePart = $relatedSubParts[1];
		$this->assertEquals('image/png', $inlineImagePart->getType());
		$this->assertEquals('inline', $inlineImagePart->getDisposition());

		$inlineImageContentId = 'cid:' . $inlineImagePart->getContentId();
		$htmlBody = $alternativeSubParts[1]->getContents();
		$this->assertStringContainsString($inlineImageContentId, $htmlBody);

		$attachmentPart1 = $subParts[1];
		$this->assertEquals('image/png', $attachmentPart1->getType());
		$this->assertEquals('attachment', $attachmentPart1->getDisposition());

		$attachmentPart2 = $subParts[2];
		$this->assertEquals('text/plain', $attachmentPart2->getType());
		$this->assertEquals('attachment', $attachmentPart2->getDisposition());
	}

	public function testInlineAttachmentGoesIntoMultipartRelated(): void {
		$inlinePart = $this->createAttachmentPart(
			'inline image',
			file_get_contents(__DIR__ . '/../../../tests/data/nextcloud.png'),
			'image/png',
			'inline',
		);
		$inlinePart->setContentId('test-inline-image@example.com');

		$part = $this->mimeMessage->build(
			'Hello',
			'<p>Hello</p>',
			false,
			[$inlinePart],
		);

		$this->assertEquals('multipart/related', $part->getType());

		/** @var Horde_Mime_Part[] $subParts */
		$subParts = $part->getParts();
		$this->assertCount(2, $subParts);
		$this->assertEquals('inline', $subParts[1]->getDisposition());
		$this->assertEquals('image/png', $subParts[1]->getType());
		$this->assertEquals('test-inline-image@example.com', $subParts[1]->getContentId());
	}

	public function testInlineAndNormalAttachmentsSeparated(): void {
		$inlinePart = $this->createAttachmentPart(
			'inline image',
			file_get_contents(__DIR__ . '/../../../tests/data/nextcloud.png'),
			'image/png',
			'inline',
		);
		$inlinePart->setContentId('test-inline-image@example.com');

		$normalPart = $this->createAttachmentPart(
			'document.txt',
			'some content',
			'text/plain',
			'attachment',
		);

		$part = $this->mimeMessage->build(
			'Hello',
			'<p>Hello</p>',
			false,
			[$inlinePart, $normalPart],
		);

		// multipart/mixed wraps (multipart/related + normal attachment)
		$this->assertEquals('multipart/mixed', $part->getType());

		/** @var Horde_Mime_Part[] $subParts */
		$subParts = $part->getParts();
		$this->assertCount(2, $subParts);

		$this->assertEquals('multipart/related', $subParts[0]->getType());
		$this->assertEquals('attachment', $subParts[1]->getDisposition());

		$relatedSubParts = $subParts[0]->getParts();
		$this->assertEquals('inline', $relatedSubParts[1]->getDisposition());
		$this->assertEquals('test-inline-image@example.com', $relatedSubParts[1]->getContentId());
	}

	public function testRewriteSrcToCidFromDataCidAttribute(): void {
		$inlinePart = $this->createAttachmentPart(
			'inline image',
			file_get_contents(__DIR__ . '/../../../tests/data/nextcloud.png'),
			'image/png',
			'inline',
		);
		$inlinePart->setContentId('test-inline-image@example.com');

		$html = '<p><img src="https://example.com/image.png" data-cid="test-inline-image@example.com" /></p>';

		$part = $this->mimeMessage->build(
			null,
			$html,
			false,
			[$inlinePart],
		);

		$this->assertEquals('multipart/related', $part->getType());

		$relatedSubParts = $part->getParts();
		$this->assertCount(2, $relatedSubParts);

		$alternativePart = $relatedSubParts[0];
		$this->assertEquals('multipart/alternative', $alternativePart->getType());

		$alternativeSubParts = $alternativePart->getParts();
		$this->assertCount(2, $alternativeSubParts);
		$this->assertEquals('text/plain', $alternativeSubParts[0]->getType());
		$this->assertEquals('text/html', $alternativeSubParts[1]->getType());

		$htmlBody = $alternativeSubParts[1]->getContents();
		$this->assertStringContainsString('cid:test-inline-image@example.com', $htmlBody);
		$this->assertStringNotContainsString('data-cid', $htmlBody);
	}

	public function testMultipartAlternativeGreek() {
		$messageData = new NewMessageData(
			$this->account,
			new AddressList(),
			new AddressList(),
			new AddressList(),
			'Text and HTML message',
			file_get_contents(__DIR__ . '/../../../tests/data/mime-html-greek.txt'),
			[],
			true,
			false
		);

		$part = $this->mimeMessage->build(
			null,
			$messageData->getBody(),
			false,
			[],
		);

		$this->assertEquals('multipart/alternative', $part->getType());

		/** @var Horde_Mime_Part[] $subParts */
		$subParts = $part->getParts();
		$this->assertCount(2, $subParts);

		$this->assertEquals('text/plain', $subParts[0]->getType());
		$this->assertEquals('text/html', $subParts[1]->getType());

		$this->assertStringContainsString(
			'Όλοι οι άνθρωποι γεννιούνται ελεύθεροι
και ίσοι στην αξιοπρέπεια και τα
δικαιώματα. Είναι προικισμένοι με λογική
και συνείδηση, και οφείλουν να
συμπεριφέρονται μεταξύ τους με πνεύμα
αδελφοσύνης.',
			$subParts[0]->getContents(),
		);
		$this->assertStringContainsString(
			'Όλοι οι άνθρωποι γεννιούνται ελεύθεροι και ίσοι στην αξιοπρέπεια και τα δικαιώματα. Είναι προικισμένοι με λογική και συνείδηση, και οφείλουν να συμπεριφέρονται μεταξύ τους με πνεύμα αδελφοσύνης.',
			$subParts[1]->getContents()
		);
	}

	public function testEmbeddedImageSkipNonImages(): void {
		$body = file_get_contents(__DIR__ . '/../../../tests/data/mime-html-image.txt');
		// replace image/png type with a non-image type to trigger the skip.
		$body = str_replace('data:image/png;base64,', 'data:text/html;base64,', $body);

		$messageData = new NewMessageData(
			$this->account,
			new AddressList(),
			new AddressList(),
			new AddressList(),
			'Text, HTML but invalid inline image',
			$body,
			[],
			true,
			false
		);

		$part = $this->mimeMessage->build(
			$messageData->getBody(),
			$messageData->getBody(),
			false,
			[],
		);

		$this->assertEquals('multipart/alternative', $part->getType());

		/** @var Horde_Mime_Part[] $alternativeSubParts */
		$alternativeSubParts = $part->getParts();
		$this->assertCount(2, $alternativeSubParts);
		$this->assertEquals('text/plain', $alternativeSubParts[0]->getType());
		$this->assertEquals('text/html', $alternativeSubParts[1]->getType());

		$htmlBody = $alternativeSubParts[1]->getContents();
		$this->assertStringContainsString('data:text/html;base64,', $htmlBody);
		$this->assertStringNotContainsString('data-cid', $htmlBody);
	}

	private function createAttachmentPart(string $name, string $content, string $mime, string $disposition): Horde_Mime_Part {
		$part = new Horde_Mime_Part();
		$part->setCharset('us-ascii');
		$part->setDisposition($disposition);
		$part->setName($name);
		$part->setContents($content);
		$part->setType($mime);
		return $part;
	}
}
