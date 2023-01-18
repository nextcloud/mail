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

namespace OCA\Mail\Service;

use OCA\Mail\Db\SMimeCertificate;
use OCA\Mail\Db\SMimeCertificateMapper;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Exception\SMimeCertificateParserException;
use OCA\Mail\Model\SMimeCertificateInfo;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\ICertificateManager;
use OCP\ITempManager;
use OCP\Security\ICrypto;

class SMimeService {
	private ITempManager $tempManager;
	private ICertificateManager $certificateManager;
	private ICrypto $crypto;
	private SMimeCertificateMapper $certificateMapper;

	public function __construct(ITempManager $tempManager,
								ICertificateManager $certificateManager,
								ICrypto $crypto,
								SMimeCertificateMapper $certificateMapper) {
		$this->tempManager = $tempManager;
		$this->certificateManager = $certificateManager;
		$this->crypto = $crypto;
		$this->certificateMapper = $certificateMapper;
	}

	/**
	 * Attempt to verify a message signed with S/MIME.
	 * Requires the openssl extension.
	 *
	 * @param string $message Whole message including all headers and parts as stored on IMAP
	 * @return bool
	 */
	public function verifyMessage(string $message): bool {
		// Ideally, we should use the more modern openssl cms module as it is a superset of the
		// smime/pkcs7 module. Unfortunately, it is only supported since php 8.
		// Ref https://www.php.net/manual/en/function.openssl-cms-verify.php

		$messageTemp = $this->tempManager->getTemporaryFile();
		$messageTempHandle = fopen($messageTemp, 'wb');
		fwrite($messageTempHandle, $message);
		fclose($messageTempHandle);
		/** @psalm-suppress NullArgument */
		$valid = openssl_pkcs7_verify($messageTemp, 0, null, [
			$this->certificateManager->getAbsoluteBundlePath(),
		]);
		if (is_int($valid)) {
			// OpenSSL error
			return false;
		}

		return $valid;
	}

	/**
	 * Parse a X509 certificate.
	 *
	 * @param string $certificate X509 certificate encoded as PEM
	 * @return SMimeCertificateInfo Metadata of the certificate
	 *
	 * @throws SMimeCertificateParserException If the certificate can't be parsed
	 */
	public function parseCertificate(string $certificate): SMimeCertificateInfo {
		$certificateData = openssl_x509_parse($certificate);
		if ($certificateData === false) {
			throw new SMimeCertificateParserException('Could not parse certificate');
		}

		if (!isset($certificateData['subject']['emailAddress'])) {
			throw new SMimeCertificateParserException('Certificate does not contain an email address');
		}

		return new SMimeCertificateInfo(
			$certificateData['subject']['CN'] ?? null,
			$certificateData['subject']['emailAddress'] ?? null,
			$certificateData['validTo_time_t'],
		);
	}

	/**
	 * Check if the given private key corresponds to the given certificate.
	 *
	 * @param string $certificate X509 certificate encoded as PEM
	 * @param string $privateKey Private key encoded as PEM
	 * @return bool True if the private key matches the certificate, false otherwise or if the private key is protected by a passphrase
	 */
	public function checkPrivateKey(string $certificate, string $privateKey): bool {
		return openssl_x509_check_private_key($certificate, $privateKey);
	}

	/**
	 * Find all S/MIME certificates of the given user.
	 *
	 * @param string $userId
	 * @return SMimeCertificate[]
	 *
	 * @throws ServiceException
	 */
	public function findAllCertificates(string $userId): array {
		return $this->certificateMapper->findAll($userId);
	}

	/**
	 * Delete an S/MIME certificate by its id.
	 *
	 * @param int $id
	 * @param string $userId
	 * @return void
	 *
	 * @throws DoesNotExistException
	 */
	public function deleteCertificate(int $id, string $userId): void {
		$certificate = $this->certificateMapper->find($id, $userId);
		$this->certificateMapper->delete($certificate);
	}

	/**
	 * Find an S/MIME certificate by its id.
	 *
	 * @param string $userId
	 * @param string $certificateData
	 * @param ?string $privateKeyData
	 * @return SMimeCertificate
	 *
	 * @throws ServiceException
	 */
	public function createCertificate(string $userId,
									  string $certificateData,
									  ?string $privateKeyData): SMimeCertificate {
		$emailAddress = $this->parseCertificate($certificateData)->getEmailAddress();

		$certificate = new SMimeCertificate();
		$certificate->setUserId($userId);
		$certificate->setEmailAddress($emailAddress);
		$certificate->setCertificate($this->crypto->encrypt($certificateData));
		if ($privateKeyData !== null) {
			if (!$this->checkPrivateKey($certificateData, $privateKeyData)) {
				throw new ServiceException('Private key does not match certificate or is protected by a passphrase');
			}
			$certificate->setPrivateKey($this->crypto->encrypt($privateKeyData));
		}
		return $this->certificateMapper->insert($certificate);
	}
}
