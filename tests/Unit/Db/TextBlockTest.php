<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Db;

use OCA\Mail\Db\TextBlock;
use PHPUnit\Framework\TestCase;

final class TextBlockTest extends TestCase {
	private TextBlock $entity;

	protected function setUp(): void {
		$this->entity = new TextBlock();
	}

	public function testSetGetOwner(): void {
		$owner = 'user@example.com';

		$this->entity->setOwner($owner);
		$result = $this->entity->getOwner();

		$this->assertSame($owner, $result);
		$this->assertIsString($result);
	}

	public function testSetGetTitle(): void {
		$title = 'My Text Block';

		$this->entity->setTitle($title);
		$result = $this->entity->getTitle();

		$this->assertSame($title, $result);
		$this->assertIsString($result);
	}

	public function testSetGetContent(): void {
		$content = <<<'EOF'
			This is the block content with multiple lines.
			It can contain special characters: äöü, émojis, etc.
			EOF;

		$this->entity->setContent($content);
		$result = $this->entity->getContent();

		$this->assertSame($content, $result);
		$this->assertIsString($result);
	}

	public function testSetGetPreview(): void {
		$preview = 'This is a preview...';

		$this->entity->setPreview($preview);
		$result = $this->entity->getPreview();

		$this->assertSame($preview, $result);
		$this->assertIsString($result);
	}

	public function testSetEmptyStrings(): void {
		$this->entity->setOwner('');
		$this->entity->setTitle('');
		$this->entity->setContent('');
		$this->entity->setPreview('');

		$this->assertSame('', $this->entity->getOwner());
		$this->assertSame('', $this->entity->getTitle());
		$this->assertSame('', $this->entity->getContent());
		$this->assertSame('', $this->entity->getPreview());
	}

	public function testJsonSerializeWithoutId(): void {
		$this->entity->setOwner('admin');
		$this->entity->setTitle('Greeting');
		$this->entity->setContent('Hello world');
		$this->entity->setPreview('Hello...');

		$json = $this->entity->jsonSerialize();

		$this->assertIsArray($json);
		$this->assertArrayHasKey('id', $json);
		$this->assertArrayHasKey('owner', $json);
		$this->assertArrayHasKey('title', $json);
		$this->assertArrayHasKey('content', $json);
		$this->assertArrayHasKey('preview', $json);
		$this->assertSame('admin', $json['owner']);
		$this->assertSame('Greeting', $json['title']);
		$this->assertSame('Hello world', $json['content']);
		$this->assertSame('Hello...', $json['preview']);
	}

	public function testSetJsonSerializeWithSpecialCharacters(): void {
		$this->entity->setOwner('user+tag@domain.com');
		$this->entity->setTitle('Test "quoted" & special chars');
		$this->entity->setContent('Content with <html> & {json} and "quotes"');
		$this->entity->setPreview('Preview with émojis 🎉');

		$json = $this->entity->jsonSerialize();

		$this->assertSame('user+tag@domain.com', $json['owner']);
		$this->assertSame('Test "quoted" & special chars', $json['title']);
		$this->assertStringContainsString('<html>', (string)$json['content']);
		$this->assertStringContainsString('🎉', (string)$json['preview']);
	}

	public function testMultipleSetCallsOverwriteValue(): void {
		$this->entity->setTitle('First');
		$this->entity->setTitle('Second');
		$this->entity->setTitle('Final');

		$this->assertSame('Final', $this->entity->getTitle());
	}

	public function testAllPropertiesIndependent(): void {
		$this->entity->setOwner('owner1');
		$this->entity->setTitle('title1');
		$this->entity->setContent('content1');
		$this->entity->setPreview('preview1');

		$this->assertSame('owner1', $this->entity->getOwner());
		$this->assertSame('title1', $this->entity->getTitle());
		$this->assertSame('content1', $this->entity->getContent());
		$this->assertSame('preview1', $this->entity->getPreview());

		$this->entity->setOwner('owner2');

		$this->assertSame('owner2', $this->entity->getOwner());
		$this->assertSame('title1', $this->entity->getTitle());
	}

	public function testLongStringValues(): void {
		$longString = str_repeat('a', 10000);

		$this->entity->setContent($longString);
		$result = $this->entity->getContent();

		$this->assertSame($longString, $result);
		$this->assertSame(10000, strlen($result));
	}

	public function testUnicodeAndMultibyteCharacters(): void {
		$unicode = '日本語テキスト Ελληνικά Русский العربية';

		$this->entity->setContent($unicode);
		$result = $this->entity->getContent();

		$this->assertSame($unicode, $result);
		$this->assertStringContainsString('日本語', $result);
		$this->assertStringContainsString('Ελληνικά', $result);
		$this->assertStringContainsString('Русский', $result);
		$this->assertStringContainsString('العربية', $result);
	}

	public function testNewlineAndWhitespacePreservation(): void {
		$content = <<<'EOF'
			Line 1
			Line 2
			Line 3	Tabbed
			EOF;

		$this->entity->setContent($content);
		$result = $this->entity->getContent();

		$this->assertSame($content, $result);
		$this->assertStringContainsString("\n", $result);
		$this->assertStringContainsString("\t", $result);
	}
}
