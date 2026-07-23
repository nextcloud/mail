<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Controller;

use OCA\Mail\AppInfo\Application;
use OCA\Mail\ConfigLexicon;
use OCA\Mail\Exception\ValidationException;
use OCA\Mail\Http\JsonResponse as HttpJsonResponse;
use OCA\Mail\Service\AntiSpamService;
use OCA\Mail\Service\Classification\ClassificationSettingsService;
use OCA\Mail\Service\Provisioning\Manager as ProvisioningManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IAppConfig;
use OCP\IRequest;
use Psr\Container\ContainerInterface;
use function array_merge;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class SettingsController extends Controller {
	public function __construct(
		IRequest $request,
		private ProvisioningManager $provisioningManager,
		private AntiSpamService $antiSpamService,
		private IAppConfig $appConfig,
		private ContainerInterface $container,
		private ClassificationSettingsService $classificationSettingsService,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	public function index(): JSONResponse {
		$provisionings = $this->provisioningManager->getConfigs();
		return new JSONResponse($provisionings);
	}

	public function provision() : JSONResponse {
		$count = $this->provisioningManager->provision();
		return new JSONResponse(['count' => $count]);
	}

	public function createProvisioning(array $data): JSONResponse {
		try {
			return new JSONResponse(
				$this->provisioningManager->newProvisioning($data)
			);
		} catch (ValidationException $e) {
			return HttpJsonResponse::fail([$e->getFields()]);
		} catch (\Exception $e) {
			return HttpJsonResponse::fail([$e->getMessage()]);
		}
	}

	public function updateProvisioning(int $id, array $data): JSONResponse {
		try {
			$this->provisioningManager->updateProvisioning(array_merge($data, ['id' => $id]));
		} catch (ValidationException $e) {
			return HttpJsonResponse::fail([$e->getFields()]);
		} catch (\Exception $e) {
			return HttpJsonResponse::fail([$e->getMessage()]);
		}

		return new JSONResponse([]);
	}

	public function deprovision(int $id): JSONResponse {
		$provisioning = $this->provisioningManager->getConfigById($id);

		if ($provisioning !== null) {
			$this->provisioningManager->deprovision($provisioning);
		}

		return new JSONResponse([]);
	}

	/**
	 *
	 * @return JSONResponse
	 */
	public function setAntiSpamEmail(string $spam, string $ham): JSONResponse {
		$this->antiSpamService->setSpamEmail($spam);
		$this->antiSpamService->setHamEmail($ham);
		return new JSONResponse([]);
	}

	/**
	 * Store the credentials used for SMTP in the config
	 *
	 * @return JSONResponse
	 */
	public function deleteAntiSpamEmail(): JSONResponse {
		$this->antiSpamService->deleteConfig();
		return new JSONResponse([]);
	}

	public function setAllowNewMailAccounts(bool $allowed): void {
		$this->appConfig->setValueBool(Application::APP_ID, ConfigLexicon::ALLOW_NEW_MAIL_ACCOUNTS, $allowed);
	}

	public function setEnabledLlmProcessing(bool $enabled): JSONResponse {
		$this->appConfig->setValueBool(Application::APP_ID, ConfigLexicon::LLM_PROCESSING, $enabled);
		return new JSONResponse([]);
	}
	public function setLayoutMessageView(string $value): JSONResponse {
		$this->appConfig->setValueString(Application::APP_ID, ConfigLexicon::LAYOUT_MESSAGE_VIEW, $value);
		return new JSONResponse([]);
	}
	public function setImportanceClassificationEnabledByDefault(bool $enabledByDefault): JSONResponse {
		$this->classificationSettingsService->setClassificationEnabledByDefault($enabledByDefault);
		return new JSONResponse([]);
	}

}
