<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\TaskProcessing;

use OCA\Mail\Provider\Command\MessageSend;
use OCP\IL10N;
use OCP\Mail\Provider\Message;
use OCP\TaskProcessing\EShapeType;
use OCP\TaskProcessing\ISynchronousProvider;
use OCP\TaskProcessing\ShapeDescriptor;
use Psr\Container\ContainerInterface;

/**
 * This is the task processing provider  for sending messages via the mail app.
 *
 * @since 5.6.0
 */
class MailSendProvider implements ISynchronousProvider {

	public function __construct(
		private readonly ContainerInterface $container,
		private readonly IL10N $l,
	) {
	}

	public function getId(): string {
		return 'mail:send';
	}

	public function getName(): string {
		return 'Mail Send Provider';
	}

	public function getTaskTypeId(): string {
		return 'mail:send';
	}

	public function getExpectedRuntime(): int {
		return 60;
	}

	public function getOptionalInputShape(): array {
		return [
			'userId' => new ShapeDescriptor(
				$this->l->t('User ID'),
				$this->l->t('The ID of the user sending the email'),
				EShapeType::Text
			),
			'serviceId' => new ShapeDescriptor(
				$this->l->t('Service ID'),
				$this->l->t('The ID of the service/account sending the email'),
				EShapeType::Number
			),
			'message' => new ShapeDescriptor(
				$this->l->t('Message'),
				$this->l->t('The email message to be sent (OCP\Mail\IMessage)'),
				EShapeType::Object
			),
			'options' => new ShapeDescriptor(
				$this->l->t('Options'),
				$this->l->t('Additional options for sending the email'),
				EShapeType::Array
			),
		];

	}

	public function getOptionalOutputShape(): array {
		return [];
	}

	public function getInputShapeEnumValues(): array {
		return [];
	}

	public function getInputShapeDefaults(): array {
		return [];
	}

	public function getOptionalInputShapeEnumValues(): array {
		return [];
	}

	public function getOptionalInputShapeDefaults(): array {
		return [];
	}

	public function getOutputShapeEnumValues(): array {
		return [];
	}

	public function getOptionalOutputShapeEnumValues(): array {
		return [];
	}

	public function process(?string $userId, array $input, callable $reportProgress): array {
		// extract parameters
		$userId = $input['userId'] ?? null;
		$serviceId = $input['serviceId'] ?? null;
		$options = $input['options'] ?? [];
		if (isset($input['message'])) {
			$message = new Message();
			$message->jsonDeserialize((array)$input['message']);
		} else {
			$message = null;
		}
		// validate parameters
		if ($userId === null || empty($userId)) {
			throw new \InvalidArgumentException('Invalid or missing userId');
		}
		if ($serviceId === null || empty($serviceId) || $serviceId <= 0) {
			throw new \InvalidArgumentException('Invalid or missing serviceId');
		}
		if (!$message instanceof Message) {
			throw new \InvalidArgumentException('Invalid or missing message');
		}
		// perform task
		/** @var MessageSend $cmd */
		$cmd = $this->container->get(MessageSend::class);
		$cmd->perform($userId, (string)$serviceId, $message, $options);

		return [];
	}
}
