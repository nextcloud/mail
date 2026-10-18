<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\JMAP;

use ChristophWurst\Nextcloud\Testing\TestCase;
use JmapClient\Responses\Mail\MailboxParameters as MailboxParametersResponse;
use JmapClient\Responses\Mail\MailboxPermissions;
use OCA\Mail\JMAP\JmapMailboxAdapter;
use PHPUnit\Framework\MockObject\MockObject;

class JmapMailboxAdapterTest extends TestCase {
	private JmapMailboxAdapter $adapter;

	protected function setUp(): void {
		parent::setUp();

		$this->adapter = new JmapMailboxAdapter();
	}

	/**
	 * @param array<string, bool> $rights permission => granted; null means "no rights object"
	 * @param array<string, mixed> $values
	 */
	private function source(array $values = [], ?array $rights = null): MailboxParametersResponse&MockObject {
		$source = $this->createMock(MailboxParametersResponse::class);

		$defaults = [
			'id' => 'remote-1',
			'label' => 'Folder',
			'in' => null,
			'role' => null,
			'subscribed' => null,
			'objectsTotal' => null,
			'objectsUnseen' => null,
		];
		foreach (array_merge($defaults, $values) as $method => $value) {
			$source->method($method)->willReturn($value);
		}

		$source->method('rights')->willReturn($rights === null ? null : $this->rights($rights));

		return $source;
	}

	/**
	 * @param array<string, bool> $granted
	 */
	private function rights(array $granted): MailboxPermissions&MockObject {
		$permissions = $this->createMock(MailboxPermissions::class);
		foreach (['readItems', 'addItems', 'removeItems', 'setSeen', 'setKeywords', 'createChild', 'rename', 'delete', 'submit'] as $right) {
			$permissions->method($right)->willReturn($granted[$right] ?? false);
		}
		return $permissions;
	}

	public function testMapsBasicMetadata(): void {
		$source = $this->source([
			'id' => 'mbx-7',
			'label' => 'Receipts',
			'in' => 'parent-1',
			'objectsTotal' => 12,
			'objectsUnseen' => 3,
		]);

		$mailbox = $this->adapter->convertToMailbox($source);

		self::assertSame('Receipts', $mailbox->getName());
		self::assertSame('mbx-7', $mailbox->getRemoteId());
		self::assertSame('parent-1', $mailbox->getRemoteParentId());
		self::assertSame(12, $mailbox->getMessages());
		self::assertSame(3, $mailbox->getUnseen());
		self::assertSame(md5('mbx-7'), $mailbox->getNameHash());
	}

	public function testNullIdDoesNotBreakNameHash(): void {
		$mailbox = $this->adapter->convertToMailbox($this->source(['id' => null, 'label' => 'X']));

		self::assertSame(md5(''), $mailbox->getNameHash());
	}

	public function testRoleIsMappedToSpecialUse(): void {
		$mailbox = $this->adapter->convertToMailbox($this->source(['role' => 'sent'], ['readItems' => true]));

		self::assertSame(['sent'], json_decode($mailbox->getSpecialUse(), true));
	}

	public function testFlaggedRoleMapsToImportantSpecialUse(): void {
		$mailbox = $this->adapter->convertToMailbox($this->source(['role' => 'flagged'], ['readItems' => true]));

		self::assertSame(['flagged'], json_decode($mailbox->getSpecialUse(), true));
	}

	public function testReadableMailboxIsSelectable(): void {
		$mailbox = $this->adapter->convertToMailbox($this->source([], ['readItems' => true]));

		self::assertTrue($mailbox->getSelectable());
		self::assertStringNotContainsString('\\noselect', $mailbox->getAttributes());
	}

	public function testUnreadableMailboxIsNotSelectableAndNoselect(): void {
		$mailbox = $this->adapter->convertToMailbox($this->source([], ['readItems' => false]));

		self::assertFalse($mailbox->getSelectable());
		self::assertStringContainsString('\\noselect', $mailbox->getAttributes());
	}

	public function testAclStringIsBuiltFromRights(): void {
		$mailbox = $this->adapter->convertToMailbox($this->source([], [
			'readItems' => true,
			'addItems' => true,
			'setSeen' => true,
		]));

		$acls = $mailbox->getMyAcls();
		self::assertStringContainsString('l', $acls);
		self::assertStringContainsString('r', $acls);
		self::assertStringContainsString('i', $acls);
		self::assertStringContainsString('s', $acls);
	}

	public function testFullRightsGrantAdministerFlag(): void {
		$mailbox = $this->adapter->convertToMailbox($this->source([], [
			'readItems' => true,
			'createChild' => true,
			'rename' => true,
			'delete' => true,
		]));

		self::assertStringContainsString('a', $mailbox->getMyAcls());
	}

	public function testNoRightsObjectYieldsNullAcl(): void {
		$mailbox = $this->adapter->convertToMailbox($this->source([], null));

		self::assertNull($mailbox->getMyAcls());
	}

	public function testNoGrantedRightsYieldsNullAcl(): void {
		$mailbox = $this->adapter->convertToMailbox($this->source([], []));

		self::assertNull($mailbox->getMyAcls());
	}

	public function testSubscribedAttributeReflectsResponse(): void {
		$subscribed = $this->adapter->convertToMailbox($this->source(['subscribed' => true], ['readItems' => true]));
		$unsubscribed = $this->adapter->convertToMailbox($this->source(['subscribed' => false], ['readItems' => true]));

		self::assertStringContainsString('\\subscribed', $subscribed->getAttributes());
		self::assertStringNotContainsString('\\subscribed', $unsubscribed->getAttributes());
	}
}
