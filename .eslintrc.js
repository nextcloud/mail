/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
module.exports = {
	extends: [
		'@nextcloud'
	],
	globals: {
		expect: true,
		OC: true,
		OCA: true,
		OCP: true,
		t: true,
		__webpack_public_path__: true,
		__webpack_nonce__: true,
	},
	rules: {
		'jsdoc/require-jsdoc': 'off',
	},
}
