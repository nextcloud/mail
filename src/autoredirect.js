/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

// eslint-disable-next-line import/no-unresolved, n/no-missing-import
import 'vite/modulepreload-polyfill'

document.addEventListener('DOMContentLoaded', () => {
	const linkEl = document.getElementById('redirectLink')

	if (linkEl) {
		linkEl.click()
	}
})
