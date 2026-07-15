<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Listener;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\AppInfo\Application;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Listener\TaskProcessingListener;
use OCP\TaskProcessing\Events\TaskSuccessfulEvent;
use OCP\TaskProcessing\Task as TaskProcessingTask;
use OCP\TaskProcessing\TaskTypes\TextToText;
use OCP\TaskProcessing\TaskTypes\TextToTextSummary;
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
		$this->messageMapper->expects($this->never())
			->method('findByIds');

		$this->listener->handle($event);
	}

	public function testNonMessageCustomIdIsIgnored(): void {
		$task = new TaskProcessingTask(TextToText::ID, [], Application::APP_ID, 'batman', 'some-thread-id');
		$event = new TaskSuccessfulEvent($task);
		$this->messageMapper->expects($this->never())
			->method('findByIds');

		$this->listener->handle($event);
	}

	public function testThreadSummaryTaskIsIgnored(): void {
		$task = new TaskProcessingTask(TextToTextSummary::ID, [], Application::APP_ID, 'batman', 'some-thread-id');
		$event = new TaskSuccessfulEvent($task);
		$this->messageMapper->expects($this->never())
			->method('findByIds');

		$this->listener->handle($event);
	}

	public function testMessageCustomIdMissingOutput(): void {
		$task = new TaskProcessingTask(TextToText::ID, [], Application::APP_ID, 'batman', 'message:12345');
		$task->setOutput(null);
		$event = new TaskSuccessfulEvent($task);
		$this->logger->expects($this->once())
			->method('info')
			->with('Error handling task processing event output missing');

		$this->listener->handle($event);
	}

	public function testMessageSummaryIsStored(): void {
		$task = new TaskProcessingTask(TextToText::ID, [], Application::APP_ID, 'batman', 'message:12345');
		$task->setOutput(['output' => 'a summary']);
		$event = new TaskSuccessfulEvent($task);
		$message = new Message();
		$this->messageMapper->expects($this->once())
			->method('findByIds')
			->with('batman', [12345], '')
			->willReturn([$message]);
		$this->messageMapper->expects($this->once())
			->method('update')
			->with($message);

		$this->listener->handle($event);

		$this->assertSame('a summary', $message->getSummary());
	}
}
