<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Mail\Controller;

use Exception;
use OCA\Mail\Db\SMimeCertificate;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Exception\SMimeCertificateParserException;
use OCA\Mail\Http\JsonResponse;
use OCA\Mail\Http\TrapError;
use OCA\Mail\Service\Attachment\UploadedFile;
use OCA\Mail\Service\SMimeService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\IRequest;
use OCP\Security\ICrypto;

class SMimeCertificatesController extends Controller {
	private ?string $userId;
	private SMimeService $certificateService;
	private ICrypto $crypto;

	public function __construct(string $appName,
								IRequest $request,
								?string $userId,
								SMimeService $certificateService,
								ICrypto $crypto) {
		parent::__construct($appName, $request);
		$this->userId = $userId;
		$this->certificateService = $certificateService;
		$this->crypto = $crypto;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @throws ServiceException
	 * @throws SMimeCertificateParserException
	 */
	#[TrapError]
	public function index(): JsonResponse {
		$certificates = $this->certificateService->findAllCertificates($this->userId);
		$certificates = array_map(function (SMimeCertificate $certificate) {
			try {
				$decryptedCertificate = $this->crypto->decrypt($certificate->getCertificate());
			} catch (Exception $e) {
				throw new ServiceException(
					'Failed to decrypt certificate: ' . $e->getMessage(),
					0,
					$e,
				);
			}
			$json = $certificate->jsonSerialize();
			$json['info'] = $this->certificateService->parseCertificate($decryptedCertificate);
			return $json;
		}, $certificates);
		return JsonResponse::success($certificates);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $id
	 * @return JsonResponse
	 *
	 * @throws DoesNotExistException
	 */
	#[TrapError]
	public function destroy(int $id): JsonResponse {
		$this->certificateService->deleteCertificate($id, $this->userId);
		return JsonResponse::success();
	}

	/**
	 * @NoAdminRequired
	 *
	 * @return JsonResponse
	 *
	 * @throws ServiceException
	 * @throws SMimeCertificateParserException
	 */
	#[TrapError]
	public function create(): JsonResponse {
		// TODO: What about PKCS12 certificates?
		// They need to be decrypted by the client because they are protected by a password.
		// We could use
		//  - https://w3c.github.io/FileAPI/#reading-data-section
		//  - https://github.com/digitalbazaar/forge#pkcs12
		// to read and decrypt a PKCS12 certificate in the browser.
		// The decrypted data could be attached to hidden file inputs and then sent to this endpoint
		// as well.

		$attachedCertificate = $this->request->getUploadedFile('certificate');
		$attachedPrivateKey = $this->request->getUploadedFile('privateKey');

		if ($attachedCertificate === null) {
			return JsonResponse::fail(
				'No certificate attached',
				Http::STATUS_UNPROCESSABLE_ENTITY,
			);
		}

		$certificateFile = new UploadedFile($attachedCertificate);
		$certificateData = file_get_contents($certificateFile->getTempPath());

		$privateKeyData = null;
		if ($attachedPrivateKey !== null) {
			$privateKeyFile = new UploadedFile($attachedPrivateKey);
			$privateKeyData = file_get_contents($privateKeyFile->getTempPath());
		}

		$certificate = $this->certificateService->createCertificate(
			$this->userId,
			$certificateData,
			$privateKeyData,
		);
		$json = $certificate->jsonSerialize();
		$json['info'] = $this->certificateService->parseCertificate($certificateData);
		return JsonResponse::success($json);
	}
}
