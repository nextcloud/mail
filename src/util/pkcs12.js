/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import forge from 'node-forge'

/**
 * Convert a PKCS #12 certificate from DER to PEM format.
 * This function will extract the certificate and private key.
 *
 * It will fail if the given certificate contains more than one certificate or private key which is
 * not very common as OpenSSL does not support it.
 *
 * @param {ArrayBuffer} pkcs12Der The PKCS #12 certificate in DER format
 * @param {string=} password Password to use for decrypting the certificate
 * @return {{certificate: string, privateKey: string}} The certificate and private key in PEM format
 */
export function convertPkcs12ToPem(pkcs12Der, password) {
	const der = new forge.util.ByteBuffer(pkcs12Der)
	const asn1 = forge.asn1.fromDer(der)
	const p12 = forge.pkcs12.pkcs12FromAsn1(asn1, password)

	const getBags = (bagType) => p12.getBags({ bagType })[bagType]
	const certBags = getBags(forge.pki.oids.certBag)
	const keyBags = getBags(forge.pki.oids.pkcs8ShroudedKeyBag)

	if (certBags.length === 0) {
		throw new InvalidPkcs12CertificateError('The PKCS #12 certificate must contain at least one certificate')
	}

	if (keyBags.length !== 1) {
		throw new InvalidPkcs12CertificateError('The PKCS #12 certificate must contain a single key')
	}

	return {
		certificate: certBags.map((certBag) => forge.pki.certificateToPem(certBag.cert)).join('\r\n'),
		privateKey: forge.pki.privateKeyToPem(keyBags[0].key),
	}
}

export class InvalidPkcs12CertificateError extends Error {

	constructor() {
		super()
		this.name = InvalidPkcs12CertificateError.name
	}

}
