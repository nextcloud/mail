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
use Horde_Imap_Client;
use Horde_Imap_Client_Data_Fetch;
use Horde_Imap_Client_Fetch_Query;
use Horde_Mail_Rfc822_Address;
use Horde_Mime_Exception;
use Horde_Mime_Headers;
use Horde_Mime_Headers_ContentParam_ContentType;
use Horde_Mime_Part;
use OCA\Mail\Address;
use OCA\Mail\AddressList;
use OCA\Mail\Db\SmimeCertificate;
use OCA\Mail\Db\SmimeCertificateMapper;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Exception\SmimeCertificateParserException;
use OCA\Mail\Exception\SmimeDecryptException;
use OCA\Mail\Exception\SmimeEncryptException;
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
	 *
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
		$valid = openssl_pkcs7_verify(
			$messageTemp,
			0,
			null,
			[$this->certificateManager->getAbsoluteBundlePath()],
		);

		if (is_int($valid)) {
			// OpenSSL error
			return false;
		}

		return $valid;
	}

	/**
	 * Attempt to extract the signed content from a signed S/MIME message.
	 * Can be used to extract opaque signed content even if the signature itself can't be verified.
	 *
	 * Warning: This method does not attempt to verify the signature.
	 *
	 * Requires the openssl extension.
	 *
	 * @param string $message Whole message including all headers and parts as stored on IMAP
	 * @return string Signed content
	 *
	 * @throws ServiceException If no signed content can be extracted
	 */
	public function extractSignedContent(string $message): string {
		// Ideally, we should use the more modern openssl cms module as it is a superset of the
		// smime/pkcs7 module. Unfortunately, it is only supported since php 8.
		// Ref https://www.php.net/manual/en/function.openssl-cms-verify.php

		$verifiedContentTemp = $this->tempManager->getTemporaryFile();
		$messageTemp = $this->tempManager->getTemporaryFile();
		$messageTempHandle = fopen($messageTemp, 'wb');
		fwrite($messageTempHandle, $message);
		fclose($messageTempHandle);
		/** @psalm-suppress NullArgument */
		$valid = openssl_pkcs7_verify(
			$messageTemp,
			PKCS7_NOSIGS | PKCS7_NOVERIFY,
			null,
			[$this->certificateManager->getAbsoluteBundlePath()],
			null,
			$verifiedContentTemp,
		);

		if (is_int($valid)) {
			// OpenSSL error
			throw new ServiceException('Failed to extract signed content');
		}

		$verifiedContent = file_get_contents($verifiedContentTemp);
		if ($verifiedContent === false) {
			throw new ServiceException('Could not read back verified content');
		}

		return $verifiedContent;
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

		if (!isset($certificateData['subject']['emailAddress'])
			&& !isset($certificateData['subject']['CN'])) {
			throw new SmimeCertificateParserException('Certificate does not contain an email address');
		}

		return new SmimeCertificateInfo(
			$certificateData['subject']['CN'] ?? null,
			$certificateData['subject']['emailAddress'] ?? $certificateData['subject']['CN'],
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
	 * Get all S/MIME certificates belonging to an email address.
	 *
	 * @param string $emailAddress
	 * @param string $userId
	 * @return SmimeCertificate[]
	 *
	 * @throws ServiceException If the database query fails
	 */
	public function findCertificatesByEmailAddress(string $emailAddress,
												  string $userId): array {
		try {
			return $this->certificateMapper->findAllByEmailAddress($userId, $emailAddress);
		} catch (\OCP\DB\Exception $e) {
			throw new ServiceException(
				'Failed to fetch certificates by email address: ' . $e->getMessage(),
				0,
				$e,
			);
		}
	}

	/**
	 * Get all S/MIME certificates belonging to an address list
	 *
	 * @param AddressList $addressList
	 * @param string $userId
	 * @return SmimeCertificate[]
	 *
	 * @throws ServiceException If the database query fails or converting an email address failed
	 */
	public function findCertificatesByAddressList(AddressList $addressList, string $userId): array {
		$emailAddresses = [];

		foreach ($addressList->iterate() as $address) {
			/** @var Address $address */
			try {
				$emailAddress = $address->getEmail();
			} catch (\Exception $e) {
				throw new ServiceException($e->getMessage(), 0, $e);
			}

			if (!empty($emailAddress)) {
				$emailAddresses[] = $emailAddress;
			}
		}

		try {
			return $this->certificateMapper->findAllByEmailAddresses($userId, $emailAddresses);
		} catch (\OCP\DB\Exception $e) {
			throw new ServiceException('Failed to fetch certificates by email addresses: ' . $e->getMessage(), 0, $e);
		}
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

	/**
	 * Decrypt full text of a MIME message.
	 * This method assumes the given mime part text to be encrypted without checking.
	 *
	 * @param string $mimePartText
	 * @param SmimeCertificate $certificate The certificate needs to contain a private key.
	 * @return string Full text of decrypted MIME message. It will probably contain multiple parts.
	 *
	 * @throws ServiceException If the given certificate does not have a private key or can't be decrypted
	 * @throws SmimeDecryptException If openssl reports an error during decryption
	 */
	public function decryptMimePartText(string $mimePartText,
									SmimeCertificate $certificate): string {
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
		file_put_contents($inPath, $mimePartText);
		if (!openssl_pkcs7_decrypt($inPath, $outPath, $decryptedCertificate, $decryptedKey)) {
			throw new SmimeDecryptException('Failed to decrypt MIME part text');
		}

		$decryptedMessage = file_get_contents($outPath);

		// Handle smime-type="signed-data" as the content is opaque until verified
		$headers = Horde_Mime_Headers::parseHeaders($decryptedMessage);
		if (!isset($headers['content-type'])) {
			return $decryptedMessage;
		}
		/** @var Horde_Mime_Headers_ContentParam_ContentType $contentType */
		$contentType = $headers['content-type'];

		if ($contentType->ptype !== 'application'
			|| $contentType->stype !== 'pkcs7-mime'
			|| !isset($contentType['smime-type'])
			|| $contentType['smime-type'] !== 'signed-data') {
			return $decryptedMessage;
		}

		try {
			// TODO: propagate signature verification status
			$decryptedMessage = $this->extractSignedContent($decryptedMessage);
		} catch (ServiceException $e) {
			throw new ServiceException(
				'Failed to extract nested signed data: ' . $e->getMessage(),
				0,
				$e,
			);
		}

		return $decryptedMessage;
	}

	/**
	 * Try to decrypt a raw data fetch from horde.
	 * The fetch needs to contain at least envelope, headerText and fullText.
	 * See the addDecryptQueries() method.
	 *
	 * This method will do nothing to the full text if the message is not encrypted.
	 *
	 * @param Horde_Imap_Client_Data_Fetch $message
	 * @param string $userId
	 * @return string
	 *
	 * @throws ServiceException
	 */
	public function decryptDataFetch(Horde_Imap_Client_Data_Fetch $message, string $userId): string {
		$encryptedText = $message->getFullMsg();
		if (!$this->isEncrypted($message)) {
			return $encryptedText;
		}

		$decryptedText = null;
		$envelope = $message->getEnvelope();
		foreach ($envelope->to as $recipient) {
			/** @var Horde_Mail_Rfc822_Address $recipient  */
			$recipientAddress = $recipient->bare_address;
			$certs = $this->findCertificatesByEmailAddress(
				$recipientAddress,
				$userId,
			);

			foreach ($certs as $cert) {
				try {
					$decryptedText = $this->decryptMimePartText($encryptedText, $cert);
				} catch (ServiceException | SmimeDecryptException $e) {
					// Certificate probably didn't match -> continue
					// TODO: filter a real decryption error
					// (is hard because openssl doesn't return a proper error code)
					continue;
				}
			}
		}

		if ($decryptedText === null) {
			throw new ServiceException('Failed to find a suitable S/MIME certificate for decryption');
		}

		return $decryptedText;
	}

	public function addEncryptionCheckQueries(Horde_Imap_Client_Fetch_Query $query,
											  bool $peek = true): void {
		if (!$query->contains(Horde_Imap_Client::FETCH_HEADERTEXT)) {
			$query->headerText([
				'peek' => $peek,
			]);
		}
	}

	public function addDecryptQueries(Horde_Imap_Client_Fetch_Query $query,
									  bool $peek = true): void {
		$this->addEncryptionCheckQueries($query, $peek);
		$query->envelope();
		if (!$query->contains(Horde_Imap_Client::FETCH_FULLMSG)) {
			$query->fullText([
				'peek' => $peek,
			]);
		}
	}

	public function isEncrypted(Horde_Imap_Client_Data_Fetch $message): bool {
		$headers = $message->getHeaderText('0', Horde_Imap_Client_Data_Fetch::HEADER_PARSE);
		if (!isset($headers['content-type'])) {
			return false;
		}

		/** @var Horde_Mime_Headers_ContentParam_ContentType $contentType */
		$contentType = $headers['content-type'];
		return $contentType->ptype === 'application'
			&& str_ends_with($contentType->stype, 'pkcs7-mime')
			&& isset($contentType['smime-type'])
			&& $contentType['smime-type'] === 'enveloped-data';
	}

	/**
	 * Encrypt a MIME part using the given certificates.
	 *
	 * @param Horde_Mime_Part $part
	 * @param SmimeCertificate[] $certificates
	 * @return Horde_Mime_Part New MIME part containing the encrypted message and the signature
	 *
	 * @throws ServiceException If decrypting the certificates fails
	 * @throws SmimeEncryptException If encrypting the message fails
	 */
	public function encryptMimePart(Horde_Mime_Part  $part, array $certificates): Horde_Mime_Part {
		try {
			/** @var string[] $decryptedCertificates */
			$decryptedCertificates = array_map(function (SmimeCertificate $certificate) {
				return $this->crypto->decrypt($certificate->getCertificate());
			}, $certificates);
		} catch (Exception $e) {
			throw new ServiceException('Failed to decrypt certificate: ' . $e->getMessage(), 0, $e);
		}

		$inPath = $this->tempManager->getTemporaryFile();
		$outPath = $this->tempManager->getTemporaryFile();
		file_put_contents($inPath, $part->toString([
			'canonical' => true,
			'headers' => true,
		]));

		/**
		 * Content-Type is application/x-pkcs7-mime by default.
		 * The flag PKCS7_NOOLDMIMETYPE / 0x400 let openssl use application/pkcs7-mime as Content-Type.
		 * PKCS7_NOOLDMIMETYPE is not available as constant in PHP.
		 *
		 * https://github.com/openssl/openssl/blob/9a2f78e14a67eeaadefc77d05f0778fc9684d26c/include/openssl/pkcs7.h.in#L211
		 * https://github.com/php/php-src/blob/51b70e4414b43a571ebd743d752cf4cbd1556eb5/ext/openssl/openssl_arginfo.h#L572-L580
		 */
		if (!openssl_pkcs7_encrypt($inPath, $outPath, $decryptedCertificates, [], 0x400, OPENSSL_CIPHER_AES_128_CBC)) {
			throw new SmimeEncryptException('Failed to encrypt MIME part');
		}

		try {
			$parsedPart = Horde_Mime_Part::parseMessage(file_get_contents($outPath), [
				'forcemime' => true,
			]);
		} catch (Horde_Mime_Exception $e) {
			throw new SmimeEncryptException('Failed to parse signed MIME part: ' . $e->getMessage(), 0, $e);
		}

		return $parsedPart;
	}
}
