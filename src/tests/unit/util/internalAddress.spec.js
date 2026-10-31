/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { parseInternalAddress } from '../../../util/internalAddress.js'

describe('parseInternalAddress', () => {
	it.each([
		['example.test', 'example.test', 'domain'],
		['  example.test  ', 'example.test', 'domain'],
		['@example.test', 'example.test', 'domain'],
		[' @ example.test ', 'example.test', 'domain'],
		['alice@example.test', 'alice@example.test', 'individual'],
		[' alice@example.test ', 'alice@example.test', 'individual'],
		['', '', 'domain'],
		['   ', '', 'domain'],
		['@', '', 'domain'],
	])('parses "%s" as "%s" of type %s', (input, address, type) => {
		expect(parseInternalAddress(input)).toEqual({ address, type })
	})
})
