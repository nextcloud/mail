/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
module.exports = {
	plugins: [
		'@babel/plugin-syntax-dynamic-import'
	],
	presets: [
		[
			'@babel/preset-env',
			{
				modules: process.env.NODE_ENV === 'testing' ? 'commonjs' : undefined,
				useBuiltIns: process.env.NODE_ENV === 'testing' ? 'usage' : 'entry',
				corejs: 3
			}
		]
	]
};
