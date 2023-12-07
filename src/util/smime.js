/**
 * @copyright Copyright (c) 2023 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * Compare two S/MIME certificates by their expiry dates (notAfter).
 * This function is intended to be used with Array.sort().
 *
 * @param {{info: {notAfter: number}}} a Certificate a
 * @param {{info: {notAfter: number}}} b Certificate b
 * @return {number} Comparison result (-1, 0, 1)
 */
export function compareSmimeCertificates(a, b) {
	if (a.info.notAfter < b.info.notAfter) {
		return 1
	} else if (a.info.notAfter > b.info.notAfter) {
		return -1
	} else {
		return 0
	}
}
