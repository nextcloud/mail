<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\IMAP\Threading;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\IMAP\Threading\Container;
use OCA\Mail\IMAP\Threading\Message;

class ContainerTest extends TestCase {
	public function testEmpty(): void {
		$container = Container::empty();

		$this->assertFalse($container->hasMessage());
		$this->assertFalse($container->hasParent());
		$this->assertFalse($container->hasChildren());
	}

	public function testWithMessage(): void {
		$message = $this->createMock(Message::class);

		$container = Container::with($message);

		$this->assertTrue($container->hasMessage());
		$this->assertFalse($container->hasParent());
		$this->assertFalse($container->hasChildren());
	}

	public function testFillWithMessage(): void {
		$message = $this->createMock(Message::class);

		$container = Container::with($message);

		$this->assertTrue($container->hasMessage());
		$this->assertEquals(Container::with($message), $container);
	}

	public function testSetParent(): void {
		$parent = Container::empty();
		$container = Container::empty();

		$container->setParent($parent);

		$this->assertTrue($container->hasParent());
		$this->assertCount(1, $parent->getChildren());
	}

	public function testReSetParent(): void {
		$parent1 = Container::empty();
		$parent2 = Container::empty();
		$container = Container::empty();

		$container->setParent($parent1);
		$container->setParent($parent2);

		$this->assertSame($parent2, $container->getParent());
		$this->assertTrue($container->hasParent());
		$this->assertEmpty($parent1->getChildren());
		$this->assertCount(1, $parent2->getChildren());
	}

	public function testHasNoAncestor(): void {
		$unrelated = Container::empty();
		$container = Container::empty();

		$hasAncestor = $container->hasAncestor($unrelated);

		$this->assertFalse($hasAncestor);
	}

	public function testHasAncestor(): void {
		$grandmother = Container::empty();
		$mother = Container::empty();
		$container = Container::empty();
		$container->setParent($mother);
		$mother->setParent($grandmother);

		$hasMother = $container->hasAncestor($mother);
		$hasGrandMother = $container->hasAncestor($grandmother);

		$this->assertTrue($hasMother);
		$this->assertTrue($hasGrandMother);
	}

	public function testUnlink(): void {
		$parent = Container::empty();
		$container = Container::empty();

		$container->setParent($parent);
		$container->unlink();

		$this->assertFalse($container->hasParent());
		$this->assertEmpty($parent->getChildren());
	}
}
