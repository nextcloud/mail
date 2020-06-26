<?php

declare(strict_types=1);

/**
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
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
