<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Unit\UserMigration\Service;

use ChristophWurst\Nextcloud\Testing\ServiceMockObject;
use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\Tag;
use OCA\Mail\UserMigration\Service\TagsMigrationService;
use OCP\IUser;
use OCP\UserMigration\IExportDestination;
use OCP\UserMigration\IImportSource;
use OCP\UserMigration\UserMigrationException;
use Symfony\Component\Console\Output\OutputInterface;

class TagsMigrationServiceTest extends TestCase {
	private const USER_ID = '123';
	private OutputInterface $output;
	private IUser $user;
	private IExportDestination $exportDestination;
	private IImportSource $importSource;
	private ServiceMockObject $serviceMock;
	private TagsMigrationService $migrationService;

	protected function setUp(): void {
		parent::setUp();

		$this->output = $this->createMock(OutputInterface::class);
		$this->exportDestination = $this->createMock(IExportDestination::class);
		$this->importSource = $this->createMock(IImportSource::class);

		$this->user = $this->createMock(IUser::class);
		$this->user->method('getUID')->willReturn(self::USER_ID);

		$this->serviceMock = $this->createServiceMock(TagsMigrationService::class);
		$this->migrationService = $this->serviceMock->getService();
	}

	public function testExportsMultipleTags(): void {
		$tagsList = [$this->getTestingTag(), $this->getSuccessfulTag()];
		$this->exportDestination->expects(self::once())->method('addFileContents')->with(TagsMigrationService::TAGS_FILE, json_encode($tagsList));

		$this->serviceMock->getParameter('tagMapper')->method('getAllTagsForUser')->with(self::USER_ID)->willReturn($tagsList);
		$this->migrationService->exportTags($this->user, $this->exportDestination, $this->output);
	}

	public function testExportsNoTags(): void {
		$tagsList = [];

		$this->serviceMock->getParameter('tagMapper')->method('getAllTagsForUser')->with(self::USER_ID)->willReturn($tagsList);
		$this->exportDestination->expects(self::once())->method('addFileContents')->with(TagsMigrationService::TAGS_FILE, json_encode($tagsList));

		$this->migrationService->exportTags($this->user, $this->exportDestination, $this->output);
	}

	public function testImportMultipleTags(): void {
		$testingTag = $this->getTestingTag();
		$successfulTag = $this->getSuccessfulTag();
		$tagsList = [$testingTag, $successfulTag];
		$this->importSource->expects(self::once())->method('getFileContents')->with(TagsMigrationService::TAGS_FILE)->willReturn(json_encode($tagsList));

		$this->serviceMock->getParameter('tagMapper')->expects(self::exactly(2))->method('insert')->with(self::callback(function (Tag $writtenTag) use ($testingTag, $successfulTag): bool {
			if ($this->userIdMatches($writtenTag)
				&& $this->displayNameMatches($writtenTag)
				&& $this->imapLabelMatches($writtenTag)
				&& $this->colorMatches($writtenTag)
				&& $this->isDefaultTagMatches($writtenTag)
			) {
				return true;
			} else {
				return false;
			}
		}))->willReturnCallback(function ($test) {
			$test->setId(random_int(10, 999));
			return $test;
		});

		$mappedTags = $this->migrationService->importTags($this->user, $this->importSource, $this->output);

		$this->assertCount(2, $mappedTags);
		$this->assertArrayHasKey($testingTag->getId(), $mappedTags);
		$this->assertIsInt($mappedTags[$testingTag->getId()]);
		$this->assertArrayHasKey($successfulTag->getId(), $mappedTags);
		$this->assertIsInt($mappedTags[$successfulTag->getId()]);
	}

	public function testImportNoTags(): void {
		$tagsList = [];
		$this->importSource->expects(self::once())->method('getFileContents')->with(TagsMigrationService::TAGS_FILE)->willReturn(json_encode($tagsList));
		$this->serviceMock->getParameter('tagMapper')->expects(self::never())->method('insert');
		$mappedTags = $this->migrationService->importTags($this->user, $this->importSource, $this->output);
		$this->assertCount(0, $mappedTags);
	}

	public function testImportNoFileIsBeingIgnored(): void {
		$this->importSource->expects(self::once())->method('getFileContents')->with(TagsMigrationService::TAGS_FILE)->willThrowException(new UserMigrationException());
		$this->serviceMock->getParameter('tagMapper')->expects(self::never())->method('insert');
		$mappedTags = $this->migrationService->importTags($this->user, $this->importSource, $this->output);
		$this->assertCount(0, $mappedTags);
	}

	private function getTestingTag(): Tag {
		$testingTag = new Tag();

		$testingTag->setId(1);
		$testingTag->setUserId(self::USER_ID);
		$testingTag->setImapLabel('testing');
		$testingTag->setDisplayName('Testing');
		$testingTag->setColor('#fff');
		$testingTag->setIsDefaultTag(false);

		return $testingTag;
	}

	private function getSuccessfulTag(): Tag {
		$successfulTag = new Tag();

		$successfulTag->setId(2);
		$successfulTag->setUserId(self::USER_ID);
		$successfulTag->setImapLabel('successful');
		$successfulTag->setDisplayName('Successful');
		$successfulTag->setColor('#fff');
		$successfulTag->setIsDefaultTag(false);

		return $successfulTag;
	}

	private function userIdMatches(Tag $tag): bool {
		return $tag->getUserId() === self::USER_ID;
	}

	private function displayNameMatches(Tag $tag): bool {
		$testing = $this->getTestingTag();
		$successful = $this->getSuccessfulTag();

		return $tag->getDisplayName() === $testing->getDisplayName() || $tag->getDisplayName() === $successful->getDisplayName();
	}

	private function imapLabelMatches(Tag $tag): bool {
		$testing = $this->getTestingTag();
		$successful = $this->getSuccessfulTag();

		return $tag->getImapLabel() === $testing->getImapLabel() || $tag->getImapLabel() === $successful->getImapLabel();
	}

	private function colorMatches(Tag $tag): bool {
		$testing = $this->getTestingTag();
		$successful = $this->getSuccessfulTag();

		return $tag->getColor() === $testing->getColor() || $tag->getColor() === $successful->getColor();
	}

	private function isDefaultTagMatches(Tag $tag): bool {
		$testing = $this->getTestingTag();
		$successful = $this->getSuccessfulTag();

		return $tag->getIsDefaultTag() === $testing->getIsDefaultTag() || $tag->getIsDefaultTag() === $successful->getIsDefaultTag();
	}
}
