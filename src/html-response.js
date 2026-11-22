/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { limitToContainerWidth } from './util/fitWidth.ts'
import { keepAttributeAspectRatio } from './util/imageAspectRatio.ts'

// injected styles
import '../css/html-response.css'
import '@iframe-resizer/child'

document.addEventListener('DOMContentLoaded', function() {
	for (const el of document.querySelectorAll('*')) {
		limitToContainerWidth(el)
	}

	for (const image of document.querySelectorAll('img')) {
		keepAttributeAspectRatio(image)
	}
})
