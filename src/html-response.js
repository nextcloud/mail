/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

// eslint-disable-next-line import/no-unresolved, n/no-missing-import
import 'vite/modulepreload-polyfill'

// injected styles
// eslint-disable-next-line import/no-unresolved
import styles from './css/html-response.css?inline'

// iframe-resizer client script
import 'iframe-resizer/js/iframeResizer.contentWindow.js'

// Fix width of some newsletter mails
document.addEventListener('DOMContentLoaded', function() {
	const style = document.createElement('style')
	style.innerHTML = styles
	document.head.append(style)

	for (const el of document.querySelectorAll('*')) {
		if (!el.style['max-width']) {
			el.style['max-width'] = '100%'
		}
	}
})
