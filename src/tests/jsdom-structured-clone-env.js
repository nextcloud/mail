/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import JSDOMEnvironment from 'jest-environment-jsdom'

export default class FixJSDOMEnvironment extends JSDOMEnvironment {

	constructor(...args) {
		super(...args)

		// Fix missing support for structuredClone() in jsdom
		// Ref https://github.com/jsdom/jsdom/issues/3363
		this.global.structuredClone = structuredClone
	}
}
