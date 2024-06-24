<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Unit\Controller;

use ChristophWurst\Nextcloud\Testing\ServiceMockObject;
use OCA\Mail\Controller\TagsController;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Service\MailManager;
use OCA\Mail\Tests\Integration\TestCase;

class TagsControllerTest extends TestCase {
	/** @var ServiceMockObject */
	private $serviceMock;

	/** @var MailManager */
	private $mailManager;

	/** @var TagsController */
	private $tagsController;

	protected function setUp(): void {
		parent::setUp();
		$this->serviceMock = $this->createServiceMock(
			TagsController::class,
			['UserId' => '1']
		);
		$this->mailManager = $this->serviceMock->getParameter('mailManager');
		$this->tagsController = $this->serviceMock->getService();
	}

	public function testCreateInvalidDisplayName(): void {
		$this->mailManager->expects($this->never())
			->method('createTag');

		$this->expectException(ClientException::class);
		$this->expectExceptionMessage('The maximum length for displayName is 128');

		$displayName = str_repeat('Hello', 30);
		$this->tagsController->create($displayName, '#0082c9');
	}

	public function testCreateInvalidColor(): void {
		$this->mailManager->expects($this->never())
			->method('createTag');

		$this->expectException(ClientException::class);
		$this->expectExceptionMessage('The maximum length for color is 9');

		$color = str_repeat('#0082c9', 30);
		$this->tagsController->create('Hello', $color);
	}

	public function testCreate(): void {
		$this->mailManager->expects($this->once())
			->method('createTag');

		$this->tagsController->create('Hello', '#0082c9');
	}

	public function testUpdateInvalidDisplayName(): void {
		$this->mailManager->expects($this->never())
			->method('updateTag');

		$this->expectException(ClientException::class);
		$this->expectExceptionMessage('The maximum length for displayName is 128');

		$displayName = str_repeat('Hello', 30);
		$this->tagsController->update(100, $displayName, '#0082c9');
	}

	public function testUpdateInvalidColor(): void {
		$this->mailManager->expects($this->never())
			->method('updateTag');

		$this->expectException(ClientException::class);
		$this->expectExceptionMessage('The maximum length for color is 9');

		$color = str_repeat('#0082c9', 30);
		$this->tagsController->update(100, 'Hello', $color);
	}

	public function testUpdate(): void {
		$this->mailManager->expects($this->once())
			->method('updateTag');

		$this->tagsController->update(100, 'Hello', '#0082c9');
	}
}
