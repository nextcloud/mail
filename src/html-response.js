/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { keepAttributeAspectRatio } from './util/imageAspectRatio.ts'

// injected styles
import '../css/html-response.css'
import '@iframe-resizer/child'

// Fix width of some newsletter mails
document.addEventListener('DOMContentLoaded', function() {
	for (const el of document.querySelectorAll('*')) {
		if (!el.style['max-width']) {
			el.style['max-width'] = '100%'
		}
	}

	for (const image of document.querySelectorAll('img')) {
		keepAttributeAspectRatio(image)
	}
})
