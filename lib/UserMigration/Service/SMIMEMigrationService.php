<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\UserMigration\Service;

use Exception;
use JsonException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Service\SmimeService;
use OCA\Mail\UserMigration\MailAccountMigrator;
use OCP\AppFramework\Db\DoesNotExistException;
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
	 * @throws Exception
	 */
	public function exportCertificates(IUser $user, IExportDestination $exportDestination, OutputInterface $output): void {
		$output->writeln(
			$this->l10n->t('Exporting S/MIME certificates for user %s', [$user->getUID()]),
			OutputInterface::VERBOSITY_VERBOSE
		);

		$certificates = $this->smimeService->findAllCertificates($user->getUID());

		foreach ($certificates as $certificate) {
			$exportContent = [
				'id' => $certificate->getId(),
				'certificate' => $this->crypto->decrypt($certificate->getCertificate()),
				'privateKey' => $certificate->getPrivateKey() !== null ? $this->crypto->decrypt((string)$certificate->getPrivateKey()) : null,
			];

			try {
				$exportDestination->addFileContents(
					str_replace(
						MailAccountMigrator::FILENAME_PLACEHOLDER, (string)$certificate->getId(), self::SMIME_CERTIFICATE_FILES
					), json_encode($exportContent, JSON_THROW_ON_ERROR)
				);
			} catch (JsonException|UserMigrationException $exception) {
				throw new UserMigrationException(
					"Failed to export S/MIME certificates for user {$user->getUID()}",
					previous: $exception
				);
			}


		}
	}

	/**
	 * Import all S/MIME certificates added by the user.
	 *
	 * @return array Contains the old certificate ID as array key and the new
	 *               certificate ID as value.
	 *
	 * @throws UserMigrationException
	 * @throws ServiceException
	 */
	public function importCertificates(IUser $user, IImportSource $importSource, OutputInterface $output): array {
		$output->writeln(
			$this->l10n->t('Importing S/MIME certificates for user %s', [$user->getUID()]),
			OutputInterface::VERBOSITY_VERBOSE
		);

		$certificatesMapping = [];

		if ($importSource->pathExists(self::SMIME_CERTIFICATE_FOLDER)) {
			$certificates = $importSource->getFolderListing(self::SMIME_CERTIFICATE_FOLDER);

			foreach ($certificates as $certificateFilePath) {
				$certificate = json_decode($importSource->getFileContents($certificateFilePath), true);
				$this->validateCertificate($certificate);

				$newCertificate = $this->smimeService->createCertificate($user->getUID(), $certificate['certificate'], $certificate['privateKey']);

				$oldCertificateId = $certificate['id'];
				$certificatesMapping[$oldCertificateId] = $newCertificate->getId();
			}

		}

		if (count($certificatesMapping) === 0) {
			$output->writeln(
				$this->l10n->t('No S/MIME certificates for user %s found. Continue...', [$user->getUID()]),
				OutputInterface::VERBOSITY_VERBOSE
			);
		}

		return $certificatesMapping;
	}

	/**
	 * Delete all S/MIME certificates added by the user.
	 *
	 * @param IUser $user
	 * @return void
	 * @throws DoesNotExistException
	 * @throws ServiceException
	 */
	public function deleteAllUserCertificates(IUser $user, OutputInterface $output): void {
		$output->writeln(
			$this->l10n->t('Delete existing S/MIME certificates for user %s', [$user->getUID()]),
			OutputInterface::VERBOSITY_VERBOSE
		);

		$allCertificates = $this->smimeService->findAllCertificates($user->getUID());
		foreach ($allCertificates as $cert) {
			$this->smimeService->deleteCertificate($cert->getId(), $user->getUID());
		}
	}

	/**
	 * Validate the parsed certificates to ensure they
	 * have the expected structure and types.
	 *
	 * @throws UserMigrationException
	 */
	private function validateCertificate(mixed $certificate): void {
		$certificateArrayIsValid = is_array($certificate);

		$idIsValid = $certificateArrayIsValid
			&& array_key_exists('id', $certificate)
			&& is_int($certificate['id']);

		$certificateNameIsValid = $certificateArrayIsValid
			&& array_key_exists('certificate', $certificate)
			&& is_string($certificate['certificate']);

		$privateKeyIsValid = $certificateArrayIsValid
			&& array_key_exists('privateKey', $certificate)
			&& (is_string($certificate['privateKey']) || is_null($certificate['privateKey']));

		if (
			!$idIsValid
			|| !$certificateNameIsValid
			|| !$privateKeyIsValid
		) {
			throw new UserMigrationException('Invalid certificate entry');
		}
	}
}
