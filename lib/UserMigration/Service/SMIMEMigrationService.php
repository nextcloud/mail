<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\UserMigration\Service;

use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Service\SmimeService;
use OCA\Mail\UserMigration\MailAccountMigrator;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IUser;
use OCP\Security\ICrypto;
use OCP\UserMigration\IExportDestination;
use OCP\UserMigration\IImportSource;
use OCP\UserMigration\UserMigrationException;
use Symfony\Component\Console\Output\OutputInterface;

class SMIMEMigrationService {
	public const SMIME_CERTIFICATE_FOLDER = MailAccountMigrator::EXPORT_ROOT . '/certificates/';
	public const SMIME_CERTIFICATE_FILES = self::SMIME_CERTIFICATE_FOLDER . MailAccountMigrator::FILENAME_PLACEHOLDER . '.json';

	public function __construct(
		private readonly SmimeService $smimeService,
		private readonly ICrypto $crypto,
		private readonly IL10N $l10n,
	) {
	}

	/**
	 * Export all S/MIME certificates added by the user
	 * on export.
	 *
	 * @throws UserMigrationException
	 * @throws ServiceException
	 * @throws \Exception
	 */
	public function exportCertificates(IUser $user, IExportDestination $exportDestination, OutputInterface $output): void {
		$certificates = $this->smimeService->findAllCertificates($user->getUID());

		foreach ($certificates as $certificate) {
			$exportContent = [
				'id' => $certificate->getId(),
				'certificate' => $this->crypto->decrypt($certificate->getCertificate()),
				'privateKey' => $certificate->getPrivateKey() !== null ? $this->crypto->decrypt($certificate->getPrivateKey()) : null,
			];

			$exportDestination->addFileContents(str_replace(MailAccountMigrator::FILENAME_PLACEHOLDER, (string)$certificate->getId(), self::SMIME_CERTIFICATE_FILES), json_encode($exportContent));
		}
	}

	/**
	 * Import all S/MIME certificates added by the user.
	 *
	 * @return array Contains the old certificate ID as array key and the new
	 *               certificate ID as value.
	 *
	 * @throws UserMigrationException
	 * @throws JsonException
	 * @throws ServiceException
	 */
	public function importCertificates(IUser $user, IImportSource $importSource): array {
		$certificatesMapping = [];

		if ($importSource->pathExists(self::SMIME_CERTIFICATE_FOLDER)) {
			$certificates = $importSource->getFolderListing(self::SMIME_CERTIFICATE_FOLDER);

			foreach ($certificates as $certificateFilePath) {
				$certificate = json_decode($importSource->getFileContents($certificateFilePath), true, flags: JSON_THROW_ON_ERROR);
				$newCertificate = $this->smimeService->createCertificate($user->getUID(), $certificate['certificate'], $certificate['privateKey']);

				$oldCertificateId = $certificate['id'];
				$certificatesMapping[$oldCertificateId] = $newCertificate->getId();
			}

		}

		return $certificatesMapping;
	}

	/**
	 * Delete all S/MIME certificates added by the user.
	 *
	 * @param IUser $user
	 * @return void
	 * @throws DoesNotExistException
	 * @throws \OCA\Mail\Exception\ServiceException
	 */
	public function deleteAllUserCertificates(IUser $user): void {
		$allCertificates = $this->smimeService->findAllCertificates($user->getUID());
		foreach ($allCertificates as $cert) {
			$this->smimeService->deleteCertificate($cert->getId(), $user->getUID());
		}
	}
}
