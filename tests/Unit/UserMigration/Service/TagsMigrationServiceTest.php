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
use OCA\Mail\Tests\Unit\UserMigration\RecordsConsoleOutput;
use OCA\Mail\UserMigration\Service\TagsMigrationService;
use OCP\DB\Exception;
use OCP\IUser;
use OCP\UserMigration\IExportDestination;
use OCP\UserMigration\IImportSource;
use OCP\UserMigration\UserMigrationException;
use Symfony\Component\Console\Output\OutputInterface;

class TagsMigrationServiceTest extends TestCase {
	use RecordsConsoleOutput;

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

	public function testEstimatedExportSizeScalesWithTagCount(): void {
		$this->serviceMock->getParameter('tagMapper')
			->method('getAllTagsForUser')
			->with(self::USER_ID)
			->willReturn([$this->getTestingTag(), $this->getSuccessfulTag()]);

		$size = $this->migrationService->getEstimatedExportSize($this->user);

		self::assertSame(2 * TagsMigrationService::ESTIMATED_TAG_SIZE, $size);
	}

	public function testEstimatedExportSizeWithoutTags(): void {
		$this->serviceMock->getParameter('tagMapper')
			->method('getAllTagsForUser')
			->willReturn([]);

		self::assertSame(0, $this->migrationService->getEstimatedExportSize($this->user));
	}

	public function testExportsMultipleTags(): void {
		$tagsList = [$this->getTestingTag(), $this->getSuccessfulTag()];
		$this->exportDestination->expects(self::once())
			->method('addFileContents')
			->with(TagsMigrationService::TAGS_FILE, json_encode($tagsList));

		$this->serviceMock->getParameter('tagMapper')
			->method('getAllTagsForUser')
			->with(self::USER_ID)
			->willReturn($tagsList);
		$this->migrationService->exportTags($this->user, $this->exportDestination, $this->output);
	}

	public function testExportsNoTags(): void {
		$tagsList = [];

		$this->serviceMock->getParameter('tagMapper')
			->method('getAllTagsForUser')
			->with(self::USER_ID)
			->willReturn($tagsList);
		$this->exportDestination->expects(self::once())
			->method('addFileContents')
			->with(TagsMigrationService::TAGS_FILE, json_encode($tagsList));

		$this->migrationService->exportTags($this->user, $this->exportDestination, $this->output);
	}

