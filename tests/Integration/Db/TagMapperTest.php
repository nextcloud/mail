<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Integration\Db;

use ChristophWurst\Nextcloud\Testing\DatabaseTransaction;
use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\Tag;
use OCA\Mail\Db\TagMapper;
use OCP\IDBConnection;
use OCP\IL10N;

class TagMapperTest extends TestCase {
	use DatabaseTransaction;

	private IDBConnection $db;

	private TagMapper $mapper;

	protected function setUp(): void {
		parent::setUp();

		$this->db = \OCP\Server::get(IDBConnection::class);
		$this->mapper = new TagMapper(
			$this->db,
			$this->createMock(IL10N::class),
		);

		$qb = $this->db->getQueryBuilder();
		$qb->delete('mail_message_tags')->executeStatement();
		$qb->delete($this->mapper->getTableName())->executeStatement();
	}

	public function testTagMessageSetsUserIdWhenInsertingNewTag(): void {
		$tag = new Tag();
		$tag->setImapLabel('project-x');
		$tag->setDisplayName('project-x');
		$tag->setColor('');
		$tag->setIsDefaultTag(false);

		$this->mapper->tagMessage($tag, '<project-x@example.com>', 'sync-user');

		$storedTag = $this->mapper->getTagByImapLabel('project-x', 'sync-user');

		self::assertSame('sync-user', $storedTag->getUserId());
		self::assertSame('project-x', $storedTag->getImapLabel());
	}
}
