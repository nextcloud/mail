<?php

declare(strict_types=1);

/**
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
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
		$this->account = new Account($this->createMock(MailAccount::class));
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
			$messageData->isHtml(),
			$messageData->getBody(),
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
			$messageData->isHtml(),
			$messageData->getBody(),
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
			$messageData->isHtml(),
			$messageData->getBody(),
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

		$attachment1 = $this->createAttachmentDetails(
			'nextcloud logo',
			file_get_contents(__DIR__ . '/../../../tests/data/nextcloud.png'),
			'image/png'
		);

		$part = $this->mimeMessage->build(
			$messageData->isHtml(),
			$messageData->getBody(),
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

		$attachment1 = $this->createAttachmentDetails(
			'nextcloud logo',
			file_get_contents(__DIR__ . '/../../../tests/data/nextcloud.png'),
			'image/png'
		);

		$attachment2 = $this->createAttachmentDetails(
			'sensitive animals logo',
			file_get_contents(__DIR__ . '/../../../tests/data/test.txt'),
			'text/plain'
		);

		$part = $this->mimeMessage->build(
			$messageData->isHtml(),
			$messageData->getBody(),
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
			$messageData->isHtml(),
			$messageData->getBody(),
			[],
		);

		$this->assertEquals('multipart/alternative', $part->getType());

		/** @var Horde_Mime_Part[] $subParts */
		$subParts = $part->getParts();
		$this->assertCount(2, $subParts);

		$this->assertEquals('text/plain', $subParts[0]->getType());
		$this->assertEquals('text/html', $subParts[1]->getType());

		$this->assertStringContainsString(
			"Όλοι οι άνθρωποι γεννιούνται ελεύθεροι
και ίσοι στην αξιοπρέπεια και τα
δικαιώματα. Είναι προικισμένοι με λογική
και συνείδηση, και οφείλουν να
συμπεριφέρονται μεταξύ τους με πνεύμα
αδελφοσύνης.",
			$subParts[0]->getContents(),
		);
		$this->assertStringContainsString(
			'Όλοι οι άνθρωποι γεννιούνται ελεύθεροι και ίσοι στην αξιοπρέπεια και τα δικαιώματα. Είναι προικισμένοι με λογική και συνείδηση, και οφείλουν να συμπεριφέρονται μεταξύ τους με πνεύμα αδελφοσύνης.',
			$subParts[1]->getContents()
		);
	}

	/**
	 * OCA\Mail\Model\Message::createAttachmentDetails
	 *
	 * @param string $name
	 * @param string $content
	 * @param string $mime
	 * @return void
	 */
	private function createAttachmentDetails(string $name, string $content, string $mime): Horde_Mime_Part {
		$part = new Horde_Mime_Part();
		$part->setCharset('us-ascii');
		$part->setDisposition('attachment');
		$part->setName($name);
		$part->setContents($content);
		$part->setType($mime);
		return $part;
	}
}
