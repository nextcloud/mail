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

use Exception;
use Horde_Mime_Exception;
use Horde_Mime_Part;
use OCA\Mail\Db\SmimeCertificate;
use OCA\Mail\Db\SmimeCertificateMapper;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Exception\SmimeCertificateParserException;
use OCA\Mail\Exception\SmimeSignException;
use OCA\Mail\Model\EnrichedSmimeCertificate;
use OCA\Mail\Model\SmimeCertificateInfo;
use OCA\Mail\Model\SmimeCertificatePurposes;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\ICertificateManager;
use OCP\ITempManager;
use OCP\Security\ICrypto;

class SmimeService {
	private ITempManager $tempManager;
	private ICertificateManager $certificateManager;
	private ICrypto $crypto;
	private SmimeCertificateMapper $certificateMapper;
	private ITimeFactory $timeFactory;

	public function __construct(ITempManager           $tempManager,
								ICertificateManager    $certificateManager,
								ICrypto                $crypto,
								SmimeCertificateMapper $certificateMapper,
								ITimeFactory           $timeFactory) {
		$this->tempManager = $tempManager;
		$this->certificateManager = $certificateManager;
		$this->crypto = $crypto;
		$this->certificateMapper = $certificateMapper;
		$this->timeFactory = $timeFactory;
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
	 * @return SmimeCertificateInfo Metadata of the certificate
	 *
	 * @throws SmimeCertificateParserException If the certificate can't be parsed
	 */
	public function parseCertificate(string $certificate): SmimeCertificateInfo {
		// TODO: support parsing email addresses from SANs
		// TODO: support multiple email addresses per certificate

		$certificateData = openssl_x509_parse($certificate);
		if ($certificateData === false) {
			throw new SmimeCertificateParserException('Could not parse certificate');
		}

		if (!isset($certificateData['subject']['emailAddress'])) {
			throw new SmimeCertificateParserException('Certificate does not contain an email address');
		}

		return new SmimeCertificateInfo(
			$certificateData['subject']['CN'] ?? null,
			$certificateData['subject']['emailAddress'] ?? null,
			$certificateData['validTo_time_t'],
		);
	}

	/**
	 * Get S/MIME related certificate purposes of the given certificate.
	 *
	 * @param string $certificate X509 certificate encoded as PEM
	 * @return SmimeCertificatePurposes
	 */
	public function getCertificatePurposes(string $certificate): SmimeCertificatePurposes {
		$caBundle = [$this->certificateManager->getAbsoluteBundlePath()];
		return new SmimeCertificatePurposes(
			openssl_x509_checkpurpose($certificate, X509_PURPOSE_SMIME_SIGN, $caBundle),
			openssl_x509_checkpurpose($certificate, X509_PURPOSE_SMIME_ENCRYPT, $caBundle),
		);
	}

	/**
	 * Enrich S/MIME certificate from the database with additional information.
	 *
	 * @param SmimeCertificate $certificate
	 * @return EnrichedSmimeCertificate
	 *
	 * @throws ServiceException If decrypting the certificate fails
	 * @throws SmimeCertificateParserException If parsing the certificate fails
	 */
	public function enrichCertificate(SmimeCertificate $certificate): EnrichedSmimeCertificate {
		try {
			$decryptedCertificate = $this->crypto->decrypt($certificate->getCertificate());
		} catch (Exception $e) {
			throw new ServiceException(
				'Failed to decrypt certificate: ' . $e->getMessage(),
				0,
				$e,
			);
		}

		return new EnrichedSmimeCertificate(
			$certificate,
			$this->parseCertificate($decryptedCertificate),
			$this->getCertificatePurposes($decryptedCertificate),
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
	 * Get a single S/MIME certificate by id.
	 *
	 * @param int $certificateId
	 * @param string $userId
	 * @return SmimeCertificate
	 *
	 * @throws DoesNotExistException
	 */
	public function findCertificate(int $certificateId, string $userId): SmimeCertificate {
		return $this->certificateMapper->find($certificateId, $userId);
	}

	/**
	 * Find all S/MIME certificates of the given user.
	 *
	 * @param string $userId
	 * @return SmimeCertificate[]
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
	 * Store an S/MIME certificate in the database.
	 *
	 * @param string $userId
	 * @param string $certificateData
	 * @param ?string $privateKeyData
	 * @return SmimeCertificate
	 *
	 * @throws ServiceException
	 */
	public function createCertificate(string $userId,
									  string $certificateData,
									  ?string $privateKeyData): SmimeCertificate {
		$emailAddress = $this->parseCertificate($certificateData)->getEmailAddress();

		$certificate = new SmimeCertificate();
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

	/**
	 * Sign a MIME part using the given certificate and private key.
	 *
	 * @param Horde_Mime_Part $part
	 * @param SmimeCertificate $certificate
	 * @return Horde_Mime_Part New MIME part containing the signed message and the signature
	 *
	 * @throws SmimeSignException If signing the message fails
	 * @throws ServiceException If decrypting the certificate or private key fails or the private key is missing
	 */
	public function signMimePart(Horde_Mime_Part  $part,
								 SmimeCertificate $certificate): Horde_Mime_Part {
		if ($certificate->getPrivateKey() === null) {
			throw new ServiceException('Certificate does not have a private key');
		}

		try {
			$decryptedCertificate = $this->crypto->decrypt($certificate->getCertificate());
			$decryptedKey = $this->crypto->decrypt($certificate->getPrivateKey());
		} catch (Exception $e) {
			throw new ServiceException(
				'Failed to decrypt certificate or private key: ' . $e->getMessage(),
				0,
				$e,
			);
		}

		$inPath = $this->tempManager->getTemporaryFile();
		$outPath = $this->tempManager->getTemporaryFile();
		file_put_contents($inPath, $part->toString([
			'canonical' => true,
			'headers' => true,
		]));
		if (!openssl_pkcs7_sign($inPath, $outPath, $decryptedCertificate, $decryptedKey, null)) {
			throw new SmimeSignException('Failed to sign MIME part');
		}

		try {
			$parsedPart = Horde_Mime_Part::parseMessage(file_get_contents($outPath), [
				'forcemime' => true,
			]);
		} catch (Horde_Mime_Exception $e) {
			throw new SmimeSignException(
				'Failed to parse signed MIME part: ' . $e->getMessage(),
				0,
				$e,
			);
		}

		// Not required but makes sense. Otherwise, it's just a generic MIME format message.
		$parsedPart->setContents("This is a cryptographically signed message in MIME format.\n");

		// Retain signature but replace signed part content with original content
		$parsedPart[1] = $part;

		return $parsedPart;
	}
}
