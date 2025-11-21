<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\TaskProcessing;

use OCP\IL10N;
use OCP\L10N\IFactory;
use OCP\TaskProcessing\EShapeType;
use OCP\TaskProcessing\ITaskType;
use OCP\TaskProcessing\ShapeDescriptor;

/**
 * This is the task processing task type for sending messages via the mail app.
 *
 * @since 5.6.0
 */
class MailSendTask implements ITaskType {

	public const ID = 'mail:send';
	private IL10N $l;

	public function __construct(
		private readonly IFactory $l10nFactory,
	) {
		$this->l = $l10nFactory->get('lib');
	}

	public function getName(): string {
		return $this->l->t('Mail Send');
	}

	public function getDescription(): string {
		return $this->l->t('Send an email using the mail app.');
	}

	public function getId(): string {
		return self::ID;
	}

	public function getInputShape(): array {
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

	public function getOutputShape(): array {
		return [
			'status_code' => new ShapeDescriptor(
				$this->l->t('Status Code'),
				$this->l->t('The status code of the email sending operation'),
				EShapeType::Number
			),
			'status_message' => new ShapeDescriptor(
				$this->l->t('Status Message'),
				$this->l->t('A message describing the result of the email sending operation'),
				EShapeType::Text
			),
		];
	}
}
