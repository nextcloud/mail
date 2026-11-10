<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service;

use OCA\Mail\Service\SharingRestrictionService;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Share\IManager as IShareManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SharingRestrictionServiceTest extends TestCase {
	private IShareManager&MockObject $shareManager;
	private IGroupManager&MockObject $groupManager;
	private IUserManager&MockObject $userManager;

	private SharingRestrictionService $service;

	protected function setUp(): void {
		$this->shareManager = $this->createMock(IShareManager::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->service = new SharingRestrictionService($this->shareManager, $this->groupManager, $this->userManager);
	}

	private function configureSharePolicy(
		bool $apiEnabled = true,
		bool $disabledForUser = false,
		bool $groupSharing = true,
		bool $groupMembersOnly = false,
		array $excludedGroups = [],
	): void {
		$this->shareManager->method('shareApiEnabled')->willReturn($apiEnabled);
		$this->shareManager->method('sharingDisabledForUser')->willReturn($disabledForUser);
		$this->shareManager->method('allowGroupSharing')->willReturn($groupSharing);
		$this->shareManager->method('shareWithGroupMembersOnly')->willReturn($groupMembersOnly);
		$this->shareManager->method('shareWithGroupMembersOnlyExcludeGroupsList')->willReturn($excludedGroups);
	}

	/**
	 * @param array<string, string[]> $groupsByUid
	 * @return array<string, IUser>
	 */
	private function configureUsers(array $groupsByUid): array {
		$users = [];
		foreach (array_keys($groupsByUid) as $uid) {
			$user = $this->createStub(IUser::class);
			$user->method('getUID')->willReturn($uid);
			$users[$uid] = $user;
		}
		$this->userManager->method('get')
			->willReturnCallback(fn (string $uid) => $users[$uid] ?? null);
		$this->groupManager->method('getUserGroupIds')
			->willReturnCallback(fn (IUser $user) => $groupsByUid[$user->getUID()]);
		return $users;
	}

	public static function userShareProvider(): array {
		return [
			'no restrictions' => [[], ['bob' => ['group-a'], 'alice' => ['group-b']], true],
			'common group' => [['groupMembersOnly' => true, 'excludedGroups' => ['everyone']], ['bob' => ['everyone', 'team'], 'alice' => ['everyone', 'team']], true],
			'sharing api disabled' => [['apiEnabled' => false], ['bob' => ['team'], 'alice' => ['team']], false],
			'sharing disabled for sharer' => [['disabledForUser' => true], ['bob' => ['team'], 'alice' => ['team']], false],
			'no common group' => [['groupMembersOnly' => true], ['bob' => ['group-a'], 'alice' => ['group-b']], false],
			'only common group is excluded' => [['groupMembersOnly' => true, 'excludedGroups' => ['everyone']], ['bob' => ['everyone', 'group-a'], 'alice' => ['everyone', 'group-b']], false],
		];
	}

	/**
	 * @dataProvider userShareProvider
	 */
	public function testCanShareWithUser(array $policy, array $groupsByUid, bool $expected): void {
		$this->configureSharePolicy(...$policy);
		$users = $this->configureUsers($groupsByUid);

		$this->assertSame($expected, $this->service->canShareWithUser('bob', $users['alice']));
	}

	public static function groupShareProvider(): array {
		return [
			'no restrictions' => [[], ['group-b'], true],
			'own group' => [['groupMembersOnly' => true], ['group-a'], true],
			'sharing api disabled' => [['apiEnabled' => false], ['group-a'], false],
			'sharing disabled for sharer' => [['disabledForUser' => true], ['group-a'], false],
			'group sharing disabled' => [['groupSharing' => false], ['group-a'], false],
			'not a member' => [['groupMembersOnly' => true], ['group-b'], false],
			'member of excluded group' => [['groupMembersOnly' => true, 'excludedGroups' => ['group-a']], ['group-a'], false],
		];
	}

	/**
	 * @dataProvider groupShareProvider
	 */
	public function testCanShareWithGroup(array $policy, array $sharerGroups, bool $expected): void {
		$this->configureSharePolicy(...$policy);
		$this->configureUsers(['bob' => $sharerGroups]);

		$this->assertSame($expected, $this->service->canShareWithGroup('bob', 'group-a'));
	}
}
