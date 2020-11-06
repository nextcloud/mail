/**
 * @copyright 2020 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author 2020 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

// injected styles
import '../css/html-response.css'

// iframe-resizer client script
import 'iframe-resizer/js/iframeResizer.contentWindow.js'
window.iFrameResizer = {
	onMessage: (message) => {
		if (!message.cssVars) {
			return
		}

		// inject received css vars
		Object.entries(message.cssVars).forEach(([key, val]) => {
			document.documentElement.style.setProperty(key, val)
		})
	},
}