	public function testImportMultipleTags(): void {
		$testingTag = $this->getTestingTag();
		$successfulTag = $this->getSuccessfulTag();
		$tagsList = [$testingTag, $successfulTag];
		$this->importSource->expects(self::once())
			->method('getFileContents')
			->with(TagsMigrationService::TAGS_FILE)
			->willReturn(json_encode($tagsList));

		$callCount = 0;
		$expectedTags = [$testingTag, $successfulTag];
		$this->serviceMock->getParameter('tagMapper')
			->expects(self::exactly(2))
			->method('insert')
			->willReturnCallback(function (Tag $tag) use (
				&$callCount,
				$expectedTags
			): Tag {
				$expectedTag = $expectedTags[$callCount];
				self::assertSame(self::USER_ID, $tag->getUserId());
				self::assertSame($expectedTag->getDisplayName(), $tag->getDisplayName());
				self::assertSame($expectedTag->getImapLabel(), $tag->getImapLabel());
				self::assertSame($expectedTag->getColor(), $tag->getColor());
				self::assertSame($expectedTag->getIsDefaultTag(), $tag->getIsDefaultTag());
				$tag->setId(random_int(10, 999));
				$callCount++;
				return $tag;
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
		$this->importSource->expects(self::once())
			->method('getFileContents')
			->with(TagsMigrationService::TAGS_FILE)
			->willReturn(json_encode($tagsList));
		$this->serviceMock->getParameter('tagMapper')
			->expects(self::never())
			->method('insert');

		$mappedTags = $this->migrationService->importTags($this->user, $this->importSource, $this->output);

		$this->assertCount(0, $mappedTags);
	}

	public function testImportNoFileIsBeingIgnored(): void {
		$this->importSource->expects(self::once())
			->method('getFileContents')
			->with(TagsMigrationService::TAGS_FILE)
			->willThrowException(new UserMigrationException());
		$this->serviceMock->getParameter('tagMapper')
			->expects(self::never())
			->method('insert');

		$mappedTags = $this->migrationService->importTags($this->user, $this->importSource, $this->output);

		$this->assertCount(0, $mappedTags);
	}

	public static function provideFileContentsWithNoTagsImported(): array {
		return [
			'empty list' => [json_encode([])],
			'invalid JSON' => ['this is not valid json {{{'],
			'JSON object instead of list' => [json_encode(['unexpected' => 'object'])],
		];
	}

	/**
	 * @dataProvider provideFileContentsWithNoTagsImported
	 */
	public function testImportEmptyOrInvalidTags(string $fileContents): void {
		$this->importSource->expects(self::once())
			->method('getFileContents')
			->with(TagsMigrationService::TAGS_FILE)
			->willReturn($fileContents);
		$this->serviceMock->getParameter('tagMapper')
			->expects(self::never())
			->method('insert');

		$mappedTags = $this->migrationService->importTags($this->user, $this->importSource, $this->output);

		$this->assertCount(0, $mappedTags);
	}

	public function testImportReusesTagsTheUserAlreadyHas(): void {
		$testingTag = $this->getTestingTag();
		$successfulTag = $this->getSuccessfulTag();
		$existingTag = $this->getSuccessfulTag();
		$existingTag->setId(77);

		$serviceMock = $this->createServiceMock(TagsMigrationService::class);
		$serviceMock->getParameter('tagMapper')
			->method('getAllTagsForUser')
			->with(self::USER_ID)
			->willReturn([$existingTag]);
		$serviceMock->getParameter('tagMapper')
			->expects(self::once())
			->method('insert')
			->willReturnCallback(static function (Tag $tag): Tag {
				$tag->setId(88);
				return $tag;
			});

		$this->importSource->method('getFileContents')
			->with(TagsMigrationService::TAGS_FILE)
			->willReturn(json_encode([$testingTag, $successfulTag]));

		$mappedTags = $serviceMock->getService()
			->importTags($this->user, $this->importSource, $this->output);

		self::assertSame(
			[$testingTag->getId() => 88, $successfulTag->getId() => 77],
			$mappedTags,
		);
	}

	public static function provideInvalidTagValues(): array {
		return [
			'empty imap label' => [['imapLabel' => '']],
			'imap label longer than the column' => [['imapLabel' => str_repeat('a', Tag::IMAP_LABEL_MAX_LENGTH + 1)]],
			'imap label with a trailing space' => [['imapLabel' => 'testing ']],
			'imap label with a trailing newline' => [['imapLabel' => "testing\n"]],
			'imap label with non-ASCII characters' => [['imapLabel' => 'tästing']],
			'imap system flag' => [['imapLabel' => '\\deleted']],
			'display name longer than the tag form allows' => [['displayName' => str_repeat('ä', Tag::DISPLAY_NAME_MAX_LENGTH + 1)]],
			'color longer than the tag form allows' => [['color' => str_repeat('f', Tag::COLOR_MAX_LENGTH + 1)]],
		];
	}

	/**
	 * @dataProvider provideInvalidTagValues
	 */
	public function testImportSkipsInvalidTagsAndKeepsGoing(array $invalidValues): void {
		$invalidTag = array_merge($this->getTestingTag()->jsonSerialize(), $invalidValues);
		$successfulTag = $this->getSuccessfulTag();
		$this->importSource->method('getFileContents')
			->with(TagsMigrationService::TAGS_FILE)
			->willReturn(json_encode([$invalidTag, $successfulTag]));
		$this->serviceMock->getParameter('tagMapper')
			->expects(self::once())
			->method('insert')
			->willReturnCallback(static function (Tag $tag): Tag {
				self::assertSame('successful', $tag->getImapLabel());
				$tag->setId(88);
				return $tag;
			});

		$mappedTags = $this->migrationService->importTags($this->user, $this->importSource, $this->output);

		self::assertSame([$successfulTag->getId() => 88], $mappedTags);
	}

	public function testImportReusesAnExistingTagEvenIfTheArchivedOneIsInvalid(): void {
		$existingTag = $this->getSuccessfulTag();
		$existingTag->setId(77);
		$archivedTag = $this->getSuccessfulTag();
		$archivedTag->setDisplayName(str_repeat('a', Tag::DISPLAY_NAME_MAX_LENGTH + 1));
		$this->serviceMock->getParameter('tagMapper')
			->method('getAllTagsForUser')
			->with(self::USER_ID)
			->willReturn([$existingTag]);
		$this->serviceMock->getParameter('tagMapper')
			->expects(self::never())
			->method('insert');
		$this->importSource->method('getFileContents')
			->with(TagsMigrationService::TAGS_FILE)
			->willReturn(json_encode([$archivedTag]));

		$mappedTags = $this->migrationService->importTags($this->user, $this->importSource, $this->output);

		self::assertSame([$archivedTag->getId() => 77], $mappedTags);
	}

	public function testImportAcceptsTagsAtTheLengthLimits(): void {
		$tag = $this->getTestingTag();
		$tag->setImapLabel('$' . str_repeat('a', Tag::IMAP_LABEL_MAX_LENGTH - 1));
		$tag->setDisplayName(str_repeat('ä', Tag::DISPLAY_NAME_MAX_LENGTH));
		$tag->setColor(str_repeat('f', Tag::COLOR_MAX_LENGTH));
		$this->importSource->method('getFileContents')
			->with(TagsMigrationService::TAGS_FILE)
			->willReturn(json_encode([$tag]));
		$this->serviceMock->getParameter('tagMapper')
			->expects(self::once())
			->method('insert')
			->willReturnCallback(static function (Tag $tag): Tag {
				$tag->setId(88);
				return $tag;
			});

		$mappedTags = $this->migrationService->importTags($this->user, $this->importSource, $this->output);

		self::assertSame([$tag->getId() => 88], $mappedTags);
	}

	public function testImportInsertsATagListedTwiceOnlyOnce(): void {
		$testingTag = $this->getTestingTag();
		$duplicateTag = $this->getSuccessfulTag();
		$duplicateTag->setImapLabel($testingTag->getImapLabel());
		$this->importSource->method('getFileContents')
			->with(TagsMigrationService::TAGS_FILE)
			->willReturn(json_encode([$testingTag, $duplicateTag]));
		$this->serviceMock->getParameter('tagMapper')
			->expects(self::once())
			->method('insert')
			->willReturnCallback(static function (Tag $tag): Tag {
				$tag->setId(88);
				return $tag;
			});

		$mappedTags = $this->migrationService->importTags($this->user, $this->importSource, $this->output);

		self::assertSame([$testingTag->getId() => 88, $duplicateTag->getId() => 88], $mappedTags);
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

	public function testImportReusesATagTheDatabaseConsidersTheSame(): void {
		$archivedTag = $this->getTestingTag();
		$archivedTag->setImapLabel('$Testing');
		$existingTag = $this->getTestingTag();
		$existingTag->setImapLabel('$testing');
		$existingTag->setId(77);
		$uniqueViolation = $this->createMock(Exception::class);
		$uniqueViolation->method('getReason')->willReturn(Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION);
		$this->importSource->method('getFileContents')
			->with(TagsMigrationService::TAGS_FILE)
			->willReturn(json_encode([$archivedTag]));
		$tagMapper = $this->serviceMock->getParameter('tagMapper');
		$tagMapper->method('getAllTagsForUser')
			->willReturn([$existingTag]);
		$tagMapper->expects(self::once())
			->method('insert')
			->willThrowException($uniqueViolation);
		$tagMapper->expects(self::once())
			->method('getTagByImapLabel')
			->with('$Testing', self::USER_ID)
			->willReturn($existingTag);

		$mappedTags = $this->migrationService->importTags($this->user, $this->importSource, $this->output);

		self::assertSame([$archivedTag->getId() => 77], $mappedTags);
	}

	public function testImportRethrowsOtherDatabaseErrors(): void {
		$databaseError = $this->createMock(Exception::class);
		$databaseError->method('getReason')->willReturn(Exception::REASON_SERVER);
		$this->importSource->method('getFileContents')
			->with(TagsMigrationService::TAGS_FILE)
			->willReturn(json_encode([$this->getTestingTag()]));
		$tagMapper = $this->serviceMock->getParameter('tagMapper');
		$tagMapper->method('insert')
			->willThrowException($databaseError);
		$tagMapper->expects(self::never())
			->method('getTagByImapLabel');
		$this->expectException(Exception::class);

		$this->migrationService->importTags($this->user, $this->importSource, $this->output);
	}

	public function testImportReportsAnInvalidTagAsANotice(): void {
		$this->recordConsoleOutput();
		$invalidTag = array_merge($this->getTestingTag()->jsonSerialize(), ['imapLabel' => 'invalid label']);
		$this->importSource->method('getFileContents')
			->with(TagsMigrationService::TAGS_FILE)
			->willReturn(json_encode([$invalidTag]));

		$this->migrationService->importTags($this->user, $this->importSource, $this->output);

		$this->assertWritten('invalid label', 0);
	}

	public function testImportReportsInvalidTagsAsANotice(): void {
		$this->recordConsoleOutput();
		$this->importSource->method('getFileContents')
			->with(TagsMigrationService::TAGS_FILE)
			->willReturn('this is not valid json {{{');

		$this->migrationService->importTags($this->user, $this->importSource, $this->output);

		$this->assertWritten('is invalid and will be skipped', 0);
	}

	public function testImportSkipsOnlyTheMalformedTag(): void {
		$this->recordConsoleOutput();
		$malformedTag = array_merge($this->getTestingTag()->jsonSerialize(), ['color' => null]);
		$successfulTag = $this->getSuccessfulTag();
		$this->importSource->method('getFileContents')
			->with(TagsMigrationService::TAGS_FILE)
			->willReturn(json_encode([$malformedTag, $successfulTag]));
		$this->serviceMock->getParameter('tagMapper')
			->expects(self::once())
			->method('insert')
			->willReturnCallback(static function (Tag $tag): Tag {
				self::assertSame('successful', $tag->getImapLabel());
				$tag->setId(88);
				return $tag;
			});

		$mappedTags = $this->migrationService->importTags($this->user, $this->importSource, $this->output);

		self::assertSame([$successfulTag->getId() => 88], $mappedTags);
		$this->assertWritten('A tag of ' . self::USER_ID . ' is invalid', 0);
	}
}
