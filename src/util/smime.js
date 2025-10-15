/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
