/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { randomId } from '../../../util/randomId.js'

describe('util/randomId test suite', () => {
	it('should generate hex strings', () => {
		for (let i = 0; i < 10; i++) {
			expect(randomId()).toMatch(/^[a-f0-9]+$/)
		}
	})
})
