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
			const certPem = readTestData('user@imap.localhost.crt').replaceAll('\n', '\r\n')
			const keyPem = readTestData('user@imap.localhost.key').replaceAll('\n', '\r\n')
			const pkcs12Der = toArrayBuffer(readTestDataRaw('user@imap.localhost.chain.p12'))

			expect(convertPkcs12ToPem(pkcs12Der, 'smime')).toEqual({
				certificate: certPem,
				privateKey: keyPem,
			})
		})
	})
})
