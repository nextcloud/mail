<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service;

use OCA\Mail\Service\SharingRestrictionService;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IGroupManager;
use OCP\Interaction\Actions\ShareAction;
use OCP\Interaction\InteractionRestrictedException;
use OCP\Interaction\Receivers\GroupReceiver;
use OCP\Interaction\Receivers\UserReceiver;
use OCP\Interaction\RestrictInteractionEvent;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Server;
use OCP\Share\IManager as IShareManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SharingRestrictionServiceTest extends TestCase {
	private IShareManager&MockObject $shareManager;
	private IGroupManager&MockObject $groupManager;
	private IUserManager&MockObject $userManager;

	/** @var callable|null */
	private $interactionListener = null;

	protected function setUp(): void {
		$this->shareManager = $this->createMock(IShareManager::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->userManager = $this->createMock(IUserManager::class);
	}

	protected function tearDown(): void {
		if ($this->interactionListener !== null) {
			Server::get(IEventDispatcher::class)->removeListener(RestrictInteractionEvent::class, $this->interactionListener);
		}
	}

	private function createLegacyService(): SharingRestrictionService {
		return new class($this->shareManager, $this->groupManager, $this->userManager) extends SharingRestrictionService {
			protected function hasInteractionApi(): bool {
				return false;
			}
		};
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

	public static function legacyUserShareProvider(): array {
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
	 * @dataProvider legacyUserShareProvider
	 */
	public function testLegacyCanShareWithUser(array $policy, array $groupsByUid, bool $expected): void {
		$this->configureSharePolicy(...$policy);
		$users = $this->configureUsers($groupsByUid);

		$this->assertSame($expected, $this->createLegacyService()->canShareWithUser('bob', $users['alice']));
	}

	public static function legacyGroupShareProvider(): array {
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
	 * @dataProvider legacyGroupShareProvider
	 */
	public function testLegacyCanShareWithGroup(array $policy, array $sharerGroups, bool $expected): void {
		$this->configureSharePolicy(...$policy);
		$this->configureUsers(['bob' => $sharerGroups]);

		$this->assertSame($expected, $this->createLegacyService()->canShareWithGroup('bob', 'group-a'));
	}

	/**
	 * Registers a listener that restricts the given receiver and records every share event.
	 *
	 * @return \ArrayObject<int, RestrictInteractionEvent>
	 */
	private function restrictReceiver(string $restrictedId): \ArrayObject {
		if (!class_exists(RestrictInteractionEvent::class)) {
			$this->markTestSkipped('RestrictInteractionEvent requires Nextcloud 34.0.2');
		}
		$events = new \ArrayObject();
		$this->interactionListener = function (RestrictInteractionEvent $event) use ($events, $restrictedId): void {
			$events[] = $event;
			if ($event->receivers[0]->getID() === $restrictedId) {
				throw new InteractionRestrictedException('restricted', 'restricted');
			}
		};
		Server::get(IEventDispatcher::class)->addListener(RestrictInteractionEvent::class, $this->interactionListener, 1000);
		return $events;
	}

	public function testInteractionApiCanShareWithUser(): void {
		$events = $this->restrictReceiver('blocked');
		$service = new SharingRestrictionService($this->shareManager, $this->groupManager, $this->userManager);
		$allowed = $this->createStub(IUser::class);
		$allowed->method('getUID')->willReturn('allowed');
		$allowed->method('isEnabled')->willReturn(true);
		$blocked = $this->createStub(IUser::class);
		$blocked->method('getUID')->willReturn('blocked');
		$blocked->method('isEnabled')->willReturn(true);

		$this->assertTrue($service->canShareWithUser('bob', $allowed));
		$this->assertFalse($service->canShareWithUser('bob', $blocked));
		$this->assertSame('bob', $events[0]->userId);
		$this->assertInstanceOf(ShareAction::class, $events[0]->action);
		$this->assertInstanceOf(UserReceiver::class, $events[0]->receivers[0]);
		$this->assertSame($allowed, $events[0]->receivers[0]->getUser());
	}

	public function testInteractionApiCanShareWithGroup(): void {
		$events = $this->restrictReceiver('blocked-group');
		$service = new SharingRestrictionService($this->shareManager, $this->groupManager, $this->userManager);

		$this->assertTrue($service->canShareWithGroup('bob', 'admin'));
		$this->assertFalse($service->canShareWithGroup('bob', 'blocked-group'));
		$this->assertSame('bob', $events[0]->userId);
		$this->assertInstanceOf(ShareAction::class, $events[0]->action);
		$this->assertInstanceOf(GroupReceiver::class, $events[0]->receivers[0]);
		$this->assertSame('admin', $events[0]->receivers[0]->getID());
	}
}
