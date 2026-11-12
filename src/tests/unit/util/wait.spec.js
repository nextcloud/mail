/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { wait } from '../../../util/wait.js'

describe('wait', () => {
	it('waits', () => new Promise((done) => {
		wait(0).then(done)
	}))
})
