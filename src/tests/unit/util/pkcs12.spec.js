/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { convertPkcs12ToPem } from "../../../util/pkcs12";

describe('pkcs12', () => {
	describe('convertPkcs12ToPem', () => {
		it('correctly extracts an RSA certificate and private key', () => {
			const certPem = readTestData('user@imap.localhost.crt').replaceAll('\n', '\r\n')
			const keyPem = readTestData('user@imap.localhost.key').replaceAll('\n', '\r\n')
			const pkcs12Der = toArrayBuffer(readTestDataRaw('user@imap.localhost.p12'))

			expect(convertPkcs12ToPem(pkcs12Der, 'smime')).toEqual({
				certificate: certPem,
				privateKey: keyPem,
			})
		})

		it('correctly handles PKCS#12 files with multiple cert bags', () => {
			const certPem = readTestData('user@imap.localhost.chain.crt').replaceAll('\n', '\r\n')
			const keyPem = readTestData('user@imap.localhost.key').replaceAll('\n', '\r\n')
			const pkcs12Der = toArrayBuffer(readTestDataRaw('user@imap.localhost.chain.p12'))

			expect(convertPkcs12ToPem(pkcs12Der, 'smime')).toEqual({
				certificate: certPem,
				privateKey: keyPem,
			})
		})
	})
})
