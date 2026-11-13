/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { domainToASCII } from '../../../util/domainToAscii.js'

describe('domainToASCII', () => {
	it('keeps an ASCII domain unchanged', () => {
		expect(domainToASCII('example.com')).toBe('example.com')
	})

	it('converts an internationalized domain to punycode', () => {
		expect(domainToASCII('müller.de')).toBe('xn--mller-kva.de')
	})

	it('lowercases the domain', () => {
		expect(domainToASCII('Example.COM')).toBe('example.com')
	})

	it('returns an empty string for an invalid domain', () => {
		expect(domainToASCII('exa mple.de')).toBe('')
	})
})
