<?php

declare(strict_types=1);

namespace OCA\Mail\Service;

use OCA\Mail\Db\MessageTemplate;
use OCA\Mail\Db\MessageTemplateMapper;
use OCA\Mail\Exception\ClientException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\DB\Exception;

class MessageTemplateService {
	public function __construct(
		private MessageTemplateMapper $messageTemplateMapper,
	) {
	}

	/**
	 * @throws Exception
	 */
	public function findMessageTemplates(string $userId): array {
		return $this->messageTemplateMapper->findAll($userId);
	}

	/**
	 * @throws ClientException
	 */
	public function createMessageTemplate(string $userId, string $title, string $body): MessageTemplate {
		$this->validateFields($title, $body);
		try {
			return $this->messageTemplateMapper->saveTemplate($userId, $title, $body);
		} catch (Exception $e) {
			throw new ClientException('Failed to save message template');
		}
	}

	/**
	 * @throws DoesNotExistException
	 * @throws ClientException
	 */
	public function updateMessageTemplate(string $userId, int $id, string $title, string $body): MessageTemplate {
		$messageTemplate = $this->messageTemplateMapper->find($id);
		if ($userId !== $messageTemplate->getUserId()) {
			throw new ClientException('Message template does not belong to this user');
		}

		try {
			return $this->messageTemplateMapper->updateTemplate($id, $title, $body);
		} catch (DoesNotExistException $e) {
			throw new ClientException('Message template does not exist');
		}
	}

	/**
	 * @throws DoesNotExistException
	 * @throws ClientException
	 */
	public function deleteMessageTemplate(string $userId, int $id): MessageTemplate {
		$messageTemplate = $this->messageTemplateMapper->find($id);
		if ($userId !== $messageTemplate->getUserId()) {
			throw new ClientException('Message template does not belong to this user');
		}

		try {
			return $this->messageTemplateMapper->deleteTemplate($id);
		} catch (DoesNotExistException $e) {
			throw new ClientException('Message template does not exist');
		}
	}

	private function validateFields(string $title, string $body): void {
		if (trim($title) === '') {
			throw new ClientException('Template title can not be empty');
		}

		if (trim($body) === '') {
			throw new ClientException('Template body can not be empty');
		}
	}
}
