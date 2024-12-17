/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

// injected styles
import '../css/html-response.css'

// iframe-resizer client script
import 'iframe-resizer/js/iframeResizer.contentWindow.js'

// Fix width of some newsletter mails
document.addEventListener('DOMContentLoaded', function() {
	for (const el of document.querySelectorAll('*')) {
		if (!el.style['max-width']) {
			el.style['max-width'] = '100%'
		}
	}
})
