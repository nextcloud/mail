<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service\Search;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Service\Search\FilterStringParser;
use OCA\Mail\Service\Search\Flag;

class FilterStringParserTest extends TestCase {
	private FilterStringParser $parser;

	protected function setUp(): void {
		parent::setUp();

		$this->parser = new FilterStringParser();
	}

	public function testParseEmptyFilter(): void {
		$query = $this->parser->parse('');

		$this->assertEmpty($query->getSubjects());
	}

	public function testParseNullFilter(): void {
		$query = $this->parser->parse(null);

		$this->assertEmpty($query->getSubjects());
	}

	public function testParseSubject(): void {
		$query = $this->parser->parse('subject:Rechnung');

		$this->assertEquals(['Rechnung'], $query->getSubjects());
	}

	public function testParseSubjectWithEncodedSpace(): void {
		$query = $this->parser->parse('subject:Offene%20Rechnung');

		$this->assertEquals(['Offene Rechnung'], $query->getSubjects());
	}

	public function testParseSubjectContainingColon(): void {
		$query = $this->parser->parse('subject:Re:%20Rechnung');

		$this->assertEquals(['Re: Rechnung'], $query->getSubjects());
	}

	public function testParseSubjectContainingPlus(): void {
		$query = $this->parser->parse('subject:C++%20Kurs');

		$this->assertEquals(['C++ Kurs'], $query->getSubjects());
	}

	public function testParseFromContainingPlus(): void {
		$query = $this->parser->parse('from:sebastian+news@example.com');

		$this->assertEquals(['sebastian+news@example.com'], $query->getFrom());
	}

	public function testParseTokenWithoutType(): void {
		$query = $this->parser->parse('Rechnung');

		$this->assertEmpty($query->getSubjects());
		$this->assertEmpty($query->getFrom());
	}

	public function testParseMultipleTokens(): void {
		$query = $this->parser->parse('from:alice@example.com subject:Re:%20Budget match:anyof');

		$this->assertEquals(['alice@example.com'], $query->getFrom());
		$this->assertEquals(['Re: Budget'], $query->getSubjects());
		$this->assertEquals('anyof', $query->getMatch());
	}

	public function testParseFlag(): void {
		$query = $this->parser->parse('is:unread');

		$this->assertEquals([Flag::not(Flag::SEEN)], $query->getFlags());
	}

	public function testParseTags(): void {
		$query = $this->parser->parse('tags:1,2');

		$this->assertEquals(['1', '2'], $query->getTags());
	}

	public function testParseStart(): void {
		$query = $this->parser->parse('start:1758412800');

		$this->assertEquals('1758412800', $query->getStart());
	}
}
