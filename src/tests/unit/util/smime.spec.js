/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { compareSmimeCertificates } from '../../../util/smime'

describe('smime', () => {
	describe('compareSmimeCertificates', () => {
		it('correctly sorts certificates', () => {
			let a, b

			a = {info: { notAfter: 200 }}
			b = {info: { notAfter: 100 }}
			expect(compareSmimeCertificates(a, b)).toEqual(-1)

			a = {info: { notAfter: 100 }}
			b = {info: { notAfter: 100 }}
			expect(compareSmimeCertificates(a, b)).toEqual(0)

			a = {info: { notAfter: 100 }}
			b = {info: { notAfter: 200 }}
			expect(compareSmimeCertificates(a, b)).toEqual(1)
		})
	})
})
