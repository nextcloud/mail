<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Listener;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\AppInfo\Application;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Listener\TaskProcessingListener;
use OCP\TaskProcessing\Events\TaskSuccessfulEvent;
use OCP\TaskProcessing\Task as TaskProcessingTask;
use OCP\TaskProcessing\TaskTypes\TextToText;
use Psr\Log\LoggerInterface;

class TaskProcessingListenerTest extends TestCase {

	/** @var TaskProcessingListener */
	private $listener;
	/** @var LoggerInterface|MockObject */
	private $logger;
	/** @var MessageMapper|MockObject */
	private $messageMapper;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(LoggerInterface::class);
		$this->messageMapper = $this->createMock(MessageMapper::class);


		$this->listener = new TaskProcessingListener(
			$this->logger,
			$this->messageMapper,
		);
	}

	public function testNoCustomIdNull(): void {
		$task = new TaskProcessingTask(TextToText::ID, [], Application::APP_ID, 'batman', null);
		$event = new TaskSuccessfulEvent($task);
		$this->logger->expects($this->once())
			->method('info')
			->with('Error handling task processing event custom id missing', ['taskCustomId' => null]);
		$this->listener->handle($event);
	}

	public function testEmptyCustomId(): void {
		$task = new TaskProcessingTask(TextToText::ID, [], Application::APP_ID, 'batman', '');
		$event = new TaskSuccessfulEvent($task);
		$this->logger->expects($this->once())
			->method('info')
			->with('Error handling task processing event custom id missing', ['taskCustomId' => '']);
		$this->listener->handle($event);
	}

	public function testCorrectCustomId(): void {
		$task = new TaskProcessingTask(TextToText::ID, [], Application::APP_ID, 'batman', 'followup:12345');
		$task->setOutput(null);
		$event = new TaskSuccessfulEvent($task);
		$this->logger->expects($this->once())
			->method('info')
			->with('Error handling task processing event output missing');
		$this->listener->handle($event);
	}

	public function testIncorrectCustomId(): void {
		$task = new TaskProcessingTask(TextToText::ID, [], Application::APP_ID, 'batman', 'followup12345');
		$event = new TaskSuccessfulEvent($task);
		$this->logger->expects($this->once())
			->method('info')
			->with('Error handling task processing event custom id missing', ['taskCustomId' => 'followup12345']);
		$this->listener->handle($event);
	}
}
