/**
 * @copyright Copyright (c) 2023 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
		certificate: forge.pki.certificateToPem(certBags[0].cert),
		privateKey: forge.pki.privateKeyToPem(keyBags[0].key),
	}
}

export class InvalidPkcs12CertificateError extends Error {

	constructor() {
		super()
		this.name = InvalidPkcs12CertificateError.name
	}

}
